<?php

declare(strict_types=1);

namespace App\Services\ControlPlane;

use App\Models\Student;
use App\Models\User;
use App\Services\SchoolLicenceService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Minimal ControlDesk HTTP client (enroll + heartbeat ping).
 *
 * The ControlDesk contract is authoritative: enroll answers FLAT JSON
 * ({deployment_uuid, heartbeat_token, licence{tier, modules[], caps{},
 * valid_until}}) on 200, and {message, error} with a fixed slug set on
 * 422. Everything is normalized at this boundary to the internal arrays:
 * ['status' => 'ok', 'snapshot' => [...]]
 * ['status' => 'expired'|'consumed'|'invalid'|'network'|'unconfigured', 'message' => ...]
 * so callers (notably LicenceEnrollmentService::redeem(), which reads
 * snapshot.deployment_uuid / snapshot.control_plane_token) never change.
 * Fully fakeable via Http::fake() in tests.
 */
final class ControlPlaneClient
{
    public function enroll(string $code): array
    {
        $url = $this->baseUrl();
        if ($url === null) {
            return $this->fail('unconfigured', __('Enrollment server is not configured (CONTROL_PLANE_URL). Continue offline or import a licence file instead.'));
        }

        try {
            $response = Http::timeout(config('controlplane.timeout', 8))
                ->acceptJson()
                ->post($url.'/api/v1/enroll', [
                    'code' => $code,
                    'app_version' => (string) config('controlplane.app_version', '1.0.0'),
                ]);
        } catch (ConnectionException $e) {
            return $this->fail('network', __('Could not reach the enrollment server. Check your connection or continue offline — your code is not lost.'));
        } catch (\Throwable $e) {
            report($e);

            return $this->fail('network', __('Could not reach the enrollment server. Check your connection or continue offline — your code is not lost.'));
        }

        if ($response->successful()) {
            return $this->parseFlatEnroll($response->json());
        }

        $error = (string) $response->json('error', '');
        $status = $response->status();

        if ($status === 422 && isset($this->errorMap()[$error])) {
            $bucket = $this->errorMap()[$error];

            return $this->fail($bucket, $this->humanMessage($bucket));
        }

        if (in_array($status, [401, 403, 404], true)) {
            return $this->fail('invalid', $this->humanMessage('invalid'));
        }

        return $this->fail('network', __('The enrollment server answered with an unexpected error. Try again or continue offline — your code is not lost.'));
    }

    public function ping(string $deploymentUuid, string $token): array
    {
        $url = $this->baseUrl();
        if ($url === null) {
            return $this->fail('unconfigured', __('Enrollment server is not configured (CONTROL_PLANE_URL).'));
        }

        try {
            $response = Http::timeout(config('controlplane.timeout', 8))
                ->acceptJson()
                ->withToken($token)
                ->post($url.'/api/v1/heartbeats', $this->heartbeatPayload($deploymentUuid));
        } catch (ConnectionException $e) {
            return $this->fail('network', __('Could not reach the enrollment server.'));
        } catch (\Throwable $e) {
            report($e);

            return $this->fail('network', __('Could not reach the enrollment server.'));
        }

        if ($response->successful()) {
            $out = ['status' => 'ok'];
            $snapshot = $response->json('snapshot');
            if (is_array($snapshot) && ($snapshot['deployment_uuid'] ?? null) !== null) {
                $out['snapshot'] = $snapshot;
            }

            return $out;
        }

        return $this->fail('invalid', __('The enrollment server rejected this deployment. Ask ops for a fresh code.'));
    }

    /**
     * Full heartbeat body, built in one place so ping never hand-rolls a
     * second shape.
     */
    public function heartbeatPayload(string $deploymentUuid): array
    {
        return [
            'deployment_uuid' => $deploymentUuid,
            'product' => 'flowedu',
            'app_version' => (string) config('controlplane.app_version', '1.0.0'),
            'counts' => [
                'students' => Student::query()->count(),
                'teachers' => User::query()->where('type', 'teacher')->count(),
                'users' => User::query()->count(),
            ],
            'modules_in_use' => $this->enabledModules(),
        ];
    }

