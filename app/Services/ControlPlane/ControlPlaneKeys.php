<?php

declare(strict_types=1);

namespace App\Services\ControlPlane;

/**
 * Single source for the ControlDesk ed25519 public key.
 *
 * One keypair signs both licence-file blobs and demo-key documents, so one
 * public key verifies both: CONTROL_PLANE_PUBLIC_KEY and nothing else. Hex
 * (what ControlDesk prints) and base64 are accepted; the returned value is
 * always the raw 32-byte key or null when unconfigured.
 */
final class ControlPlaneKeys
{
    /**
     * Raw 32-byte ed25519 public key, or null when no key is configured.
     */
    public static function publicKeyRaw(): ?string
    {
        $raw = trim((string) config('controlplane.public_key'));
        if ($raw === '') {
            return null;
        }

        return self::decodeKey($raw);
    }

    /**
     * Whether any public key is configured (either env).
     */
    public static function isConfigured(): bool
    {
        return self::publicKeyRaw() !== null;
    }

    /**
     * Decode a hex or base64 key to raw bytes. Null when malformed.
     */
    public static function decodeKey(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        // Hex first (ControlDesk prints hex): exactly 64 hex chars.
        if (strlen($raw) === 2 * SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES && ctype_xdigit($raw)) {
            $decoded = hex2bin($raw);
            if (is_string($decoded) && strlen($decoded) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
                return $decoded;
            }
        }

        $decoded = base64_decode($raw, true);

        return is_string($decoded) && strlen($decoded) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
            ? $decoded
            : null;
    }

    /**
     * Decode a detached signature in hex (demo documents) or base64
     * (licence blobs). Null when malformed.
     */
    public static function decodeSignature(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if (strlen($raw) === 2 * SODIUM_CRYPTO_SIGN_BYTES && ctype_xdigit($raw)) {
            $decoded = hex2bin($raw);
            if (is_string($decoded) && strlen($decoded) === SODIUM_CRYPTO_SIGN_BYTES) {
                return $decoded;
            }
        }

        $decoded = base64_decode($raw, true);

        return is_string($decoded) && strlen($decoded) === SODIUM_CRYPTO_SIGN_BYTES
            ? $decoded
            : null;
    }

    /**
     * Canonical JSON shared with document signers: recursive key sort,
     * unescaped slashes/unicode. Matches DemoKeyVerifier + LicenceFileVerifier.
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
     * Verify a detached signature against a payload, trying the canonical
     * (sorted) encoding first and the plain json_encode form from the
     * ControlDesk demo-key contract as fallback. Either passing means the
     * document was signed by ControlDesk.
     */
    public static function verifyPayload(array $payload, string $signatureRaw, string $publicKeyRaw): bool
    {
        $signature = self::decodeSignature($signatureRaw);
        if ($signature === null) {
            return false;
        }

        $candidates = [
            self::canonicalJson($payload),
            (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];

        foreach (array_unique($candidates) as $message) {
            try {
                if (sodium_crypto_sign_verify_detached($signature, $message, $publicKeyRaw) === true) {
                    return true;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return false;
    }
}
