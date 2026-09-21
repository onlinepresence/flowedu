<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Offline-first demo-key verification (ControlDesk document shape).
 *
 * A key is a signed document:
 *   {"payload": {"expires_at": "YYYY-MM-DD"|null, "host": "..."|null,
 *                 "issued_at": "YYYY-MM-DD"},
 *    "signature": "<hex ed25519 detached over canonical payload JSON>",
 *    "algorithm": "ed25519"}
 * Canonical JSON = recursive key sort, unescaped slashes/unicode (same
 * scheme as licence-file verification). Signatures check against the
 * baked-in public key (college.demo_public_key); no network involved.
 *
 * The key screen accepts two inputs (see routes/web.php demo.key.store):
 * - pasted document JSON  -> verified offline, directly;
 * - bare code              -> POSTed once to ControlDesk
 *   /api/v1/demo-keys/verify, the returned document is cached locally
 *   (Setting demo.cached_key_document) and then verified offline like any
 *   other document. A bare code entered while offline falls back to the
 *   cached document; offline with no cache stays on the key screen.
 *
 * Outcomes: tamper (bad signature), clock-suspect (issued in the future)
 * fail into enforced mode — the key screen keeps showing — with a loud
 * (warning-level) log. A null expires_at means NEVER: accepted, but logged
 * at warning level so the marketing key stays visible. Expired, host
 * mismatch, malformed input and missing public key fail quieter (info),
 * same screen. Bare opaque tokens (e.g. a heartbeat token pasted at the
 * wrong door) keep their own wrong-door copy. Live (linked) installs
 * refuse demo keys outright, whatever they look like.
 */
final class DemoKeyVerifier
{
    public const REASON_OK = 'ok';

    public const REASON_INVALID = 'invalid';

    public const REASON_EXPIRED = 'expired';

    public const REASON_HOST_MISMATCH = 'host_mismatch';

    public const REASON_WRONG_DOOR = 'wrong_door';

    public const REASON_CLOCK_SKEW = 'clock_skew';

    public const REASON_UNCONFIGURED = 'unconfigured';

    public const CACHE_KEY = 'demo.cached_key_document';

    public const ENV_PREFIX_ENC = 'enc:';

    public const ENV_PREFIX_B64 = 'b64:';

    /**
     * Check either input shape.
     *
     * On success the verified document is returned in hand when one exists
     * (pasted input, fresh online response, or offline cache fallback) so
     * callers can persist durability layers without re-parsing.
     *
     * @return array{ok: bool, reason: string, document: ?array}
     */
    public function check(string $code, ?string $host = null): array
    {
        $candidate = trim($code);

        if ($candidate === '') {
            Log::info('demo.key.rejected', ['reason' => self::REASON_INVALID]);

            return ['ok' => false, 'reason' => self::REASON_INVALID, 'document' => null];
        }

        // One-way street: live (linked) installs refuse demo keys outright,
        // whatever the key looks like. Elevation never flows back to demo.
        if ($this->isLiveInstall()) {
            Log::warning('demo.key.refused_live', ['host' => $host]);

            return ['ok' => false, 'reason' => self::REASON_INVALID, 'document' => null];
        }

        $document = $this->parseDocument($candidate);
        if ($document !== null) {
            return $this->checkDocument($document, $host, false);
        }

        if ($this->looksLikeBareToken($candidate)) {
            Log::info('demo.key.rejected', ['reason' => self::REASON_WRONG_DOOR, 'host' => $host]);

            return ['ok' => false, 'reason' => self::REASON_WRONG_DOOR, 'document' => null];
        }

        return $this->checkBareCode($candidate, $host);
    }

    /**
     * Boolean seam kept for callers that only need pass/fail.
     */
    public function verify(string $code, ?string $host = null): bool
    {
        return $this->check($code, $host)['ok'];
    }