    /**
     * Post a sales lead. Unknown/inactive product slugs answer 404 — logged
     * once, never retried. Anything else unexpected throws for queue retry.
     *
     * @param array<string, mixed> $lead
     * @return array{status: string}
     */
    public function postLead(array $lead): array
    {
        $url = $this->baseUrl();
        if ($url === null) {
            return ['status' => 'skipped'];
        }

        $response = Http::timeout(config('controlplane.timeout', 8))
            ->acceptJson()
            ->post($url.'/api/v1/leads', $lead);

        if ($response->successful()) {
            return ['status' => 'ok'];
        }

        if ($response->status() === 404) {
            return ['status' => 'notFound'];
        }

        throw new \RuntimeException('Lead post failed with HTTP '.$response->status().'.');
    }

    public function humanMessage(string $code): string
    {
        return match ($code) {
            'expired' => __('That code has expired — ask ops for a fresh code.'),
            'consumed' => __('That code was already used (each install needs its own code) — ask ops for a fresh code.'),
            default => __('That code was not recognized — check it and try again, or ask ops for a fresh code.'),
        };
    }

    /**
     * Parse the flat enroll shape into the internal snapshot envelope.
     */
    private function parseFlatEnroll(mixed $json): array
    {
        if (! is_array($json)) {
            return $this->fail('invalid', __('The enrollment server returned an unreadable response. Ask ops for a fresh code.'));
        }

        $uuid = $json['deployment_uuid'] ?? null;
        $licence = $json['licence'] ?? null;

        if (! is_string($uuid) || $uuid === '' || ! is_array($licence)) {
            return $this->fail('invalid', __('The enrollment server returned an unreadable response. Ask ops for a fresh code.'));
        }

        // Consumed-replay: the code already produced a deployment, so no
        // one-time token is issued — but the licence rides along.
        if (! isset($json['heartbeat_token']) || ! is_string($json['heartbeat_token']) || $json['heartbeat_token'] === '') {
            return $this->fail('consumed', $this->humanMessage('consumed'));
        }

        return ['status' => 'ok', 'snapshot' => $this->normalizeSnapshot($uuid, $json['heartbeat_token'], $licence)];
    }

    /**
     * Normalize flat licence {tier, modules[], caps{}, valid_until} into the
     * internal snapshot shape the enrollment service consumes.
     */
    private function normalizeSnapshot(string $uuid, string $token, array $licence): array
    {
        $modules = [];
        foreach ((array) ($licence['modules'] ?? []) as $name) {
            if (is_string($name) && $name !== '') {
                $modules[$name] = true;
            }
        }

        $caps = is_array($licence['caps'] ?? null) ? $licence['caps'] : [];

        return [
            'deployment_uuid' => $uuid,
            'control_plane_token' => $token,
            'licence' => [
                'package_tier' => $licence['tier'] ?? 'complete',
                'max_active_students' => $caps['max_active_students']
                    ?? $caps['max_students']
                    ?? $caps['students']
                    ?? null,
                'starts_at' => $licence['starts_at'] ?? null,
                'expires_at' => $licence['valid_until'] ?? null,
                'core' => [],
                'modules' => $modules,
            ],
        ];
    }

    /**
     * ControlDesk 422 slugs mapped to internal buckets.
     */
    private function errorMap(): array
    {
        return [
            'unknown_code' => 'invalid',
            'code_voided' => 'invalid',
            'code_expired' => 'expired',
            'attempts_exceeded' => 'invalid',
            'deployment_mismatch' => 'invalid',
            'deployment_revoked' => 'invalid',
            'unbound_code' => 'invalid',
        ];
    }

    /**
     * Enabled module keys from the local licence row (heartbeat context).
     *
     * @return list<string>
     */
    private function enabledModules(): array
    {
        try {
            $states = app(SchoolLicenceService::class)->allFeatureStates();
        } catch (\Throwable $e) {
            return [];
        }

        $out = [];
        foreach ($states['modules'] ?? [] as $key => $feat) {
            if (! empty($feat['value'])) {
                $out[] = $key;
            }
        }

        return $out;
    }

    private function baseUrl(): ?string
    {
        $url = trim((string) config('controlplane.url'));

        return $url === '' ? null : rtrim($url, '/');
    }

    private function fail(string $status, string $message): array
    {
        return ['status' => $status, 'message' => $message];
    }
}
