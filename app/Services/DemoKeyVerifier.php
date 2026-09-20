<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Offline demo-key verification.
 *
 * Keys are signed, self-contained tokens — no network involved:
 *   demo1.<base64url(payload JSON)>.<base64url(ed25519 signature)>
 * Payload: {"h": "<host the key was issued for>", "exp": "YYYY-MM-DD",
 * "iat": "YYYY-MM-DD"}. The signature covers the exact payload segment
 * bytes and verifies against college.demo_public_key (DEMO_PUBLIC_KEY).
 *
 * Outcomes: bad signature (tamper) and issued-in-the-future keys (clock
 * suspect) fail into enforced mode — the key screen keeps showing — with a
 * loud (warning-level) log. Expiry, host mismatch, malformed input and a
 * missing public key fail quieter (info), same screen. A bare opaque token
 * (e.g. a ControlDesk heartbeat token pasted at the wrong door) gets its
 * own wrong-door copy: those belong in .env, not here.
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

    /**
     * Detailed check.
     *
     * @return array{ok: bool, reason: string}
     */
    public function check(string $code, ?string $host = null): array
    {
        $candidate = trim($code);

        if ($candidate === '') {
            Log::info('demo.key.rejected', ['reason' => self::REASON_INVALID]);

            return ['ok' => false, 'reason' => self::REASON_INVALID];
        }

        // One-way street: live (linked) installs refuse demo keys outright,
        // whatever the key looks like. Elevation never flows back to demo.
        if ($this->isLiveInstall()) {
            Log::warning('demo.key.refused_live', ['host' => $host]);

            return ['ok' => false, 'reason' => self::REASON_INVALID];
        }

        if ($this->looksLikeBareToken($candidate)) {
            Log::info('demo.key.rejected', ['reason' => self::REASON_WRONG_DOOR, 'host' => $host]);

            return ['ok' => false, 'reason' => self::REASON_WRONG_DOOR];
        }

        $parts = explode('.', $candidate);
        if (count($parts) !== 3 || $parts[0] !== 'demo1') {
            Log::info('demo.key.rejected', ['reason' => self::REASON_INVALID, 'host' => $host]);

            return ['ok' => false, 'reason' => self::REASON_INVALID];
        }

        [, $payloadSegment, $signatureSegment] = $parts;

        $payloadJson = $this->base64UrlDecode($payloadSegment);
        $signature = $this->base64UrlDecode($signatureSegment);
        $payload = is_string($payloadJson) ? json_decode($payloadJson, true) : null;

        if (! is_array($payload)) {
            Log::info('demo.key.rejected', ['reason' => self::REASON_INVALID, 'host' => $host]);

            return ['ok' => false, 'reason' => self::REASON_INVALID];
        }

        $key = $this->publicKey();
        if ($key === null) {
            Log::warning('demo.key.unconfigured', ['host' => $host]);

            return ['ok' => false, 'reason' => self::REASON_UNCONFIGURED];
        }

        if (! is_string($signature)) {
            Log::info('demo.key.rejected', ['reason' => self::REASON_INVALID, 'host' => $host]);

            return ['ok' => false, 'reason' => self::REASON_INVALID];
        }

        try {
            $valid = sodium_crypto_sign_verify_detached($signature, $payloadSegment, $key);
        } catch (\Throwable $e) {
            $valid = false;
        }

        if ($valid !== true) {
            Log::warning('demo.key.tamper_suspect', ['host' => $host]);

            return ['ok' => false, 'reason' => self::REASON_INVALID];
        }

        $today = now()->toDateString();

        $exp = $payload['exp'] ?? null;
        if (! is_string($exp) || $exp === '' || $exp < $today) {
            Log::info('demo.key.rejected', ['reason' => self::REASON_EXPIRED, 'host' => $host]);

            return ['ok' => false, 'reason' => self::REASON_EXPIRED];
        }

        // Issued-in-the-future beyond a day of leeway: either the server
        // clock is wrong or the key is misissued. Enforce + log loudly.
        $iat = $payload['iat'] ?? null;
        if (is_string($iat) && $iat !== '' && $iat > now()->addDay()->toDateString()) {
            Log::warning('demo.key.clock_suspect', ['host' => $host, 'iat' => $iat]);

            return ['ok' => false, 'reason' => self::REASON_CLOCK_SKEW];
        }

        // Host binding enforced only when both sides present it (the
        // scheduler verifies with no host and skips this leg).
        $issuedHost = $payload['h'] ?? null;
        if ($host !== null && $host !== '') {
            $hosts = is_array($issuedHost) ? $issuedHost : [$issuedHost];
            $hosts = array_map(static fn ($h): string => strtolower((string) $h), $hosts);
            if (! in_array(strtolower($host), $hosts, true)) {
                Log::info('demo.key.rejected', ['reason' => self::REASON_HOST_MISMATCH, 'host' => $host]);

                return ['ok' => false, 'reason' => self::REASON_HOST_MISMATCH];
            }
        }

        Log::info('demo.key.accepted', ['host' => $host, 'exp' => $exp]);

        return ['ok' => true, 'reason' => self::REASON_OK];
    }

    /**
     * Boolean seam kept for callers that only need pass/fail.
     */
    public function verify(string $code, ?string $host = null): bool
    {
        return $this->check($code, $host)['ok'];
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

    /**
     * A bare opaque token (no dots, token charset, reasonably long) is not
     * a demo key at all — most likely a server-side secret such as a
     * ControlDesk heartbeat token pasted at the wrong door.
     */
    private function looksLikeBareToken(string $candidate): bool
    {
        return ! str_contains($candidate, '.')
            && strlen($candidate) >= 16
            && (bool) preg_match('/^[A-Za-z0-9\-_=+\/]+$/', $candidate);
    }

    private function base64UrlDecode(string $segment): string|false
    {
        $padded = strtr($segment, '-_', '+/');
        $remainder = strlen($padded) % 4;
        if ($remainder > 0) {
            $padded .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode($padded, true);
    }

    private function publicKey(): ?string
    {
        $raw = trim((string) config('college.demo_public_key'));
        if ($raw === '') {
            return null;
        }

        $decoded = base64_decode($raw, true);

        return $decoded !== false && strlen($decoded) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
            ? $decoded
            : null;
    }
}