    /**
     * Verify an already-parsed document offline. Shared by pasted input,
     * freshly fetched documents, and the offline cache fallback.
     *
     * @return array{ok: bool, reason: string, document: ?array}
     */
    public function checkDocument(array $document, ?string $host = null, bool $fromCache = false): array
    {
        $payload = $document['payload'] ?? null;
        $signatureHex = $document['signature'] ?? null;
        $algorithm = strtolower((string) ($document['algorithm'] ?? ''));

        if (! is_array($payload) || ! is_string($signatureHex) || $signatureHex === '' || $algorithm !== 'ed25519') {
            Log::info('demo.key.rejected', ['reason' => self::REASON_INVALID, 'host' => $host]);

            return ['ok' => false, 'reason' => self::REASON_INVALID, 'document' => null];
        }

        $key = $this->publicKey();
        if ($key === null) {
            Log::warning('demo.key.unconfigured', ['host' => $host]);

            return ['ok' => false, 'reason' => self::REASON_UNCONFIGURED, 'document' => null];
        }

        $signature = $this->hexDecode($signatureHex);
        if ($signature === null) {
            Log::info('demo.key.rejected', ['reason' => self::REASON_INVALID, 'host' => $host]);

            return ['ok' => false, 'reason' => self::REASON_INVALID, 'document' => null];
        }

        try {
            $valid = sodium_crypto_sign_verify_detached($signature, self::canonicalJson($payload), $key);
        } catch (\Throwable $e) {
            $valid = false;
        }

        if ($valid !== true) {
            Log::warning('demo.key.tamper_suspect', ['host' => $host]);

            return ['ok' => false, 'reason' => self::REASON_INVALID, 'document' => null];
        }

        $today = now()->toDateString();

        // Null expiry = NEVER (the marketing key): accepted, but loudly.
        $exp = $payload['expires_at'] ?? null;
        $neverExpires = $exp === null;
        if (! $neverExpires && (! is_string($exp) || $exp === '' || $exp < $today)) {
            Log::info('demo.key.rejected', ['reason' => self::REASON_EXPIRED, 'host' => $host]);

            return ['ok' => false, 'reason' => self::REASON_EXPIRED, 'document' => null];
        }

        // Issued-in-the-future beyond a day of leeway: either the server
        // clock is wrong or the document is misissued. Enforce + log loudly.
        $iat = $payload['issued_at'] ?? null;
        if (is_string($iat) && $iat !== '' && $iat > now()->addDay()->toDateString()) {
            Log::warning('demo.key.clock_suspect', ['host' => $host, 'issued_at' => $iat]);

            return ['ok' => false, 'reason' => self::REASON_CLOCK_SKEW, 'document' => null];
        }

        // Host binding enforced only when present on both sides.
        $issuedHost = $payload['host'] ?? null;
        if ($host !== null && $host !== '' && $issuedHost !== null && $issuedHost !== '') {
            $bound = is_array($issuedHost) ? $issuedHost : [$issuedHost];
            $bound = array_map(static fn ($h): string => strtolower((string) $h), $bound);
            if (! in_array(strtolower($host), $bound, true)) {
                Log::info('demo.key.rejected', ['reason' => self::REASON_HOST_MISMATCH, 'host' => $host]);

                return ['ok' => false, 'reason' => self::REASON_HOST_MISMATCH, 'document' => null];
            }
        }

        if ($neverExpires) {
            Log::warning('demo.key.accepted', ['host' => $host, 'never_expires' => true]);
        } else {
            Log::info('demo.key.accepted', ['host' => $host, 'exp' => $exp, 'from_cache' => $fromCache]);
        }

        return ['ok' => true, 'reason' => self::REASON_OK, 'document' => $document];
    }

