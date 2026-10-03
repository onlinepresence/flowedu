<?php

namespace Tests\Feature\Demo;

use App\Models\SchoolLicence;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserRole;
use App\Services\DemoKeyVerifier;
use Database\Seeders\AdminSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class DemoFullAccessTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    private function mint(?string $host, ?string $exp, ?string $iat = null, ?string $secret = null, ?string &$public = null): array
    {
        static $s = null;
        static $p = null;
        if ($s === null) {
            $kp = sodium_crypto_sign_keypair();
            $s = sodium_crypto_sign_secretkey($kp);
            $p = sodium_crypto_sign_publickey($kp);
        }
        $secret ??= $s;
        $public = $p;
        $payload = ['expires_at' => $exp, 'host' => $host, 'issued_at' => $iat ?? now()->toDateString()];
        $msg = DemoKeyVerifier::canonicalJson($payload);

        return [
            'payload' => $payload,
            'signature' => bin2hex(sodium_crypto_sign_detached($msg, $secret)),
            'algorithm' => 'ed25519',
        ];
    }

    public function test_single_public_key_verifies_both_encodings(): void
    {
        $doc = $this->mint('localhost', now()->addMonth()->toDateString(), null, null, $public);

        // Hex form, as ControlDesk prints it.
        config(['controlplane.public_key' => bin2hex($public)]);
        $this->assertSame('ok', app(DemoKeyVerifier::class)->check(json_encode($doc), 'localhost')['reason']);

        // Base64 form works too. One key, no second variable anywhere.
        config(['controlplane.public_key' => base64_encode($public)]);
        $this->assertSame('ok', app(DemoKeyVerifier::class)->check(json_encode($doc), 'localhost')['reason']);

        // Empty key = unconfigured, not a silent pass.
        config(['controlplane.public_key' => null]);
        $this->assertSame(
            DemoKeyVerifier::REASON_UNCONFIGURED,
            app(DemoKeyVerifier::class)->check(json_encode($doc), 'localhost')['reason']
        );
    }

    public function test_telemetry_payload(): void
    {
        $doc = $this->mint('localhost', now()->addMonth()->toDateString(), null, null, $public);
        config(['controlplane.public_key' => base64_encode($public)]);
        config(['controlplane.url' => 'https://control.test', 'controlplane.app_version' => '9.9.9']);
        $this->createTestSchool();
        $v = app(DemoKeyVerifier::class);
        $v->recordModuleUse('finance');
        $v->recordModuleUse('reports');
        Http::fake(['*/api/v1/demo-keys/verify' => Http::response($doc, 200)]);
        $check = $v->check('demo-ABC12345', 'localhost');
        $this->assertTrue($check['ok']);
        Http::assertSent(function ($req) {
            if (! str_ends_with($req->url(), '/api/v1/demo-keys/verify')) {
                return false;
            }
            $d = $req->data();

            return ($d['code'] ?? null) === 'demo-ABC12345'
                && ($d['app_version'] ?? null) === '9.9.9'
                && in_array('finance', (array) ($d['modules_in_use'] ?? []), true)
                && in_array('reports', (array) ($d['modules_in_use'] ?? []), true);
        });
    }

    public function test_unlock_all_and_no_caps(): void
    {
        config(['college.demo_mode' => true]);
        $doc = $this->mint('localhost', now()->addMonth()->toDateString(), null, null, $public);
        config(['controlplane.public_key' => base64_encode($public)]);
        $school = $this->createTestSchool();
        SchoolLicence::forceCreate([
            'school_id' => $school->id,
            'module_finance' => false,
            'module_reports' => false,
            'max_active_students' => 1,
            'licence_start' => now()->toDateString(),
            'licence_end' => null,
            'support_until' => now()->addYear()->toDateString(),
            'notes' => null, 'external_ref' => null, 'licence_key' => null,
        ]);
        // No demo yet: locked.
        $this->assertFalse(app(\App\Services\SchoolLicenceService::class)->can('finance'));
        // Cache a valid doc -> full access.
        app(DemoKeyVerifier::class)->cacheDocument($doc);
        // New verifier instance to drop memo (singleton memoized per request but app() returns same; clear via fresh host key).
        $this->assertTrue(app(\App\Services\SchoolLicenceService::class)->can('finance'));
        $this->assertTrue(app(\App\Services\SchoolLicenceService::class)->can('reports'));
        $this->assertNull(app(\App\Services\StudentLicenceCapService::class)->messageIfCannotApproveAnotherStudent());
        $this->assertFalse(app(\App\Services\StudentLicenceCapService::class)->blocksNewAdmissions());
        $this->assertNull(app(\App\Services\StudentLicenceCapService::class)->dashboardCapNotice());
        // Banner status.
        $status = app(DemoKeyVerifier::class)->demoStatus('localhost');
        $this->assertNotNull($status);
        $this->assertSame($doc['payload']['expires_at'], $status['expires_at']);
    }

    public function test_error_mapping(): void
    {
        $this->mint('localhost', now()->addMonth()->toDateString(), null, null, $public);
        config(['controlplane.public_key' => base64_encode($public)]);
        config(['controlplane.url' => 'https://control.test']);
        $this->createTestSchool();
        $v = app(DemoKeyVerifier::class);
        $current = '';
        Http::fake(function () use (&$current) {
            return Http::response(['message' => 'x', 'error' => $current], 422);
        });
        foreach ([
            'unknown_code' => DemoKeyVerifier::REASON_UNKNOWN_CODE,
            'key_revoked' => DemoKeyVerifier::REASON_REVOKED,
            'key_expired' => DemoKeyVerifier::REASON_EXPIRED,
        ] as $slug => $reason) {
            $current = $slug;
            // fresh instance to avoid memo? checkBareCode has no memo.
            $res = $v->check('demo-BADCODE1', 'localhost');
            $this->assertSame($reason, $res['reason'], "slug $slug");
            $this->assertNotEmpty(DemoKeyVerifier::errorCopy($reason));
        }
    }

    public function test_revocation_bites_and_offline_keeps_working(): void
    {
        config(['college.demo_mode' => true]);
        $doc = $this->mint('localhost', now()->addMonth()->toDateString(), null, null, $public);
        config(['controlplane.public_key' => base64_encode($public)]);
        config(['controlplane.url' => 'https://control.test']);
        config(['college.demo_key' => 'demo-GOOD1234']);
        $this->createTestSchool();
        $v = app(DemoKeyVerifier::class);
        $mode = 'ok';
        Http::fake(function () use (&$mode, $doc) {
            if ($mode === 'down') {
                throw new \Illuminate\Http\Client\ConnectionException('down');
            }
            if ($mode === 'revoked') {
                return Http::response(['message' => 'revoked', 'error' => 'key_revoked'], 422);
            }

            return Http::response($doc, 200);
        });
        // Seed cache via online success.
        $this->assertTrue($v->check('demo-GOOD1234', 'localhost')['ok']);
        $this->assertTrue($v->isUnlocked('localhost'));
        // Offline (network down): cached copy keeps working.
        $mode = 'down';
        $this->assertTrue($v->check('demo-ANYOTHER1', 'localhost')['ok']);
        $this->assertTrue($v->isUnlocked('localhost'));
        // Online revocation bites.
        $mode = 'revoked';
        $res2 = $v->check('demo-GOOD1234', 'localhost');
        $this->assertSame(DemoKeyVerifier::REASON_REVOKED, $res2['reason']);
        $this->assertTrue($v->isRevoked());
        $this->assertFalse($v->isUnlocked('localhost'));
    }

    public function test_stored_document_survives_database_refresh_wipe(): void
    {
        // Monthly demo:refresh runs migrate:fresh: every settings row (cached
        // document, last-check stamps, telemetry) is wiped, but the .env
        // DEMO_KEY file persists. A stored document must keep verifying
        // offline with zero network.
        config(['college.demo_mode' => true]);
        $doc = $this->mint('localhost', now()->addMonth()->toDateString(), null, null, $public);
        config(['controlplane.public_key' => base64_encode($public)]);
        config(['controlplane.url' => 'https://control.test']);
        config(['college.demo_key' => DemoKeyVerifier::encodeForEnv((string) json_encode($doc))]);
        $this->createTestSchool();

        app(DemoKeyVerifier::class)->cacheDocument($doc);
        $this->assertTrue(app(DemoKeyVerifier::class)->isUnlocked('localhost'));

        // Simulate the refresh wipe: all settings rows gone.
        Setting::query()->delete();

        $fresh = app(DemoKeyVerifier::class);
        $this->assertTrue($fresh->isUnlocked('localhost'));
        $status = $fresh->demoStatus();
        $this->assertNotNull($status);
        $this->assertSame($doc['payload']['expires_at'], $status['expires_at']);
        $this->assertTrue(app(\App\Services\SchoolLicenceService::class)->can('finance'));
    }

    public function test_bare_code_env_reheals_cache_after_refresh_wipe(): void
    {
        // Bare demo-XXXXXXXX in .env with a wiped cache: the next request's
        // boot re-verify re-fetches the document and re-caches it.
        config(['college.demo_mode' => true]);
        $doc = $this->mint('localhost', now()->addMonth()->toDateString(), null, null, $public);
        config(['controlplane.public_key' => base64_encode($public)]);
        config(['controlplane.url' => 'https://control.test']);
        config(['college.demo_key' => 'demo-REHEAL01']);
        $this->createTestSchool();

        Http::fake(['*/api/v1/demo-keys/verify' => Http::response($doc, 200)]);

        $this->assertNull(
            Setting::query()->where('setting_key', DemoKeyVerifier::CACHE_KEY)->value('setting_value')
        );

        $fresh = app(DemoKeyVerifier::class);
        $result = $fresh->reverifyIfDue('localhost');
        $this->assertTrue(($result['ok'] ?? false) === true);
        $this->assertNotNull(
            Setting::query()->where('setting_key', DemoKeyVerifier::CACHE_KEY)->value('setting_value')
        );
        $this->assertTrue($fresh->isUnlocked('localhost'));
    }

    public function test_licence_settings_page_shows_demo_full_access(): void
    {
        $this->seed(AdminSystemSeeder::class);
        UserRole::ensureSystemRoles();

        config(['college.demo_mode' => true]);
        $doc = $this->mint('localhost', now()->addMonth()->toDateString(), null, null, $public);
        config(['controlplane.public_key' => base64_encode($public)]);
        $school = $this->createTestSchool();

        SchoolLicence::forceCreate([
            'school_id' => $school->id,
            'module_finance' => false,
            'max_active_students' => null,
            'licence_start' => now()->toDateString(),
            'licence_end' => null,
            'support_until' => now()->addYear()->toDateString(),
            'notes' => null,
            'external_ref' => null,
            'licence_key' => null,
        ]);

        $user = User::factory()->create(['type' => 'admin', 'username' => 'demoowner']);
        $admin = new \App\Models\Admin;
        $admin->user_id = $user->id;
        $admin->type = UserRole::query()->where('name', 'owner')->value('id');
        $admin->save();

        app(DemoKeyVerifier::class)->cacheDocument($doc);

        // Unlinked install, but the demo is unlocked: no "not linked" and no
        // "licence not active" states; every module lists as available.
        Livewire::actingAs($user)
            ->test(\App\Livewire\Admin\Settings\LicenceSettingsPage::class)
            ->assertSee('Demo trial — full access')
            ->assertDontSee('not linked to ControlDesk')
            ->assertDontSee('licence is not active')
            ->assertDontSee('Modular extensions unavailable')
            ->assertSee('Financial Portal');
    }
}
