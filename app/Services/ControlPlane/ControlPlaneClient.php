<?php

declare(strict_types=1);

namespace App\Services\ControlPlane;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Minimal ControlDesk HTTP client (enroll + heartbeat ping).
 *
 * All responses are normalized to arrays:
 * ['status' => 'ok', 'snapshot' => [...]]
 * ['status' => 'expired'|'consumed'|'invalid'|'network'|'unconfigured', 'message' => ...]
 * so callers never deal with HTTP shapes directly. Fully fakeable via
 * Http::fake() in tests.
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
            $snapshot = $response->json('snapshot');
            if (is_array($snapshot) && ($snapshot['deployment_uuid'] ?? null) !== null) {
                return ['status' => 'ok', 'snapshot' => $snapshot];
            }

            return $this->fail('invalid', __('The enrollment server returned an unreadable response. Ask ops for a fresh code.'));
        }

        $error = (string) $response->json('error', '');
        $status = $response->status();

        if ($status === 422 && in_array($error, ['expired', 'consumed', 'invalid'], true)) {
            return $this->fail($error, $this->humanMessage($error));
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
                ->post($url.'/api/v1/ping', [
                    'deployment_uuid' => $deploymentUuid,
                    'app_version' => (string) config('controlplane.app_version', '1.0.0'),
                ]);
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

    public function humanMessage(string $code): string
    {
        return match ($code) {
            'expired' => __('That code has expired — ask ops for a fresh code.'),
            'consumed' => __('That code was already used (each install needs its own code) — ask ops for a fresh code.'),
            default => __('That code was not recognized — check it and try again, or ask ops for a fresh code.'),
        };
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