    /**
     * Bare code path: one online fetch, then everything offline. A network
     * failure falls back to the locally cached document; no cache means
     * the key screen, as always.
     *
     * @return array{ok: bool, reason: string, document: ?array}
     */
    public function checkBareCode(string $code, ?string $host = null): array
    {
        $url = trim((string) config('controlplane.url'));
        if ($url === '') {
            Log::info('demo.key.rejected', ['reason' => self::REASON_INVALID, 'host' => $host, 'offline' => true]);

            return ['ok' => false, 'reason' => self::REASON_INVALID, 'document' => null];
        }

        try {
            $response = Http::timeout(config('controlplane.timeout', 8))
                ->acceptJson()
                ->post(rtrim($url, '/').'/api/v1/demo-keys/verify', [
                    'code' => $code,
                    'app_version' => (string) config('controlplane.app_version', '1.0.0'),
                ]);
        } catch (ConnectionException $e) {
            return $this->checkCachedDocument($host);
        } catch (\Throwable $e) {
            report($e);

            return $this->checkCachedDocument($host);
        }

        if (! $response->successful()) {
            $error = (string) $response->json('error', '');
            if ($error === 'expired') {
                return ['ok' => false, 'reason' => self::REASON_EXPIRED, 'document' => null];
            }

            Log::info('demo.key.rejected', ['reason' => self::REASON_INVALID, 'host' => $host]);

            return ['ok' => false, 'reason' => self::REASON_INVALID, 'document' => null];
        }

        $document = $response->json();
        if (! is_array($document) || ! isset($document['payload']) || ! is_array($document['payload'])) {
            Log::info('demo.key.rejected', ['reason' => self::REASON_INVALID, 'host' => $host]);

            return ['ok' => false, 'reason' => self::REASON_INVALID, 'document' => null];
        }

        $this->cacheDocument($document);

        return $this->checkDocument($document, $host);
    }

    /**
     * Offline fallback: verify whatever document was cached from the last
     * good online check. No cache = the key screen stays.
     *
     * @return array{ok: bool, reason: string, document: ?array}
     */
    public function checkCachedDocument(?string $host = null): array
    {
        $cached = $this->readCachedDocument();
        if ($cached === null) {
            Log::info('demo.key.rejected', ['reason' => self::REASON_INVALID, 'host' => $host, 'offline' => true]);

            return ['ok' => false, 'reason' => self::REASON_INVALID, 'document' => null];
        }

        return $this->checkDocument($cached, $host, true);
    }

    /**
     * Encode a verified plain value (canonical document or bare code) as a
     * single opaque .env-safe token. Encrypted via the app key so the stored
     * value is not guessable; falls back to base64 only if encryption is
     * unavailable. Prefixes keep legacy plaintext readable.
     */
    public static function encodeForEnv(string $plain): string
    {
        $plain = trim($plain);

        try {
            return self::ENV_PREFIX_ENC.encrypt($plain);
        } catch (\Throwable $e) {
            return self::ENV_PREFIX_B64.base64_encode($plain);
        }
    }

    /**
     * Decode a stored DEMO_KEY value back to its plain form. Legacy
     * plaintext (no prefix) passes through; corrupt prefixed values yield
     * null so callers fall back to the key screen instead of bypassing.
     */
    public static function decodeStoredKey(?string $stored): ?string
    {
        $candidate = trim((string) $stored);
        if ($candidate === '') {
            return null;
        }

        if (str_starts_with($candidate, self::ENV_PREFIX_ENC)) {
            try {
                $plain = decrypt(substr($candidate, strlen(self::ENV_PREFIX_ENC)));
            } catch (\Throwable $e) {
                return null;
            }
            $plain = trim((string) $plain);

            return $plain === '' ? null : $plain;
        }

        if (str_starts_with($candidate, self::ENV_PREFIX_B64)) {
            $decoded = base64_decode(substr($candidate, strlen(self::ENV_PREFIX_B64)), true);
            if (! is_string($decoded)) {
                return null;
            }
            $decoded = trim($decoded);

            return $decoded === '' ? null : $decoded;
        }

        return $candidate;
    }

