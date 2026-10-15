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
use Illuminate\Support\Facades\DB;
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

    /**
     * Real ControlDesk heartbeat shape (db_column keys in `modules`,
     * explicit `core` map, tier=standard, ISO8601 starts_at) must seed
     * modules, core grants, caps, and start dates — not defaults.
     */
    public function test_redeem_accepts_control_desk_heartbeat_shape(): void
    {
        $school = $this->setupSchool();
        $user = $this->owner();
        $starts = now()->subMonth()->toDateString();

        Http::fake([
            '*/api/v1/enroll' => Http::response([
                'deployment_uuid' => 'dep-desk-2222',
                'heartbeat_token' => 'tok-desk-9999',
                'licence' => [
                    'tier' => 'standard',
                    'modules' => ['academic_structure', 'students', 'core_attendance', 'module_finance', 'module_messaging'],
                    'core' => ['attendance' => true, 'timetable' => false],
                    'caps' => ['max_active_students' => 600],
                    'starts_at' => now()->subMonth()->toIso8601String(),
                    'valid_until' => '2027-09-18',
                ],
            ], 200),
        ]);

        Livewire::actingAs($user)
            ->test(SetupLicenceForm::class)
            ->set('enrollCode', 'APEX-2026-DESK')
            ->call('redeemCode')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.setup.faculties', absolute: false));

        $row = SchoolLicence::query()->where('school_id', $school->id)->firstOrFail();
        $this->assertSame('dep-desk-2222', $row->external_ref);
        $this->assertSame(600, $row->max_active_students);
        $this->assertSame($starts, $row->licence_start->format('Y-m-d'));
        $this->assertTrue((bool) $row->core_attendance);
        $this->assertFalse((bool) $row->core_timetable);
        $this->assertTrue((bool) $row->module_finance);
        $this->assertTrue((bool) $row->module_messaging);
        $this->assertFalse((bool) $row->module_reports);
    }

    public function test_redeem_failures_speak_human_and_seed_nothing(): void
    {
        $school = $this->setupSchool();
        $user = $this->owner();

        // NOTE: Http::fake([...]) merges stubs with first-match priority, so
        // one by-reference fake serves the whole test (see ControlPlaneClientTest).
        $body = ['message' => 'nope', 'error' => 'unknown_code'];
        $status = 422;
        Http::fake(function () use (&$body, &$status) {
            return Http::response($body, $status);
        });

        foreach (['unknown_code', 'code_voided', 'code_expired', 'attempts_exceeded', 'deployment_mismatch', 'deployment_revoked', 'unbound_code'] as $error) {
            $body = ['message' => 'nope', 'error' => $error];
            $status = 422;

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
        $body = $replay;
        $status = 200;

        $replayed = Livewire::actingAs($user)
            ->test(SetupLicenceForm::class)
            ->set('enrollCode', 'USED-CODE')
            ->call('redeemCode');

        $replayed->assertHasNoErrors();
        $this->assertStringContainsString('already used', $replayed->get('enrollError'));

        $body = [];
        $status = 500;
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
            'algorithm' => 'ed25519',
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
            'algorithm' => 'ed25519',
        ]);
        $expiredCall = Livewire::actingAs($user)
            ->test(SetupLicenceForm::class)
            ->set('importBlob', $expired)
            ->call('importLicence')
            ->assertHasNoErrors();
        $this->assertStringContainsString('expired', (string) $expiredCall->get('enrollError'));
    }

    /**
     * Real ControlDesk air-gap export shape (flat payload with `enabled`
     * list, hex signature, ISO8601 dates) must import and seed modules,
     * caps, and dates — including the messaging module column.
     */
    public function test_import_accepts_flat_control_desk_export_shape(): void
    {
        $school = $this->setupSchool();
        $user = $this->owner();

        $keypair = sodium_crypto_sign_keypair();
        $secret = sodium_crypto_sign_secretkey($keypair);
        $public = sodium_crypto_sign_publickey($keypair);
        config(['controlplane.public_key' => bin2hex($public)]);

        $verifier = app(\App\Services\ControlPlane\LicenceFileVerifier::class);
        $payload = [
            'product' => 'flowedu',
            'deployment_uuid' => 'dep-flat-1111',
            'tier' => 'standard',
            'enabled' => ['academic_structure', 'students', 'grading', 'teacher_portal', 'student_portal', 'core_timetable', 'module_finance', 'module_messaging'],
            'caps' => ['max_active_students' => 420],
            'starts_at' => now()->subDay()->toIso8601String(),
            'expires_at' => now()->addYear()->toIso8601String(),
            'issued_at' => now()->toIso8601String(),
            'version' => 1,
            'kid' => '2026-a',
        ];
        $blob = json_encode([
            'payload' => $payload,
            'signature' => bin2hex(sodium_crypto_sign_detached($verifier->canonicalJson($payload), $secret)),
            'algorithm' => 'ed25519',
        ]);

        Livewire::actingAs($user)
            ->test(SetupLicenceForm::class)
            ->set('importBlob', $blob)
            ->call('importLicence')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.setup.faculties', absolute: false));

        $row = SchoolLicence::query()->where('school_id', $school->id)->firstOrFail();
        $this->assertSame('dep-flat-1111', $row->external_ref);
        $this->assertFalse((bool) $row->provisional);
        $this->assertSame(420, $row->max_active_students);
        $this->assertTrue((bool) $row->core_timetable);
        $this->assertTrue((bool) $row->module_finance);
        $this->assertTrue((bool) $row->module_messaging);
        $this->assertFalse((bool) $row->module_reports);
        $this->assertSame(now()->addYear()->toDateString(), $row->support_until->format('Y-m-d'));
    }

    /**
     * A demo document pasted at the import exit unlocks a trial WITHOUT
     * seeding any licence row or deployment link — the old "no deployment
     * identity" dead-end is gone.
     */
    public function test_import_accepts_demo_document_as_trial(): void
    {
        $school = $this->setupSchool();
        $user = $this->owner();

        $key = $this->mintDemoKey('localhost', now()->addMonth()->toDateString());

        Livewire::actingAs($user)
            ->test(SetupLicenceForm::class)
            ->set('importBlob', $key)
            ->call('importLicence')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.setup.faculties', absolute: false));

        // Trial, not enrollment: the row stays unlinked.
        $this->assertNull(SchoolLicence::query()->where('school_id', $school->id)->value('external_ref'));

        // And the demo actually unlocks through the normal licence service.
        $this->assertTrue(app(\App\Services\SchoolLicenceService::class)->can('finance'));
        $this->assertTrue((bool) session('demo_key_accepted', false));
    }

    /**
     * Tampered and expired demo documents fail at the import exit with demo
     * copy — and seed nothing.
     */
    public function test_import_rejects_bad_demo_documents(): void
    {
        $school = $this->setupSchool();
        $user = $this->owner();

        $tampered = json_decode($this->mintDemoKey('localhost', now()->addMonth()->toDateString()), true);
        $tampered['payload']['host'] = 'evil.example.com';
        $bad = Livewire::actingAs($user)
            ->test(SetupLicenceForm::class)
            ->set('importBlob', json_encode($tampered))
            ->call('importLicence')
            ->assertHasNoErrors();
        $this->assertStringContainsString('did not look right', (string) $bad->get('enrollError'));

        $expired = Livewire::actingAs($user)
            ->test(SetupLicenceForm::class)
            ->set('importBlob', $this->mintDemoKey('localhost', now()->subDay()->toDateString()))
            ->call('importLicence')
            ->assertHasNoErrors();
        $this->assertStringContainsString('expired', (string) $expired->get('enrollError'));

        $this->assertNull(SchoolLicence::query()->where('school_id', $school->id)->value('external_ref'));
        $this->assertFalse((bool) session('demo_key_accepted', false));
    }

    /**
     * Universal activate() routes every input type: claim codes redeem,
     * demo documents and bare demo codes unlock trials, licence files seed.
     */
    public function test_activate_routes_every_input_type(): void
    {
        $school = $this->setupSchool();
        $keeper = $this->owner();
        $service = app(LicenceEnrollmentService::class);

        // NOTE: Http::fake() merges stubs with first-match priority, so one
        // routing fake serves the whole test (see ControlPlaneClientTest).
        // One keypair serves both demo steps (mintDemoKey configures it).
        $docJson = $this->mintDemoKey('localhost', now()->addMonth()->toDateString());
        $onlineDoc = json_decode($docJson, true);
        $body = $this->snapshot();
        $status = 200;
        Http::fake(function ($request) use (&$body, &$status, $onlineDoc) {
            if (str_ends_with($request->url(), '/api/v1/demo-keys/verify')) {
                return Http::response($onlineDoc, 200);
            }

            return Http::response($body, $status);
        });

        // Claim code -> redeem -> row seeded.
        $redeemed = $service->activate('APEX-2026-ABCD', false, $school, $keeper->id);
        $this->assertTrue((bool) ($redeemed['ok'] ?? false));
        $this->assertArrayNotHasKey('demo', $redeemed);
        $this->assertSame('dep-uuid-1234', SchoolLicence::query()->where('school_id', $school->id)->value('external_ref'));

        // Demo document -> trial, row untouched.
        $trial = $service->activate($docJson, false, $school, $keeper->id);
        $this->assertTrue((bool) ($trial['ok'] ?? false));
        $this->assertTrue((bool) ($trial['demo'] ?? false));
        $this->assertSame('dep-uuid-1234', SchoolLicence::query()->where('school_id', $school->id)->value('external_ref'));

        // Bare demo code -> online Desk verify -> trial.
        $bare = $service->activate('demo-QWERTY123', false, $school, $keeper->id);
        $this->assertTrue((bool) ($bare['ok'] ?? false));
        $this->assertTrue((bool) ($bare['demo'] ?? false));

        // Garbage -> redeem failure, nothing seeded.
        $body = ['message' => 'nope', 'error' => 'unknown_code'];
        $status = 422;
        $bad = $service->activate('NOT-A-CODE', false, $school, $keeper->id);
        $this->assertFalse((bool) ($bad['ok'] ?? false));
        $this->assertNotEmpty((string) ($bad['message'] ?? ''));
    }

    /**
     * Fresh-start activation clears business records but keeps the keeper
     * login, the school, the just-seeded licence row, and settings.
     */
    public function test_activate_fresh_clears_but_keeps_owner_school_licence(): void
    {
        $school = $this->setupSchool();
        $keeper = $this->owner();
        $this->seedBusinessData($school, $keeper);

        Http::fake(['*/api/v1/enroll' => Http::response($this->snapshot(), 200)]);

        $result = app(LicenceEnrollmentService::class)->activate('APEX-2026-ABCD', true, $school, $keeper->id);

        $this->assertTrue((bool) ($result['ok'] ?? false));
        $this->assertTrue((bool) ($result['refreshed'] ?? false));
        $this->assertGreaterThan(0, (int) ($result['cleared_total'] ?? 0));

        // Keeper survives; everyone else is gone.
        $this->assertSame([$keeper->id], User::query()->pluck('id')->all());
        $this->assertSame(1, Admin::query()->where('user_id', $keeper->id)->count());

        // Business tables cleared, identity/config kept.
        $this->assertSame(0, DB::table('students')->count());
        $this->assertSame(0, DB::table('memos')->count());
        $this->assertSame(0, DB::table('halls')->count());
        $this->assertSame(1, DB::table('schools')->where('id', $school->id)->count());
        $this->assertSame('dep-uuid-1234', SchoolLicence::query()->where('school_id', $school->id)->value('external_ref'));
        $this->assertSame('1', (string) DB::table('settings')->where('setting_key', 'keep.me')->value('setting_value'));
    }

    /**
     * Without the switch, activation leaves existing records alone.
     */
    public function test_activate_without_fresh_preserves_data(): void
    {
        $school = $this->setupSchool();
        $keeper = $this->owner();
        $this->seedBusinessData($school, $keeper);

        Http::fake(['*/api/v1/enroll' => Http::response($this->snapshot(), 200)]);

        $result = app(LicenceEnrollmentService::class)->activate('APEX-2026-ABCD', false, $school, $keeper->id);

        $this->assertTrue((bool) ($result['ok'] ?? false));
        $this->assertArrayNotHasKey('refreshed', $result);
        $this->assertSame(3, DB::table('users')->count());
        $this->assertSame(1, DB::table('students')->count());
        $this->assertSame(1, DB::table('memos')->count());
        $this->assertSame('dep-uuid-1234', SchoolLicence::query()->where('school_id', $school->id)->value('external_ref'));
    }

    /**
     * Settings single-field activation: demo document plus fresh switch in
     * one step, banners refresh in place.
     */
    public function test_settings_activate_accepts_demo_with_fresh_switch(): void
    {
        $school = $this->setupSchool();
        $keeper = $this->owner();
        $this->seedBusinessData($school, $keeper);
        SchoolLicence::query()->updateOrCreate(['school_id' => $school->id], [
            'provisional' => true,
            'external_ref' => null,
        ]);

        $doc = $this->mintDemoKey('localhost', now()->addMonth()->toDateString());

        $component = Livewire::actingAs($keeper)
            ->test(\App\Livewire\Admin\Settings\LicenceSettingsPage::class)
            ->set('activationInput', $doc)
            ->set('freshStart', true)
            ->call('activate')
            ->assertHasNoErrors();

        $this->assertStringContainsString('fresh slate', (string) $component->get('notice'));
        $this->assertSame([$keeper->id], User::query()->pluck('id')->all());
        $this->assertSame(0, DB::table('students')->count());
        $this->assertNull(SchoolLicence::query()->where('school_id', $school->id)->value('external_ref'));
        $this->assertTrue((bool) $component->get('isDemo'));
    }

    /**
     * Seed representative business data: an extra admin, a student, a hall,
     * a memo, and a keeper setting.
     */
    private function seedBusinessData(School $school, User $keeper): void
    {
        $extra = User::factory()->create(['type' => 'admin', 'username' => 'extraadmin']);
        $admin = new Admin;
        $admin->user_id = $extra->id;
        $admin->type = UserRole::query()->where('name', 'owner')->value('id');
        $admin->save();

        $hallId = DB::table('halls')->insertGetId([
            'name' => 'Test Hall',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $studentUser = User::factory()->create(['type' => 'student', 'username' => 'studentone']);
        DB::table('students')->insert([
            'user_id' => $studentUser->id,
            'index_number' => 'IDX-1',
            'admission_index' => 'ADM-1',
            'lastname' => 'Test',
            'date_of_birth' => '2010-01-01',
            'gender' => 'male',
            'nationality' => 'Ghanaian',
            'contact_address' => 'Addr',
            'phone_number' => '123',
            'hall_id' => $hallId,
            'profile_pic' => 'x.png',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('memos')->insert([
            'title' => 'T',
            'content' => 'C',
            'sender_id' => $keeper->id,
            'recipient_type' => 'user',
            'recipient_entity_id' => $keeper->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('settings')->insert([
            'setting_key' => 'keep.me',
            'setting_value' => '1',
            'data_type' => 'string',
            'category' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_linked_live_install_allows_plan_toggles_and_guards_save(): void
    {
        $school = $this->setupSchool();
        $user = $this->owner();

        SchoolLicence::query()->updateOrCreate(['school_id' => $school->id], [
            'external_ref' => 'dep-linked-1',
            'provisional' => false,
            'max_active_students' => 100,
            'module_finance' => true,
        ]);
        // NOTE: config, not putenv — putenv() cannot shadow a real
        // $_SERVER entry, which made this test order/machine-dependent.
        config(['controlplane.deployment_uuid' => 'dep-linked-1']);

        // Live plan: granted modules switchable, the rest tucked into a
        // disabled dropdown, capacity inputs always locked.
        Livewire::actingAs($user)
            ->test(\App\Livewire\Admin\Settings\LicenceSettingsPage::class)
            ->assertSee('Managed by ControlDesk')
            ->assertSee('Save licensing')
            ->assertSee('Core Academic System')
            ->assertSee('Modular Extensions')
            ->assertSee('Financial Portal')
            ->assertSee('Other modules')
            ->assertSee('disabled')
            ->assertSee('Centrally managed');

        // Granted toggles persist; ungranted escalation and capacity edits
        // are ignored server-side.
        Livewire::actingAs($user)
            ->test(\App\Livewire\Admin\Settings\LicenceSettingsPage::class)
            ->set('coreStates.attendance', false)
            ->set('moduleStates.finance', false)
            ->set('moduleStates.reports', true)
            ->set('max_active_students', '999')
            ->call('save')
            ->assertHasNoErrors();

        $row = SchoolLicence::query()->where('school_id', $school->id)->firstOrFail();
        $this->assertFalse((bool) $row->core_attendance);
        $this->assertFalse((bool) $row->module_finance);
        $this->assertFalse((bool) $row->module_reports);
        $this->assertSame(100, $row->max_active_students);
        $this->assertSame('dep-linked-1', $row->external_ref);

        Livewire::actingAs($user)
            ->test(SetupLicenceForm::class)
            ->assertSee('Managed by ControlDesk')
            ->assertSee('Core Academic System')
            ->assertSee('Financial Portal')
            ->assertSee('disabled')
            ->assertDontSee('Continue to faculties');
    }

    public function test_expired_linked_install_is_core_only_with_empty_state(): void
    {
        $school = $this->setupSchool();
        $user = $this->owner();
        $this->linkSchool($school, [
            'licence_end' => now()->subDay()->toDateString(),
            'support_until' => now()->subDay()->toDateString(),
        ]);
        config(['controlplane.deployment_uuid' => 'dep-uuid-1234']);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Admin\Settings\LicenceSettingsPage::class)
            ->assertSee('LICENCE INACTIVE')
            ->assertSee('Core Academic System')
            ->assertSee('Modular extensions unavailable')
            ->assertSee('Activate licence')
            ->assertDontSee('Financial Portal');

        // Full saves are blocked until reactivation; core-only saves pass.
        Livewire::actingAs($user)
            ->test(\App\Livewire\Admin\Settings\LicenceSettingsPage::class)
            ->call('save')
            ->assertHasErrors(['form']);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Admin\Settings\LicenceSettingsPage::class)
            ->set('coreStates.attendance', false)
            ->call('saveCoreFeatures')
            ->assertHasNoErrors();

        $this->assertFalse((bool) SchoolLicence::query()->where('school_id', $school->id)->value('core_attendance'));
    }

    public function test_module_grants_split_plan_vs_other(): void
    {
        $school = $this->setupSchool();
        $user = $this->owner();
        $this->linkSchool($school);

        Http::fake(['*/api/v1/heartbeats' => Http::response(
            ['status' => 'ok', 'snapshot' => $this->heartbeatSnapshot()], 200
        )]);
        $this->assertTrue(app(LicenceEnrollmentService::class)->syncHeartbeat());

        $component = Livewire::actingAs($user)->test(\App\Livewire\Admin\Settings\LicenceSettingsPage::class);

        $this->assertTrue($component->get('isLive'));
        $this->assertSame(['finance'], array_keys($component->viewData('grantedModules')));
        $this->assertCount(9, $component->viewData('otherModules'));
        $this->assertTrue(app(LicenceEnrollmentService::class)->centralModuleGrants()['finance']);
        $this->assertFalse(app(LicenceEnrollmentService::class)->centralModuleGrants()['reports']);
    }

    public function test_modal_redeem_activates_and_refreshes_in_place(): void
    {
        $school = $this->setupSchool();
        $user = $this->owner();
        SchoolLicence::query()->updateOrCreate(['school_id' => $school->id], [
            'provisional' => true,
            'external_ref' => null,
            'module_finance' => false,
        ]);

        Http::fake([
            '*/api/v1/enroll' => Http::response($this->snapshot(), 200),
        ]);

        $component = Livewire::actingAs($user)
            ->test(\App\Livewire\Admin\Settings\LicenceSettingsPage::class)
            ->assertSee('Modular extensions unavailable');

        $component
            ->set('enrollCode', 'APEX-2026-ABCD')
            ->call('redeemCode')
            ->assertHasNoErrors()
            ->assertSee('Financial Portal')
            ->assertDontSee('Modular extensions unavailable');

        $this->assertTrue($component->get('isLive'));

        $row = SchoolLicence::query()->where('school_id', $school->id)->firstOrFail();
        $this->assertFalse((bool) $row->provisional);
        $this->assertSame('dep-uuid-1234', $row->external_ref);
        $this->assertTrue((bool) $row->module_finance);
    }

    public function test_wizard_shows_one_mode_at_a_time(): void
    {
        $this->setupSchool();
        $user = $this->owner();

        $component = Livewire::actingAs($user)->test(SetupLicenceForm::class);

        // Switcher always visible; redeem panel first by default.
        $component
            ->assertSee('Redeem code')
            ->assertSee('Import file')
            ->assertSee('Continue offline')
            ->assertSee('Redeem & continue')
            ->assertDontSee('Signed blob')
            ->assertDontSee('Saved for silent retry');

        $component
            ->set('mode', 'import')
            ->assertSee('Signed blob')
            ->assertSee('Verify & continue')
            ->assertDontSee('Redeem & continue');

        $component
            ->set('mode', 'offline')
            ->assertSee('Saved for silent retry')
            ->assertDontSee('Signed blob');
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

    /**
     * Mint a signed demo-key document fixture (ed25519) for one-way-gate
     * tests. Returns the JSON-encoded document as pasted at the key screen.
     */
    private function mintDemoKey(string $host, string $exp): string
    {
        // Fresh keypair every call: static caching leaked the secret across
        // tests while config resets per test, signing with an unconfigured key.
        $keypair = sodium_crypto_sign_keypair();
        $secret = sodium_crypto_sign_secretkey($keypair);
        config(['controlplane.public_key' => base64_encode(sodium_crypto_sign_publickey($keypair))]);

        $payload = ['kind' => 'demo', 'expires_at' => $exp, 'host' => $host, 'issued_at' => now()->toDateString(), 'version' => 1, 'kid' => '2026-a'];

        return (string) json_encode([
            'payload' => $payload,
            'signature' => bin2hex(sodium_crypto_sign_detached(
                \App\Services\DemoKeyVerifier::canonicalJson($payload),
                $secret
            )),
            'algorithm' => 'ed25519',
        ]);
    }

    public function test_elevation_panel_shows_only_with_existing_data(): void
    {
        $this->setupSchool();
        $user = $this->owner();

        // Owner alone: fresh setup, no elevation panel.
        Livewire::actingAs($user)
            ->test(SetupLicenceForm::class)
            ->assertDontSee('Continue with existing data and activate')
            ->assertDontSee('Start fresh');

        // Real records present: elevation choice appears.
        User::factory()->create(['type' => 'admin', 'username' => 'second_admin']);

        Livewire::actingAs($user)
            ->test(SetupLicenceForm::class)
            ->assertSee('Continue with existing data and activate')
            ->assertSee('Start fresh')
            ->assertSee('Reinstall a fresh copy');
    }

    public function test_elevation_activates_and_voids_demo_key(): void
    {
        $school = $this->setupSchool();
        $user = $this->owner();
        User::factory()->create(['type' => 'admin', 'username' => 'second_admin']);

        // A local demo key is present before elevation.
        file_put_contents($this->envFile, "APP_NAME=Test\nDEMO_KEY=old-demo-key\nOTHER=kept\n");
        session(['demo_key_accepted' => true]);

        Http::fake(['*/api/v1/enroll' => Http::response($this->snapshot(), 200)]);

        Livewire::actingAs($user)
            ->test(SetupLicenceForm::class)
            ->set('elevationCode', 'APEX-2026-ELEVATE')
            ->call('activateWithExistingData')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.setup.faculties', absolute: false));

        // Licence replaced, provisional cleared.
        $row = SchoolLicence::query()->where('school_id', $school->id)->firstOrFail();
        $this->assertSame('dep-uuid-1234', $row->external_ref);
        $this->assertFalse((bool) $row->provisional);

        // Demo key voided locally, everything else kept.
        $env = (string) file_get_contents($this->envFile);
        $this->assertStringNotContainsString('DEMO_KEY', $env);
        $this->assertStringContainsString('OTHER=kept', $env);
        $this->assertStringContainsString('DEPLOYMENT_UUID=dep-uuid-1234', $env);
        $this->assertFalse(session('demo_key_accepted', false));
    }

    public function test_elevation_redeem_failure_leaves_everything_untouched(): void
    {
        $school = $this->setupSchool();
        $user = $this->owner();
        User::factory()->create(['type' => 'admin', 'username' => 'second_admin']);

        file_put_contents($this->envFile, "APP_NAME=Test\nDEMO_KEY=old-demo-key\nOTHER=kept\n");
        session(['demo_key_accepted' => true]);

        Http::fake(['*/api/v1/enroll' => Http::response(['message' => 'nope', 'error' => 'unknown_code'], 422)]);

        $component = Livewire::actingAs($user)
            ->test(SetupLicenceForm::class)
            ->set('elevationCode', 'APEX-2026-ELEVATE')
            ->call('activateWithExistingData');

        $component->assertHasNoErrors();
        $this->assertStringContainsString('fresh code', (string) $component->get('enrollError'));
        $this->assertNull(SchoolLicence::query()->where('school_id', $school->id)->value('external_ref'));

        // Failed elevation never voids the local demo key.
        $env = (string) file_get_contents($this->envFile);
        $this->assertStringContainsString('DEMO_KEY=old-demo-key', $env);
        $this->assertTrue(session('demo_key_accepted', false));
    }

    public function test_live_install_refuses_demo_keys_one_way(): void
    {
        $school = $this->setupSchool();
        config(['controlplane.deployment_uuid' => 'dep-live-1']);
        SchoolLicence::query()->updateOrCreate(['school_id' => $school->id], [
            'external_ref' => 'dep-live-1',
            'provisional' => false,
        ]);

        $key = $this->mintDemoKey('localhost', now()->addMonth()->toDateString());
        $verifier = app(\App\Services\DemoKeyVerifier::class);

        $this->assertFalse($verifier->verify($key, 'localhost'));

        // Same key passes on an unlinked install.
        config(['controlplane.deployment_uuid' => null]);
        $this->assertTrue($verifier->verify($key, 'localhost'));
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

    public function test_ping_reports_demo_trial_without_uuid(): void
    {
        $this->setupSchool();

        $key = $this->mintDemoKey('localhost', now()->addMonth()->toDateString());
        app(\App\Services\DemoKeyVerifier::class)->cacheDocument(json_decode($key, true));

        $this->artisan('controlplane:ping')
            ->assertSuccessful()
            ->expectsOutputToContain('Demo trial active')
            ->expectsOutputToContain('Valid until:');
    }

    public function test_ping_fails_with_guidance_when_nothing_held(): void
    {
        $this->setupSchool();

        $this->artisan('controlplane:ping')
            ->assertFailed()
            ->expectsOutputToContain('no demo trial');
    }

    public function test_ping_redeem_accepts_demo_document(): void
    {
        $this->setupSchool();

        $key = $this->mintDemoKey('localhost', now()->addMonth()->toDateString());

        $this->artisan('controlplane:ping', ['--redeem' => $key])
            ->assertSuccessful()
            ->expectsOutputToContain('Demo accepted');

        $this->assertTrue(app(\App\Services\DemoKeyVerifier::class)->isUnlocked());
    }

    /**
     * Seed a linked install (UUID+token configured, row carrying the UUID).
     */
    private function linkSchool(School $school, array $rowOverrides = []): void
    {
        config(['controlplane.deployment_uuid' => 'dep-uuid-1234']);
        config(['controlplane.token' => 'tok-secret-5678']);

        SchoolLicence::query()->updateOrCreate(['school_id' => $school->id], array_merge([
            'external_ref' => 'dep-uuid-1234',
            'provisional' => false,
            'licence_key' => null,
            'max_active_students' => 100,
            'core_timetable' => true,
            'core_attendance' => true,
            'core_memos' => true,
            'core_impersonation' => true,
            'module_finance' => false,
            'module_reports' => false,
        ], $rowOverrides));
    }

    /**
     * Heartbeat-attached snapshot in the internal shape the ping path
     * passes through to applySnapshot.
     */
    private function heartbeatSnapshot(array $licenceOverrides = []): array
    {
        return [
            'deployment_uuid' => 'dep-uuid-1234',
            'licence' => array_merge([
                'package_tier' => 'complete',
                'max_active_students' => 350,
                'starts_at' => now()->toDateString(),
                'expires_at' => '2027-09-18',
                'core' => [],
                'modules' => ['finance' => true],
            ], $licenceOverrides),
        ];
    }

    public function test_heartbeat_sync_applies_central_change(): void
    {
        $school = $this->setupSchool();
        $this->linkSchool($school, ['module_finance' => false, 'max_active_students' => 100]);

        Http::fake(['*/api/v1/heartbeats' => Http::response(
            ['status' => 'ok', 'snapshot' => $this->heartbeatSnapshot()], 200
        )]);

        $logs = new \Tests\Support\RecordingLogger();
        \Illuminate\Support\Facades\Log::swap($logs);

        $this->assertTrue(app(LicenceEnrollmentService::class)->syncHeartbeat());

        $row = SchoolLicence::query()->where('school_id', $school->id)->firstOrFail();
        $this->assertTrue((bool) $row->module_finance);
        $this->assertSame(350, $row->max_active_students);
        $this->assertSame('dep-uuid-1234', $row->external_ref);
        $this->assertFalse((bool) $row->provisional);

        // Receipt logged with counts only — the token never appears.
        $haystack = (string) json_encode($logs->records);
        $this->assertStringNotContainsString('tok-secret-5678', $haystack);
    }

    public function test_heartbeat_sync_keeps_local_core_off(): void
    {
        $school = $this->setupSchool();
        $this->linkSchool($school, ['core_timetable' => false, 'module_finance' => false]);

        $snapshot = $this->heartbeatSnapshot(['core' => ['timetable' => true]]);
        Http::fake(['*/api/v1/heartbeats' => Http::response(
            ['status' => 'ok', 'snapshot' => $snapshot], 200
        )]);

        $this->assertTrue(app(LicenceEnrollmentService::class)->syncHeartbeat());

        $row = SchoolLicence::query()->where('school_id', $school->id)->firstOrFail();
        // Central true + local false stays false; modules still follow central.
        $this->assertFalse((bool) $row->core_timetable);
        $this->assertTrue((bool) $row->module_finance);
    }

    public function test_heartbeat_sync_central_off_kills_local_on(): void
    {
        $school = $this->setupSchool();
        $this->linkSchool($school, ['core_memos' => true]);

        $snapshot = $this->heartbeatSnapshot(['core' => ['memos' => false]]);
        Http::fake(['*/api/v1/heartbeats' => Http::response(
            ['status' => 'ok', 'snapshot' => $snapshot], 200
        )]);

        $this->assertTrue(app(LicenceEnrollmentService::class)->syncHeartbeat());

        $row = SchoolLicence::query()->where('school_id', $school->id)->firstOrFail();
        $this->assertFalse((bool) $row->core_memos);
    }

    public function test_heartbeat_sync_offline_keeps_cache(): void
    {
        $school = $this->setupSchool();
        $this->linkSchool($school, ['module_finance' => false, 'core_memos' => true]);

        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('down');
        });

        // Prime the serving cache the way a running app would hold it.
        app(\App\Services\SchoolLicenceService::class)->getLicenceRow();

        $this->assertTrue(app(LicenceEnrollmentService::class)->syncHeartbeat());

        $row = SchoolLicence::query()->where('school_id', $school->id)->firstOrFail();
        $this->assertFalse((bool) $row->module_finance);
        $this->assertTrue((bool) $row->core_memos);
        $this->assertSame(100, $row->max_active_students);
    }

    public function test_heartbeat_sync_skips_unlinked(): void
    {
        $school = $this->setupSchool();
        config(['controlplane.deployment_uuid' => 'dep-uuid-1234']);
        config(['controlplane.token' => 'tok-secret-5678']);
        SchoolLicence::query()->updateOrCreate(['school_id' => $school->id], [
            'external_ref' => 'dep-other-9999',
            'provisional' => false,
            'licence_key' => null,
            'module_finance' => false,
        ]);

        Http::fake(['*/api/v1/heartbeats' => Http::response(
            ['status' => 'ok', 'snapshot' => $this->heartbeatSnapshot()], 200
        )]);

        $this->assertTrue(app(LicenceEnrollmentService::class)->syncHeartbeat());

        Http::assertNothingSent();
        $this->assertFalse((bool) SchoolLicence::query()->where('school_id', $school->id)->value('module_finance'));
    }

    public function test_heartbeat_sync_skips_provisional(): void
    {
        $school = $this->setupSchool();
        $this->linkSchool($school, ['provisional' => true, 'external_ref' => null, 'module_finance' => false]);

        Http::fake(['*/api/v1/heartbeats' => Http::response(
            ['status' => 'ok', 'snapshot' => $this->heartbeatSnapshot()], 200
        )]);

        $this->assertTrue(app(LicenceEnrollmentService::class)->syncHeartbeat());

        Http::assertNothingSent();
        $this->assertFalse((bool) SchoolLicence::query()->where('school_id', $school->id)->value('module_finance'));
    }

    public function test_grant_badges_reflect_granted_vs_ungranted(): void
    {
        $school = $this->setupSchool();
        $user = $this->owner();
        $this->linkSchool($school);

        $snapshot = $this->heartbeatSnapshot(['core' => ['timetable' => true, 'memos' => false]]);
        Http::fake(['*/api/v1/heartbeats' => Http::response(
            ['status' => 'ok', 'snapshot' => $snapshot], 200
        )]);
        $this->assertTrue(app(LicenceEnrollmentService::class)->syncHeartbeat());

        $component = Livewire::actingAs($user)->test(\App\Livewire\Admin\Settings\LicenceSettingsPage::class);

        $grants = $component->viewData('coreGrants');
        $this->assertTrue($grants['timetable']);
        $this->assertFalse($grants['memos']);
        $this->assertTrue($component->viewData('showGrantBadges'));

        $component->assertSee('On your plan', false)
            ->assertSee('Not included — contact ops', false);
    }

    public function test_grant_badges_hidden_when_not_linked(): void
    {
        $school = $this->setupSchool();
        $user = $this->owner();
        SchoolLicence::query()->updateOrCreate(['school_id' => $school->id], [
            'provisional' => true,
            'external_ref' => null,
            'core_timetable' => true,
            'core_memos' => false,
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Admin\Settings\LicenceSettingsPage::class)
            ->assertDontSee('On your plan', false)
            ->assertDontSee('Not included', false);
    }

    public function test_saving_inside_grant_unaffected(): void
    {
        $school = $this->setupSchool();
        $user = $this->owner();
        $this->linkSchool($school);

        Http::fake(['*/api/v1/heartbeats' => Http::response(
            ['status' => 'ok', 'snapshot' => $this->heartbeatSnapshot()], 200
        )]);
        $this->assertTrue(app(LicenceEnrollmentService::class)->syncHeartbeat());

        Livewire::actingAs($user)
            ->test(\App\Livewire\Admin\Settings\LicenceSettingsPage::class)
            ->set('coreStates.attendance', false)
            ->call('saveCoreFeatures')
            ->assertHasNoErrors();

        $row = SchoolLicence::query()->where('school_id', $school->id)->firstOrFail();
        $this->assertFalse((bool) $row->core_attendance);
        $this->assertSame('dep-uuid-1234', $row->external_ref);
    }

    public function test_heartbeat_sync_skips_file_managed(): void
    {
        $school = $this->setupSchool();
        $this->linkSchool($school, ['licence_key' => 'FILE-KEY-1', 'module_finance' => false]);

        Http::fake(['*/api/v1/heartbeats' => Http::response(
            ['status' => 'ok', 'snapshot' => $this->heartbeatSnapshot()], 200
        )]);

        $this->assertTrue(app(LicenceEnrollmentService::class)->syncHeartbeat());

        Http::assertNothingSent();
        $this->assertFalse((bool) SchoolLicence::query()->where('school_id', $school->id)->value('module_finance'));
    }
}
