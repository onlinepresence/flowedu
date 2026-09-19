<?php

declare(strict_types=1);

namespace App\Services\ControlPlane;

/**
 * Verifies signed licence blobs for the setup "import licence file" exit.
 *
 * Blob format (JSON):
 * {"payload": {...snapshot shape...}, "signature": "<base64 ed25519>"}
 *
 * The signature is a detached ed25519 signature over the canonical JSON of
 * "payload" (recursive key sort, no escaping of slashes/unicode), verified
 * with the baked-in public key (CONTROL_PLANE_PUBLIC_KEY, base64 of the
 * 32-byte raw key). Date validity: expires_at must be today or later.
 */
final class LicenceFileVerifier
{
    /**
     * @return array{ok: bool, payload?: array, error?: string}
     */
    public function verify(string $blob): array
    {
        $decoded = json_decode(trim($blob), true);
        if (! is_array($decoded) || ! isset($decoded['payload']) || ! is_array($decoded['payload'])) {
            return $this->fail(__('That file is not a licence blob (expected JSON with a "payload" object).'));
        }

        if (! isset($decoded['signature']) || ! is_string($decoded['signature']) || $decoded['signature'] === '') {
            return $this->fail(__('That file has no signature — it cannot be trusted. Ask ops for a signed licence file.'));
        }

        $key = $this->publicKey();
        if ($key === null) {
            return $this->fail(__('No licence public key is configured on this install. Ask ops to configure one, then import again.'));
        }

        $signature = base64_decode($decoded['signature'], true);
        if ($signature === false) {
            return $this->fail(__('That file has a malformed signature. Ask ops for a fresh licence file.'));
        }

        $message = $this->canonicalJson($decoded['payload']);

        try {
            $valid = sodium_crypto_sign_verify_detached($signature, $message, $key);
        } catch (\Throwable $e) {
            return $this->fail(__('That file could not be verified. Ask ops for a fresh licence file.'));
        }

        if ($valid !== true) {
            return $this->fail(__('Bad signature — this file was not issued by ops. Ask ops for a fresh licence file.'));
        }

        $payload = $decoded['payload'];
        $expires = (string) ($payload['licence']['expires_at'] ?? '');
        if ($expires === '' || $expires < now()->toDateString()) {
            return $this->fail(__('This licence file has expired — ask ops for a fresh licence file.'));
        }

        $starts = (string) ($payload['licence']['starts_at'] ?? '');
        if ($starts !== '' && $starts > now()->toDateString()) {
            return $this->fail(__('This licence file is not valid yet — ask ops for a fresh licence file.'));
        }

        return ['ok' => true, 'payload' => $payload];
    }

    public function canonicalJson(mixed $value): string
    {
        if (is_array($value)) {
            if (array_is_list($value)) {
                $value = array_map($this->canonicalJson(...), $value);

                return '['.implode(',', $value).']';
            }
            ksort($value);
            $parts = [];
            foreach ($value as $k => $v) {
                $parts[] = json_encode((string) $k, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).':'.$this->canonicalJson($v);
            }

            return '{'.implode(',', $parts).'}';
        }

        return (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function publicKey(): ?string
    {
        $raw = trim((string) config('controlplane.public_key'));
        if ($raw === '') {
            return null;
        }

        $decoded = base64_decode($raw, true);

        return $decoded !== false && strlen($decoded) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
            ? $decoded
            : null;
    }

    private function fail(string $error): array
    {
        return ['ok' => false, 'error' => $error];
    }
}