    /**
     * Whether a stored DEMO_KEY value counts as present (decodable and
     * non-empty). Corrupt encoded values do not count.
     */
    public static function hasStoredKey(?string $stored): bool
    {
        return self::decodeStoredKey($stored) !== null;
    }

    /**
     * Canonical JSON shared with document signers: recursive key sort,
     * unescaped slashes/unicode. Public so fixtures sign exactly this.
     */
    public static function canonicalJson(mixed $value): string
    {
        if (is_array($value)) {
            if (array_is_list($value)) {
                $items = array_map(self::canonicalJson(...), $value);

                return '['.implode(',', $items).']';
            }
            ksort($value);
            $parts = [];
            foreach ($value as $k => $v) {
                $parts[] = json_encode((string) $k, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).':'.self::canonicalJson($v);
            }

            return '{'.implode(',', $parts).'}';
        }

        return (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Try to parse pasted input as a signed document. Null = not a document.
     */
    private function parseDocument(string $candidate): ?array
    {
        $trimmed = trim($candidate);
        if ($trimmed === '' || $trimmed[0] !== '{') {
            return null;
        }

        $decoded = json_decode($trimmed, true);
        if (! is_array($decoded) || ! isset($decoded['payload']) || ! is_array($decoded['payload'])) {
            return null;
        }

        return $decoded;
    }

    /**
     * A long bare opaque token (no envelope, no dots, token charset) is not
     * a demo key at all — most likely a server-side secret such as a
     * ControlDesk heartbeat token pasted at the wrong door. Short codes
     * stay on the online bare-code path instead: length is what separates a
     * human access code from a server token.
     */
    private function looksLikeBareToken(string $candidate): bool
    {
        return ! str_contains($candidate, '{')
            && ! str_contains($candidate, '.')
            && strlen($candidate) >= 40
            && (bool) preg_match('/^[A-Za-z0-9\-_=+\/]+$/', $candidate);
    }

    private function hexDecode(string $hex): ?string
    {
        if ((strlen($hex) % 2) !== 0 || ! ctype_xdigit($hex)) {
            return null;
        }

        $decoded = hex2bin($hex);

        return $decoded !== false && strlen($decoded) === SODIUM_CRYPTO_SIGN_BYTES
            ? $decoded
            : null;
    }

    private function publicKey(): ?string
    {
        $raw = trim((string) config('college.demo_public_key'));
        if ($raw === '') {
            return null;
        }

        // ControlDesk prints hex; accept it verbatim. Legacy base64 still works.
        if (strlen($raw) === 2 * SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES && ctype_xdigit($raw)) {
            $decoded = hex2bin($raw);
        } else {
            $decoded = base64_decode($raw, true);
        }

        return is_string($decoded) && strlen($decoded) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
            ? $decoded
            : null;
    }

    /**
     * Persist the last verified document for offline rechecks
     * (Setting demo.cached_key_document). Idempotent; never throws.
     */
    public function cacheDocument(array $document): void
    {
        try {
            Setting::query()->updateOrCreate(
                ['setting_key' => self::CACHE_KEY],
                [
                    'setting_value' => json_encode($document, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'data_type' => 'json',
                    'category' => 'demo',
                    'description' => 'Last ControlDesk-verified demo key document (offline fallback).',
                ]
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function readCachedDocument(): ?array
    {
        try {
            $raw = Setting::query()->where('setting_key', self::CACHE_KEY)->value('setting_value');
        } catch (\Throwable $e) {
            return null;
        }

        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) && isset($decoded['payload']) && is_array($decoded['payload'])
            ? $decoded
            : null;
    }

    /**
     * Live = linked install (deployment UUID configured and on the licence
     * row). Demo keys are refused there unconditionally: one-way street.
     */
    private function isLiveInstall(): bool
    {
        try {
            return app(\App\Services\ControlPlane\LicenceEnrollmentService::class)->isLinked();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
