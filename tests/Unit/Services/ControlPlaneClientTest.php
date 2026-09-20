<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ControlPlane\ControlPlaneClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ControlDesk contract tests (authoritative flat shape).
 */
class ControlPlaneClientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['controlplane.url' => 'https://control.test']);
    }

    private function flat(array $overrides = []): array
    {
        return array_merge([
            'deployment_uuid' => 'dep-flat-1',
            'heartbeat_token' => 'tok-flat-2',
            'licence' => [
                'tier' => 'professional',
                'modules' => ['finance', 'reports'],
                'caps' => ['max_active_students' => 250],
                'valid_until' => '2027-06-30',
            ],
        ], $overrides);
    }

    public function test_enroll_parses_flat_happy_path(): void
    {
        Http::fake(['*/api/v1/enroll' => Http::response($this->flat(), 200)]);

        $result = app(ControlPlaneClient::class)->enroll('APEX-1');

        $this->assertSame('ok', $result['status']);
        $this->assertSame('dep-flat-1', $result['snapshot']['deployment_uuid']);
        $this->assertSame('tok-flat-2', $result['snapshot']['control_plane_token']);
        $this->assertSame('professional', $result['snapshot']['licence']['package_tier']);
        $this->assertSame(250, $result['snapshot']['licence']['max_active_students']);
        $this->assertSame('2027-06-30', $result['snapshot']['licence']['expires_at']);
        $this->assertSame(['finance' => true, 'reports' => true], $result['snapshot']['licence']['modules']);
    }

    public function test_enroll_maps_each_error_slug(): void
    {
        $cases = [
            'unknown_code' => 'invalid',
            'code_voided' => 'invalid',
            'code_expired' => 'expired',
            'attempts_exceeded' => 'invalid',
            'deployment_mismatch' => 'invalid',
            'deployment_revoked' => 'invalid',
            'unbound_code' => 'invalid',
        ];

        // NOTE: Http::fake([...]) merges stubs with first-match priority, so
        // re-registering per iteration would poison later slugs with the
        // first stub. One fake with a by-reference slug instead.
        $current = '';
        Http::fake(function () use (&$current) {
            return Http::response(['message' => 'x', 'error' => $current], 422);
        });

        foreach ($cases as $slug => $bucket) {
            $current = $slug;

            $result = app(ControlPlaneClient::class)->enroll('APEX-1');

            $this->assertSame($bucket, $result['status'], "slug {$slug}");
            $this->assertStringContainsString('ask ops for a fresh code', $result['message'], "slug {$slug}");
        }
    }

    public function test_enroll_consumed_replay_without_token(): void
    {
        $flat = $this->flat();
        $flat['heartbeat_token'] = null;
        Http::fake(['*/api/v1/enroll' => Http::response($flat, 200)]);

        $result = app(ControlPlaneClient::class)->enroll('APEX-1');

        $this->assertSame('consumed', $result['status']);
        $this->assertStringContainsString('already used', $result['message']);
    }

    public function test_ping_uses_heartbeats_endpoint_with_full_payload(): void
    {
        Http::fake(['*/api/v1/heartbeats' => Http::response(['ok' => true], 200)]);

        $result = app(ControlPlaneClient::class)->ping('dep-flat-1', 'tok-flat-2');

        $this->assertSame('ok', $result['status']);

        Http::assertSent(function ($request): bool {
            if (! str_ends_with($request->url(), '/api/v1/heartbeats')) {
                return false;
            }

            $payload = $request->data();

            return ($payload['deployment_uuid'] ?? null) === 'dep-flat-1'
                && ($payload['product'] ?? null) === 'flowedu'
                && isset($payload['app_version'], $payload['counts']['students'], $payload['counts']['teachers'], $payload['counts']['users'], $payload['modules_in_use']);
        });

        Http::assertNotSent(fn ($request): bool => str_ends_with($request->url(), '/api/v1/ping'));
    }

    public function test_ping_404_is_invalid(): void
    {
        Http::fake(['*/api/v1/heartbeats' => Http::response([], 404)]);

        $result = app(ControlPlaneClient::class)->ping('dep-flat-1', 'tok-flat-2');

        $this->assertSame('invalid', $result['status']);

        Http::assertSent(function ($request): bool {
            return str_ends_with($request->url(), '/api/v1/heartbeats');
        });
    }
}
