<?php

declare(strict_types=1);

namespace Tests\Feature\Licence;

use App\Models\SchoolLicence;
use Database\Seeders\AdminSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class LicenceResetTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    private string $envFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AdminSystemSeeder::class);
        \App\Models\UserRole::ensureSystemRoles();

        $this->envFile = tempnam(sys_get_temp_dir(), 'envtest');
        file_put_contents(
            $this->envFile,
            "APP_NAME=Test\nDEPLOYMENT_UUID=dep-uuid-1234\nCONTROL_PLANE_TOKEN=tok-secret\nDEMO_KEY=enc:stub\n"
        );
        config(['controlplane.env_path' => $this->envFile]);
        config([
            'controlplane.deployment_uuid' => 'dep-uuid-1234',
            'controlplane.token' => 'tok-secret',
            'college.demo_key' => 'enc:stub',
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->envFile) && file_exists($this->envFile)) {
            unlink($this->envFile);
        }

        parent::tearDown();
    }

    private function seedLicenceState(): void
    {
        $school = $this->createTestSchool();
        SchoolLicence::query()->updateOrCreate(['school_id' => $school->id], [
            'external_ref' => 'dep-uuid-1234',
            'provisional' => false,
        ]);

        foreach (['controlplane.pending_code', 'demo.cached_key_document', 'demo.last_online_check', 'demo.last_status', 'demo.revoked_key_hash', 'licence.last_central_grant'] as $key) {
            DB::table('settings')->insert([
                'setting_key' => $key,
                'setting_value' => 'x',
                'data_type' => 'string',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->assertSame(1, SchoolLicence::query()->count());
    }

    private function envContents(): string
    {
        return (string) file_get_contents($this->envFile);
    }

    public function test_full_reset_clears_link_demo_row_and_settings(): void
    {
        $this->seedLicenceState();

        $code = Artisan::call('licence:reset', ['--force' => true]);

        $this->assertSame(0, $code);
        $env = $this->envContents();
        $this->assertStringNotContainsString('DEPLOYMENT_UUID=', $env);
        $this->assertStringNotContainsString('CONTROL_PLANE_TOKEN=', $env);
        $this->assertStringNotContainsString('DEMO_KEY=', $env);
        $this->assertStringContainsString('APP_NAME=Test', $env);
        $this->assertSame(0, DB::table('settings')->whereIn('setting_key', \App\Console\Commands\LicenceResetCommand::RESET_SETTINGS)->count());
        $this->assertSame(0, SchoolLicence::query()->count());
        // Runtime config mirrors the reset for the rest of the process.
        $this->assertNull(config('controlplane.deployment_uuid'));
        $this->assertNull(config('college.demo_key'));
    }

    public function test_demo_only_keeps_link_and_row(): void
    {
        $this->seedLicenceState();

        $code = Artisan::call('licence:reset', ['--demo-only' => true, '--force' => true]);

        $this->assertSame(0, $code);
        $env = $this->envContents();
        $this->assertStringContainsString('DEPLOYMENT_UUID=dep-uuid-1234', $env);
        $this->assertStringContainsString('CONTROL_PLANE_TOKEN=tok-secret', $env);
        $this->assertStringNotContainsString('DEMO_KEY=', $env);
        $this->assertSame(0, DB::table('settings')->where('setting_key', 'like', 'demo.%')->count());
        $this->assertSame(1, SchoolLicence::query()->count());
    }

    public function test_aborts_without_force_leaving_everything(): void
    {
        $this->seedLicenceState();

        $code = Artisan::call('licence:reset');

        $this->assertSame(0, $code);
        $this->assertStringContainsString('DEMO_KEY=enc:stub', $this->envContents());
        $this->assertSame(1, SchoolLicence::query()->count());
    }
}
