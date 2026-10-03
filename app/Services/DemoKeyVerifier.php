<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use App\Services\ControlPlane\ControlPlaneKeys;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Offline-first demo-key verification (ControlDesk document shape).
 *
 * Demo keys are FULL access by design: a valid signature means unlock
 * everything. The signed document payload is only
 *   {"expires_at": "YYYY-MM-DD"|null, "host": "..."|null, "issued_at": "YYYY-MM-DD"}
 * plus {signature, algorithm: ed25519}. There is deliberately no
 * enabled/modules/caps list — never gate any module on demo and never
 * enforce student caps while a demo is unlocked. Host binding and expiry
 * live INSIDE the signed payload; trust nothing outside the signature.
 *
 * Verify flow: on key entry, POST {DESK_URL}/api/v1/demo-keys/verify with
 * {code, app_version, modules_in_use[]} (telemetry only, never enforcement).
 * On 200, verify the signature against the single ControlDesk public key
 * (CONTROL_PLANE_PUBLIC_KEY — the same key that verifies live licence
 * blobs) via
 * sodium_crypto_sign_verify_detached(hex2bin(signature),
 *   json_encode(payload, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
 *   hex2bin(public_key)).
 * The canonical (sorted) encoding is tried first for backward compat with
 * previously minted documents; the plain json_encode form from the contract
 * is the fallback. The whole document is cached locally
 * (Setting demo.cached_key_document) for offline use.
 *
 * Re-verify online when possible (boot + ~24h interval): revocation only
 * bites on the next online check; offline copies keep working until
 * expires_at. Each online hit stamps demo.last_online_check so the desk's
 * last_used_at stays fresh.
 *
 * The key screen accepts two inputs (see routes/web.php demo.key.store):
 * - pasted document JSON  -> verified offline, directly;
 * - bare code (demo-XXXXXXXX) -> POSTed once to ControlDesk, the returned
 *   document is cached locally and then verified offline like any other
 *   document. A bare code entered while offline falls back to the cached
 *   document; offline with no cache stays on the key screen.
 *
 * Outcomes: tamper (bad signature), clock-suspect (issued in the future)
 * fail into enforced mode — the key screen keeps showing — with a loud
 * (warning-level) log. A null expires_at means NEVER: accepted, but logged
 * at warning level so the marketing key stays visible. Expired, host
 * mismatch, malformed input and missing public key fail quieter (info),
 * same screen. Desk 422 slugs map cleanly: unknown_code (not recognized),
 * key_revoked (revoked), key_expired (expired). Bare opaque tokens (e.g. a
 * heartbeat token pasted at the wrong door) keep their own wrong-door copy.
 * Live (linked) installs refuse demo keys outright, whatever they look like.
 *
 * Demos never reuse the deployment heartbeat token flow — they use the short
 * demo-XXXXXXXX code, not Sanctum tokens. Convert-to-paid happens desk-side;
 * FlowEdu enrollment just accepts the claim code afterwards (and voids any
 * local demo key on elevation).
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

    public const REASON_REVOKED = 'revoked';

    public const REASON_UNKNOWN_CODE = 'unknown_code';

    public const CACHE_KEY = 'demo.cached_key_document';

    public const LAST_CHECK_KEY = 'demo.last_online_check';

    public const LAST_STATUS_KEY = 'demo.last_verify_status';

    public const MODULES_USED_KEY = 'demo.modules_in_use';

    public const REVERIFY_INTERVAL_HOURS = 24;

    public const ENV_PREFIX_ENC = 'enc:';

    public const ENV_PREFIX_B64 = 'b64:';

    /** @var array<string, bool> per-request memo of isUnlocked by host key */
    private array $unlockedMemo = [];

    /** @var array|null cached demoStatus memo per request */
    private ?array $statusMemo = null;

    private bool $statusMemoSet = false;

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
     * Never expects enabled/modules/caps: a valid signature, a future (or
     * null = never) expiry, and a matching host when bound are the only
     * gates. Everything else is telemetry or desk-side.
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

        $valid = ControlPlaneKeys::verifyPayload($payload, $signatureHex, $key);

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

        // Host binding enforced only when present on both sides. The binding
        // lives inside the signed payload; nothing outside it is trusted.
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
     * the key screen, as always. Definitive desk answers (revoked, expired,
     * unknown) never fall back — revocation bites on the next online check.
     *
     * Telemetry (read-only, never enforcement): app_version + modules_in_use
     * ride along so the desk sees what the prospect tried.
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
                    'modules_in_use' => $this->modulesInUse(),
                ]);
        } catch (ConnectionException $e) {
            return $this->checkCachedDocument($host);
        } catch (\Throwable $e) {
            report($e);

            return $this->checkCachedDocument($host);
        }

        if (! $response->successful()) {
            $reason = $this->mapDeskError((string) $response->json('error', ''));

            // Stamp the online hit even on failure so the desk triage and
            // local re-verify throttling stay in sync. Revoked sticks; other
            // failures just record the last status without locking offline
            // copies that never saw a definitive answer.
            $this->stampOnlineCheck($reason);

            if ($reason === self::REASON_EXPIRED) {
                return ['ok' => false, 'reason' => self::REASON_EXPIRED, 'document' => null];
            }
            if ($reason === self::REASON_REVOKED) {
                Log::warning('demo.key.revoked', ['host' => $host]);

                return ['ok' => false, 'reason' => self::REASON_REVOKED, 'document' => null];
            }
            if ($reason === self::REASON_UNKNOWN_CODE) {
                Log::info('demo.key.rejected', ['reason' => self::REASON_UNKNOWN_CODE, 'host' => $host]);

                return ['ok' => false, 'reason' => self::REASON_UNKNOWN_CODE, 'document' => null];
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
        $this->stampOnlineCheck(self::REASON_OK);

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
     * Whether demo mode currently unlocks ALL features: demo flag on, not a
     * live (linked) install, not revoked by the last online check, and a
     * locally verifiable document (cached, stored, env bare code pending
     * first verify, or accepted session) whose signature, expiry and host
     * all pass. Never enforces student caps — callers bypass caps on true.
     *
     * Memoized per request: can() fans out 10-20x per page.
     */
    public function isUnlocked(?string $host = null): bool
    {
        $host ??= $this->currentHost();
        $memoKey = $host ?? '';

        if (array_key_exists($memoKey, $this->unlockedMemo)) {
            return $this->unlockedMemo[$memoKey];
        }

        $unlocked = $this->resolveUnlocked($host);
        $this->unlockedMemo[$memoKey] = $unlocked;

        return $unlocked;
    }

    /**
     * Human-facing demo status for the owner/admin banner: expiry (or Never)
     * plus host lock, sourced offline from the cached or stored document.
     * Null when no verifiable document exists yet.
     *
     * @return array{expires_at: ?string, host: string|array|null, never: bool}|null
     */
    public function demoStatus(?string $host = null): ?array
    {
        if ($this->statusMemoSet) {
            return $this->statusMemo;
        }

        $this->statusMemoSet = true;

        $document = $this->readCachedDocument();
        if ($document === null) {
            $stored = self::decodeStoredKey(config('college.demo_key'));
            if ($stored !== null && str_starts_with(trim($stored), '{')) {
                $document = $this->parseDocument($stored);
            }
        }

        if ($document === null || ! is_array($document['payload'] ?? null)) {
            $this->statusMemo = null;

            return null;
        }

        // Only report documents that verify (signature + host); expiry is
        // reported even when past so the banner can show "expired".
        $payload = $document['payload'];
        $key = $this->publicKey();
        if ($key === null) {
            $this->statusMemo = null;

            return null;
        }

        $signature = (string) ($document['signature'] ?? '');
        if (! ControlPlaneKeys::verifyPayload($payload, $signature, $key)) {
            $this->statusMemo = null;

            return null;
        }

        if (strtolower((string) ($document['algorithm'] ?? '')) !== 'ed25519') {
            $this->statusMemo = null;

            return null;
        }

        $exp = $payload['expires_at'] ?? null;
        $this->statusMemo = [
            'expires_at' => is_string($exp) ? $exp : null,
            'host' => $payload['host'] ?? null,
            'never' => $exp === null,
        ];

        return $this->statusMemo;
    }

    /**
     * Re-verify the stored bare code online when due (no successful online
     * hit in the last 24h). Boot + scheduler both funnel here. Returns the
     * fresh check, or null when no re-verify was attempted (not due, no
     * stored bare code, offline with no cache to compare, or live install).
     * Never throws; offline copies keep working until expires_at.
     *
     * @return array{ok: bool, reason: string, document: ?array}|null
     */
    public function reverifyIfDue(?string $host = null, bool $force = false): ?array
    {
        try {
            if (! (bool) config('college.demo_mode', false)) {
                return null;
            }
            if ($this->isLiveInstall()) {
                return null;
            }
            if (! $force && ! $this->shouldReverify()) {
                return null;
            }

            $stored = self::decodeStoredKey(config('college.demo_key'));
            if ($stored === null || str_starts_with(trim($stored), '{') || $this->looksLikeBareToken($stored)) {
                // Document inputs and wrong-door tokens have no code to
                // re-POST, and there is no desk hit to stamp. Skip entirely —
                // isUnlocked() already verifies these offline (memoized per
                // request) without extra logging here.
                return null;
            }

            $host ??= $this->currentHost();

            return $this->checkBareCode($stored, $host);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Whether the last online check is older than the re-verify interval
     * (or never happened). Missing settings table / DB errors count as due
     * so boot still attempts a verify rather than silently skipping.
     */
    public function shouldReverify(): bool
    {
        try {
            $raw = Setting::query()->where('setting_key', self::LAST_CHECK_KEY)->value('setting_value');
        } catch (\Throwable) {
            return true;
        }

        if (! is_string($raw) || trim($raw) === '') {
            return true;
        }

        try {
            $last = \Carbon\Carbon::parse($raw);
        } catch (\Throwable) {
            return true;
        }

        return $last->lt(now()->subHours(self::REVERIFY_INTERVAL_HOURS));
    }

    /**
     * Whether the last definitive online answer was revocation. When true,
     * both the gate and the licence unlock refuse until a fresh key lands.
     */
    public function isRevoked(): bool
    {
        try {
            $raw = Setting::query()->where('setting_key', self::LAST_STATUS_KEY)->value('setting_value');
        } catch (\Throwable) {
            return false;
        }

        return is_string($raw) && trim($raw) === self::REASON_REVOKED;
    }

    /**
     * Record a feature key the user actually opened (telemetry only, never
     * enforcement). Called from the licence middleware on allowed passes.
     * Unique, capped, idempotent, never throws.
     */
    public function recordModuleUse(string $feature): void
    {
        $feature = trim($feature);
        if ($feature === '') {
            return;
        }

        try {
            $current = $this->modulesInUse();
            if (in_array($feature, $current, true)) {
                return;
            }
            $current[] = $feature;
            $current = array_values(array_unique($current));
            // Cap so a curious prospect cannot grow the row unboundedly.
            $current = array_slice($current, 0, 100);

            Setting::query()->updateOrCreate(
                ['setting_key' => self::MODULES_USED_KEY],
                [
                    'setting_value' => json_encode($current, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'data_type' => 'json',
                    'category' => 'demo',
                    'description' => 'Feature keys actually opened on this demo (telemetry for ControlDesk verify calls).',
                ]
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Feature keys actually opened on this demo (telemetry for verify calls).
     *
     * @return list<string>
     */
    public function modulesInUse(): array
    {
        try {
            $raw = Setting::query()->where('setting_key', self::MODULES_USED_KEY)->value('setting_value');
        } catch (\Throwable) {
            return [];
        }

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded)
            ? array_values(array_filter(array_map('strval', $decoded)))
            : [];
    }

    /**
     * Stamp an online verify hit (success or definitive failure) so the
     * 24h throttle advances and the desk's last_used_at stays fresh.
     * Never throws.
     */
    public function stampOnlineCheck(string $status): void
    {
        try {
            Setting::query()->updateOrCreate(
                ['setting_key' => self::LAST_CHECK_KEY],
                [
                    'setting_value' => now()->toIso8601String(),
                    'data_type' => 'string',
                    'category' => 'demo',
                    'description' => 'Last online ControlDesk demo-key verify (throttles the ~24h re-verify).',
                ]
            );
            Setting::query()->updateOrCreate(
                ['setting_key' => self::LAST_STATUS_KEY],
                [
                    'setting_value' => $status,
                    'data_type' => 'string',
                    'category' => 'demo',
                    'description' => 'Last ControlDesk demo-key verify outcome (revoked sticks until a fresh key).',
                ]
            );
        } catch (\Throwable $e) {
            report($e);
        }

        $this->unlockedMemo = [];
        $this->statusMemoSet = false;
        $this->statusMemo = null;
    }

    /**
     * Clear any stuck revocation (fresh key entry path). Never throws.
     */
    public function clearRevocation(): void
    {
        try {
            Setting::query()->where('setting_key', self::LAST_STATUS_KEY)->delete();
        } catch (\Throwable $e) {
            report($e);
        }

        $this->unlockedMemo = [];
    }

    /**
     * Map a ControlDesk demo-verify 422 slug to an internal reason.
     * Unknown slugs fail closed as invalid.
     */
    public function mapDeskError(string $slug): string
    {
        $slug = trim($slug);

        return match ($slug) {
            'unknown_code' => self::REASON_UNKNOWN_CODE,
            'key_revoked', 'code_voided', 'deployment_revoked' => self::REASON_REVOKED,
            'key_expired', 'code_expired', 'expired' => self::REASON_EXPIRED,
            default => self::REASON_INVALID,
        };
    }

    /**
     * Human copy for the key screen per reason.
     */
    public static function errorCopy(string $reason): string
    {
        return match ($reason) {
            self::REASON_EXPIRED => __('That key has expired — ask ops for a fresh one.'),
            self::REASON_REVOKED => __('That key has been revoked — ask ops for a fresh one.'),
            self::REASON_UNKNOWN_CODE => __('That code was not recognized — check it and try again, or ask ops for a fresh code.'),
            self::REASON_WRONG_DOOR => __('That looks like a ControlDesk heartbeat token — those belong in .env as CONTROL_PLANE_TOKEN, not here. Use your demo key instead.'),
            self::REASON_HOST_MISMATCH => __('That key was issued for a different host.'),
            self::REASON_CLOCK_SKEW => __('That key is not valid yet — the server clock may be off. Ask ops for a fresh key.'),
            self::REASON_UNCONFIGURED => __('Demo keys are not configured on this install yet — ask ops to configure one.'),
            default => __('That key did not look right. Check it and try again.'),
        };
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
        return ControlPlaneKeys::canonicalJson($value);
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
     * (demo-XXXXXXXX) stay on the online bare-code path instead: length is
     * what separates a human access code from a server token.
     */
    private function looksLikeBareToken(string $candidate): bool
    {
        return ! str_contains($candidate, '{')
            && ! str_contains($candidate, '.')
            && strlen($candidate) >= 40
            && (bool) preg_match('/^[A-Za-z0-9\-_=+\/]+$/', $candidate);
    }

    private function publicKey(): ?string
    {
        // Single source: CONTROL_PLANE_PUBLIC_KEY verifies demo documents
        // and live licence blobs alike.
        return ControlPlaneKeys::publicKeyRaw();
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

        $this->unlockedMemo = [];
        $this->statusMemoSet = false;
        $this->statusMemo = null;
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
     * Resolve unlock without memo (isUnlocked wraps this with per-request
     * caching). Cached and stored documents verify strictly; a bare env code
     * with no cache yet is optimistically trusted (ops-set, pending the next
     * online re-verify which will cache the definitive document).
     */
    private function resolveUnlocked(?string $host): bool
    {
        if (! (bool) config('college.demo_mode', false)) {
            return false;
        }

        try {
            if ($this->isLiveInstall()) {
                return false;
            }
        } catch (\Throwable) {
            // isLiveInstall already guards; treat lookup failure as locked.
            return false;
        }

        if ($this->isRevoked()) {
            return false;
        }

        $cached = $this->readCachedDocument();
        if ($cached !== null) {
            // Offline copies keep working until expires_at; host still binds.
            // checkDocument logs at info on accept — acceptable once per
            // request thanks to the isUnlocked memo.
            return $this->checkDocument($cached, $host, true)['ok'] === true;
        }

        $stored = self::decodeStoredKey(config('college.demo_key'));
        if ($stored !== null && str_starts_with(trim($stored), '{')) {
            $doc = $this->parseDocument($stored);
            if ($doc === null) {
                return false;
            }

            return $this->checkDocument($doc, $host)['ok'] === true;
        }

        if ($stored !== null && ! $this->looksLikeBareToken($stored)) {
            // Bare demo-XXXXXXXX in env with no cache yet: ops-trusted until
            // the next online check caches the definitive document.
            return true;
        }

        // Session pass from a prior verified entry (durability layer 1 may
        // still be writing .env / cache on slow filesystems).
        try {
            if (function_exists('session') && session()->get('demo_key_accepted', false) === true) {
                return true;
            }
        } catch (\Throwable) {
            // No session in console / queue contexts.
        }

        return false;
    }

    private function currentHost(): ?string
    {
        try {
            if (function_exists('request') && app()->bound('request')) {
                $host = request()->getHost();

                return is_string($host) && $host !== '' ? $host : null;
            }
        } catch (\Throwable) {
            // Console / queue contexts have no request.
        }

        return null;
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
