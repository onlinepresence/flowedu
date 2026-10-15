<?php

declare(strict_types=1);

namespace App\Services\ControlPlane;

/**
 * Verifies signed licence blobs for the setup "import licence file" exit.
 *
 * Two payload shapes are accepted (both signed the same way):
 * - Flat air-gap export from ControlDesk (Licence::exportPayload):
 *   {product, deployment_uuid, tier, enabled[], caps, starts_at,
 *   expires_at, issued_at, version, kid}
 * - Nested enrollment snapshot: {deployment_uuid, control_plane_token,
 *   licence{package_tier, max_active_students, starts_at, expires_at,
 *   core{}, modules{}}}
 *
 * The detached ed25519 signature covers the canonical JSON of "payload"
 * (recursive key sort, unescaped slashes/unicode); the as-received key
 * order is tried as a transition fallback. Verified with the single
 * ControlDesk public key (CONTROL_PLANE_PUBLIC_KEY — the same key that
 * verifies demo-key documents; hex or base64). Date validity: expires_at
 * must be today or later (ISO8601 datetimes accepted, compared as dates).
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

        if (strtolower((string) ($decoded['algorithm'] ?? '')) !== 'ed25519') {
            return $this->fail(__('That file has no valid signature algorithm — it cannot be trusted. Ask ops for a signed licence file.'));
        }

        if (! isset($decoded['signature']) || ! is_string($decoded['signature']) || $decoded['signature'] === '') {
            return $this->fail(__('That file has no signature — it cannot be trusted. Ask ops for a signed licence file.'));
        }

        $key = $this->publicKey();
        if ($key === null) {
            return $this->fail(__('No licence public key is configured on this install. Ask ops to configure one, then import again.'));
        }

        $payload = $decoded['payload'];

        if (! ControlPlaneKeys::verifyPayload($payload, $decoded['signature'], $key)) {
            return $this->fail(__('Bad signature — this file was not issued by ops. Ask ops for a fresh licence file.'));
        }

        // Product binding: flat exports carry it; nested snapshots predate
        // it and skip the check.
        if (array_key_exists('product', $payload) && (string) $payload['product'] !== '' && (string) $payload['product'] !== 'flowedu') {
            return $this->fail(__('That licence file was issued for a different product — ask ops for a FlowEdu file.'));
        }

        // Version gate: Desk mint version 1. Unknown versions fail closed.
        if (array_key_exists('version', $payload) && (int) $payload['version'] !== 1) {
            return $this->fail(__('That licence file uses an unsupported version — ask ops for a fresh licence file.'));
        }

        $fileUuid = trim((string) ($payload['deployment_uuid'] ?? ''));
        if ($fileUuid === '') {
            return $this->fail(__('That licence file has no deployment identity — ask ops for a fresh licence file.'));
        }

        // Deployment binding: first import sets the identity; afterwards a
        // file for another UUID is refused instead of silently re-linking.
        $configured = trim((string) config('controlplane.deployment_uuid'));
        if ($configured !== '' && $fileUuid !== $configured) {
            return $this->fail(__('That licence file belongs to a different deployment — ask ops for a file for this install.'));
        }

        $expires = (string) ($payload['expires_at'] ?? (is_array($payload['licence'] ?? null) ? ($payload['licence']['expires_at'] ?? '') : ''));
        if ($expires === '' || $this->toDate($expires) === null || $this->toDate($expires) < now()->toDateString()) {
            return $this->fail(__('This licence file has expired — ask ops for a fresh licence file.'));
        }

        $starts = (string) ($payload['starts_at'] ?? (is_array($payload['licence'] ?? null) ? ($payload['licence']['starts_at'] ?? '') : ''));
        if ($starts !== '' && $this->toDate($starts) !== null && $this->toDate($starts) > now()->toDateString()) {
            return $this->fail(__('This licence file is not valid yet — ask ops for a fresh licence file.'));
        }

        return ['ok' => true, 'payload' => $payload];
    }

    public function canonicalJson(mixed $value): string
    {
        return ControlPlaneKeys::canonicalJson($value);
    }

    /**
     * Parse an ISO8601 datetime or YYYY-MM-DD date to Y-m-d. Null when
     * unparseable. String comparison alone misorders ISO8601 against
     * bare dates, so everything is compared as dates.
     */
    private function toDate(string $value): ?string
    {
        try {
            return \Carbon\Carbon::parse(trim($value))->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function publicKey(): ?string
    {
        // Single source (see ControlPlaneKeys): the same key verifies
        // licence blobs and demo-key documents.
        return ControlPlaneKeys::publicKeyRaw();
    }

    private function fail(string $error): array
    {
        return ['ok' => false, 'error' => $error];
    }
}
