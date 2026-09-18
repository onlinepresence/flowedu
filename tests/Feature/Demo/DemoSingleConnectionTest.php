<?php

namespace Tests\Feature\Demo;

use App\Services\DemoKeyVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class DemoSingleConnectionTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    public function test_prod_path_identical_with_flag_off(): void
    {
        config(['college.demo_mode' => false]);
        config(['college.demo_key' => null]);
        $this->createTestSchool();

        // Old demo machinery is gone.
        $this->assertNull(config('database.connections.demo'));
        $this->post('/demo/toggle')->assertNotFound();
        $this->post('/demo/reset')->assertNotFound();
        $this->get('/demo/setup')->assertNotFound();

        // Production behaviour unaffected: register renders, login has no demo hint.
        $this->get('/register')->assertOk();
        $this->get('/login')->assertOk()->assertDontSee('Quick Demo Login', false);
    }

    public function test_key_screen_shows_with_flag_on_and_no_key(): void
    {
        config(['college.demo_mode' => true]);
        config(['college.demo_key' => null]);
        $this->createTestSchool();

        // Nothing else renders: 403-style branded key screen.
        $response = $this->get('/login');
        $response->assertStatus(403);
        $response->assertSee('Enter your demo key', false);
        $response->assertSee('FlowEdu', false);

        // Key entry screen itself is reachable.
        $this->get('/demo/key')->assertOk()->assertSee('Access key', false);
    }

    public function test_demo_key_env_bypasses_screen(): void
    {
        config(['college.demo_mode' => true]);
        config(['college.demo_key' => 'FLOWEDU-DEMO-2026']);
        $this->createTestSchool();

        $this->get('/login')->assertOk();
        $this->get('/demo/key')->assertRedirect(route('login'));
    }

    public function test_key_entry_sets_session_and_passes_gate(): void
    {
        config(['college.demo_mode' => true]);
        config(['college.demo_key' => null]);
        $this->createTestSchool();

        $this->post('/demo/key', ['code' => 'short'])->assertRedirect(route('demo.key.show'));

        $this->post('/demo/key', ['code' => 'FLOWEDU-DEMO-2026'])
            ->assertRedirect(route('login'));

        $this->assertTrue(session('demo_key_accepted', false));

        $this->get('/login')->assertOk()->assertSee('Quick Demo Login', false);
    }

    public function test_registration_closed_in_demo_mode(): void
    {
        config(['college.demo_mode' => true]);
        config(['college.demo_key' => 'FLOWEDU-DEMO-2026']);
        $this->createTestSchool();

        $this->get('/register')->assertForbidden();
        $this->get('/login')->assertOk()->assertDontSee('Create account', false);
    }

    public function test_mail_forced_to_log_in_demo_mode(): void
    {
        config(['college.demo_mode' => true]);
        config(['college.demo_key' => 'FLOWEDU-DEMO-2026']);
        config(['mail.default' => 'array']);
        $this->createTestSchool();

        $this->get('/login')->assertOk();
        $this->assertSame('log', config('mail.default'));
    }

    public function test_licence_enforcement_stays_on_in_demo(): void
    {
        config(['college.demo_mode' => true]);
        config(['college.demo_key' => 'FLOWEDU-DEMO-2026']);
        $this->createTestSchool();

        $this->get('/login')->assertOk();
        $this->assertTrue((bool) config('licence.enforce', true));
    }

    public function test_verifier_accepts_sane_format_and_rejects_short(): void
    {
        $verifier = app(DemoKeyVerifier::class);

        $this->assertTrue($verifier->verify('FLOWEDU-DEMO-2026', 'example.com'));
        $this->assertFalse($verifier->verify('short', 'example.com'));
        $this->assertFalse($verifier->verify('', 'example.com'));
        $this->assertFalse($verifier->verify('bad code! with spaces', 'example.com'));
    }

    public function test_refresh_aborts_when_demo_flag_off(): void
    {
        config(['college.demo_mode' => false]);
        config(['college.demo_key' => 'FLOWEDU-DEMO-2026']);

        $this->artisan('demo:refresh')
            ->expectsOutputToContain('Aborted: APP_DEMO is not true.')
            ->assertExitCode(1);
    }

    public function test_refresh_ignores_database_name_when_demo_flag_on(): void
    {
        config(['college.demo_mode' => true]);

        // Restriction is env-only: a non-demo database name must NOT abort.
        // Assert the command no longer carries the db-name guard.
        $source = (string) file_get_contents(app_path('Console/Commands/DemoRefreshCommand.php'));
        $this->assertStringNotContainsString('does not contain "demo"', $source);
        $this->assertStringNotContainsStringIgnoringCase('stripos', $source);
    }
}
