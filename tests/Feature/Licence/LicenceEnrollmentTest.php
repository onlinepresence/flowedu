<?php

declare(strict_types=1);

namespace Tests\Feature\Licence;

use App\Livewire\Admin\Setup\SetupLicenceForm;
use App\Models\Admin;
use App\Models\School;
use App\Models\SchoolLicence;
use App\Models\User;
use App\Models\UserRole;
use App\Services\ControlPlane\LicenceEnrollmentService;
use Database\Seeders\AdminSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class LicenceEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private string $envFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AdminSystemSeeder::class);
        UserRole::ensureSystemRoles();

        // Never touch the real .env in tests.
        $this->envFile = tempnam(sys_get_temp_dir(), 'envtest');
        file_put_contents($this->envFile, "APP_NAME=Test\n");
        config(['controlplane.env_path' => $this->envFile]);
        config(['controlplane.url' => 'https://control.test']);

        session(['admin_register' => true]);
    }

    protected function tearDown(): void
    {
        @unlink($this->envFile);
        putenv('DEPLOYMENT_UUID');

        parent::tearDown();
    }

    private function setupSchool(): School
    {
        return School::query()->create([
            'name' => 'Enroll College',
            'address' => '1 Campus Road',
            'ready' => false,
            'is_admit' => true,
        ]);
    }

    private function owner(): User
    {
        $user = User::factory()->create(['type' => 'admin', 'username' => 'enrollowner']);
        $admin = new Admin;
        $admin->user_id = $user->id;
        $admin->type = UserRole::query()->where('name', 'owner')->value('id');
        $admin->save();

        return $user;
    }

    /**
     * Flat ControlDesk enroll shape (authoritative contract): no snapshot
     * envelope. starts_at is absent, so licence_start defaults to today.
     */
    private function snapshot(array $overrides = []): array
    {
        return array_merge([
            'deployment_uuid' => 'dep-uuid-1234',
            'heartbeat_token' => 'tok-secret-5678',
            'licence' => [
                'tier' => 'complete',
                'modules' => ['finance'],
                'caps' => ['max_active_students' => 350],
                'valid_until' => '2027-09-18',
            ],
        ], $overrides);
    }

    public function test_redeem_success_seeds_row_maps_dates_and_writes_env(): void
    {
        $school = $this->setupSchool();
        $user = $this->owner();

        Http::fake([
            '*/api/v1/enroll' => Http::response($this->snapshot(), 200),
        ]);

        Livewire::actingAs($user)
            ->test(SetupLicenceForm::class)
            ->set('enrollCode', 'APEX-2026-ABCD')
            ->call('redeemCode')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.setup.faculties', absolute: false));

        $row = SchoolLicence::query()->where('school_id', $school->id)->firstOrFail();
        $this->assertSame('dep-uuid-1234', $row->external_ref);
        $this->assertFalse((bool) $row->provisional);
        $this->assertSame(now()->toDateString(), $row->licence_start->format('Y-m-d'));
        $this->assertSame('2027-09-18', $row->support_until->format('Y-m-d'));
        $this->assertSame('2027-09-18', $row->licence_end->format('Y-m-d'));
        $this->assertSame(350, $row->max_active_students);
        $this->assertTrue((bool) $row->module_finance);
        $this->assertFalse((bool) $row->module_reports);

        $env = (string) file_get_contents($this->envFile);
        $this->assertStringContainsString('DEPLOYMENT_UUID=dep-uuid-1234', $env);
        $this->assertStringContainsString('CONTROL_PLANE_TOKEN=tok-secret-5678', $env);
    }

    public function test_redeem_failures_speak_human_and_seed_nothing(): void
    {
        $school = $this->setupSchool();
        $user = $this->owner();

        foreach (['unknown_code', 'code_voided', 'code_expired', 'attempts_exceeded', 'deployment_mismatch', 'deployment_revoked', 'unbound_code'] as $error) {
            Http::fake(['*/api/v1/enroll' => Http::response(['message' => 'nope', 'error' => $error], 422)]);

            $component = Livewire::actingAs($user)
                ->test(SetupLicenceForm::class)
                ->set('enrollCode', 'USED-CODE')
                ->call('redeemCode');

            $component->assertHasNoErrors();
            $this->assertStringContainsString('ask ops for a fresh code', $component->get('enrollError'));
        }

        // Consumed-replay: 200 with a licence but no one-time token.
        $replay = $this->snapshot();
        $replay['heartbeat_token'] = null;
        Http::fake(['*/api/v1/enroll' => Http::response($replay, 200)]);

        $replayed = Livewire::actingAs($user)
            ->test(SetupLicenceForm::class)
            ->set('enrollCode', 'USED-CODE')
            ->call('redeemCode');

        $replayed->assertHasNoErrors();
        $this->assertStringContainsString('already used', $replayed->get('enrollError'));

        Http::fake(['*/api/v1/enroll' => Http::response([], 500)]);
        Livewire::actingAs($user)
            ->test(SetupLicenceForm::class)
            ->set('enrollCode', 'ANY-CODE')
            ->call('redeemCode')
            ->assertHasNoErrors();

        $this->assertNull(SchoolLicence::query()->where('school_id', $school->id)->value('external_ref'));
    }

    public function test_continue_offline_flags_provisional_core_only_and_parks_code(): void
    {
        $school = $this->setupSchool();
        $user = $this->owner();

        Livewire::actingAs($user)
            ->test(SetupLicenceForm::class)
            ->set('offlineCode', 'APEX-2026-LATER')
            ->call('continueOffline')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.setup.faculties', absolute: false));

        $row = SchoolLicence::query()->where('school_id', $school->id)->firstOrFail();
        $this->assertTrue((bool) $row->provisional);
        $this->assertNull($row->external_ref);
        $this->assertTrue((bool) $row->core_timetable);
        $this->assertFalse((bool) $row->module_finance);
        $this->assertFalse((bool) $row->module_reports);

        $this->assertSame(
            'APEX-2026-LATER',
            \App\Models\Setting::query()->where('setting_key', LicenceEnrollmentService::PENDING_CODE_KEY)->value('setting_value')
        );

        // Daily retry redeems silently when the network appears.
        Http::fake(['*/api/v1/enroll' => Http::response($this->snapshot(), 200)]);
        $this->assertTrue(app(LicenceEnrollmentService::class)->retryPending());

        $row->refresh();
        $this->assertFalse((bool) $row->provisional);
        $this->assertSame('dep-uuid-1234', $row->external_ref);
        $this->assertNull(
            \App\Models\Setting::query()->where('setting_key', LicenceEnrollmentService::PENDING_CODE_KEY)->value('setting_value')
        );

        // No pending code = quiet no-op without touching the network.
        Http::fake(['*/api/v1/enroll' => Http::response([], 500)]);
        $this->assertTrue(app(LicenceEnrollmentService::class)->retryPending());
    }

    public function test_import_verifies_signature_and_expiry(): void
    {
        $school = $this->setupSchool();
        $user = $this->owner();

        $keypair = sodium_crypto_sign_keypair();
        $secret = sodium_crypto_sign_secretkey($keypair);
        $public = sodium_crypto_sign_publickey($keypair);
        config(['controlplane.public_key' => base64_encode($public)]);

        $verifier = app(\App\Services\ControlPlane\LicenceFileVerifier::class);
        $payload = [
            'deployment_uuid' => 'dep-file-9999',
            'control_plane_token' => 'tok-file-0000',
            'licence' => [
                'package_tier' => 'professional',
                'max_active_students' => 200,
                'starts_at' => now()->subDay()->toDateString(),
                'expires_at' => now()->addYear()->toDateString(),
                'core' => [],
                'modules' => ['finance' => true],
            ],
        ];
        $blob = json_encode([
            'payload' => $payload,
            'signature' => base64_encode(sodium_crypto_sign_detached($verifier->canonicalJson($payload), $secret)),
        ]);

        Livewire::actingAs($user)
            ->test(SetupLicenceForm::class)
            ->set('importBlob', $blob)
            ->call('importLicence')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.setup.faculties', absolute: false));

        $row = SchoolLicence::query()->where('school_id', $school->id)->firstOrFail();
        $this->assertSame('dep-file-9999', $row->external_ref);
        $this->assertFalse((bool) $row->provisional);

        // Tampered payload fails.
        $bad = json_decode($blob, true);
        $bad['payload']['licence']['max_active_students'] = 99999;
        $tampered = Livewire::actingAs($user)
            ->test(SetupLicenceForm::class)
            ->set('importBlob', json_encode($bad))
            ->call('importLicence')
            ->assertHasNoErrors();
        $this->assertStringContainsString('Bad signature', (string) $tampered->get('enrollError'));

        // Expired file fails even with a good signature.
        $payload['licence']['expires_at'] = now()->subDay()->toDateString();
        $expired = json_encode([
            'payload' => $payload,
            'signature' => base64_encode(sodium_crypto_sign_detached($verifier->canonicalJson($payload), $secret)),
        ]);
        $expiredCall = Livewire::actingAs($user)
            ->test(SetupLicenceForm::class)
            ->set('importBlob', $expired)
            ->call('importLicence')
            ->assertHasNoErrors();
        $this->assertStringContainsString('expired', (string) $expiredCall->get('enrollError'));
    }

    public function test_linked_install_renders_read_only_and_blocks_save(): void
    {
        $school = $this->setupSchool();
        $user = $this->owner();

        SchoolLicence::query()->updateOrCreate(['school_id' => $school->id], [
            'external_ref' => 'dep-linked-1',
            'provisional' => false,
            'max_active_students' => 100,
            'module_finance' => true,
        ]);
        putenv('DEPLOYMENT_UUID=dep-linked-1');

        // Old form design restored: full catalog with server values,
        // every input frozen (disabled).
        Livewire::actingAs($user)
            ->test(\App\Livewire\Admin\Settings\LicenceSettingsPage::class)
            ->assertSee('Managed by ControlDesk')
            ->assertDontSee('Save licensing')
            ->assertSee('Core Academic System')
            ->assertSee('Modular Extensions')
            ->assertSee('Financial Portal')
            ->assertSee('disabled');

        Livewire::actingAs($user)
            ->test(\App\Livewire\Admin\Settings\LicenceSettingsPage::class)
            ->call('save')
            ->assertHasErrors(['form']);

        Livewire::actingAs($user)
            ->test(SetupLicenceForm::class)
            ->assertSee('Managed by ControlDesk')
            ->assertSee('Core Academic System')
            ->assertSee('Financial Portal')
            ->assertSee('disabled')
            ->assertDontSee('Continue to faculties');
    }

    public function test_env_write_failure_shows_manual_lines(): void
    {
        $this->setupSchool();
        $user = $this->owner();
        config(['controlplane.env_path' => sys_get_temp_dir().'/no-such-dir-'.uniqid().'/missing.env']);

        Http::fake(['*/api/v1/enroll' => Http::response($this->snapshot(), 200)]);

        $component = Livewire::actingAs($user)
            ->test(SetupLicenceForm::class)
            ->set('enrollCode', 'APEX-2026-ABCD')
            ->call('redeemCode')
            ->assertHasNoErrors();

        // No redirect: manual paste panel stays on screen with exact lines.
        $this->assertSame(
            ['DEPLOYMENT_UUID=dep-uuid-1234', 'CONTROL_PLANE_TOKEN=tok-secret-5678'],
            $component->get('manualLines')
        );
        $component->assertSee('DEPLOYMENT_UUID=dep-uuid-1234');
    }

    public function test_preview_matches_live_core_pricing_fixture(): void
    {
        $this->setupSchool();
        $user = $this->owner();

        // Hand-computed from config/licence.php: 200 students -> 1-500 band
        // (x1.0), core renewal 1200.00 / upfront 4500.00, no modules/founding.
        $component = Livewire::actingAs($user)->test(SetupLicenceForm::class);
        $component->set('max_active_students', '200');
        // Testing env defaults every module on; fixture wants none on.
        $component->set('moduleStates', array_fill_keys(array_keys(config('licence.modules', [])), false));
        $preview = $component->instance()->getPricingPreview(app(\App\Services\SchoolLicenceService::class));

        $this->assertSame('1 – 500 students', $preview['band_label']);
        $this->assertEquals(1.0, $preview['multiplier']);
        $this->assertEquals(1200.00, $preview['core_annual']);
        $this->assertEquals(4500.00, $preview['core_setup']);
        $this->assertEquals(0.0, $preview['modules_annual']);
        $this->assertEquals(0.0, $preview['discount']);
        $this->assertEquals(1200.00, $preview['total_annual']);
        $this->assertEquals(4500.00, $preview['total_setup']);
        $this->assertEquals(5700.00, $preview['grand_total']);

        // All ten modules on: 12% bundle discount applies to module renewal.
        $modules = array_keys(config('licence.modules', []));
        $this->assertCount(10, $modules);
        $renewSum = 0.0;
        foreach ($modules as $key) {
            $renewSum += (float) config("licence.modules.{$key}.renewal_base", 0.0);
        }
        $component->set('moduleStates', array_fill_keys($modules, true));
        $preview = $component->instance()->getPricingPreview(app(\App\Services\SchoolLicenceService::class));

        // Service returns post-discount module sums (12% bundle at 10 modules).
        $this->assertEqualsWithDelta($renewSum * 0.88, $preview['modules_annual'], 0.01);
        $this->assertEqualsWithDelta($renewSum * 0.12, $preview['discount'], 0.01);
        $this->assertEqualsWithDelta(1200.00 + $renewSum * 0.88, $preview['total_annual'], 0.01);
    }

    public function test_ping_redeem_option_works_without_wizard(): void
    {
        $this->setupSchool();

        Http::fake(['*/api/v1/enroll' => Http::response($this->snapshot(), 200)]);

        $this->artisan('controlplane:ping', ['--redeem' => 'APEX-2026-ABCD'])
            ->assertSuccessful();

        $this->assertSame(
            'dep-uuid-1234',
            SchoolLicence::query()->value('external_ref')
        );
    }
}
