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

    /**
     * Mint a signed demo key for tests. Returns [key, secret] so tests can
     * tamper deliberately.
     *
     * @return array{0: string, 1: string}
     */
    private function mintDemoKey(string $host, string $exp, ?string $iat = null): array
    {
        static $secret = null;
        static $public = null;

        if ($secret === null) {
            $keypair = sodium_crypto_sign_keypair();
            $secret = sodium_crypto_sign_secretkey($keypair);
            $public = sodium_crypto_sign_publickey($keypair);
        }

        config(['college.demo_public_key' => base64_encode($public)]);

        $segment = rtrim(strtr(base64_encode((string) json_encode([
            'h' => $host,
            'exp' => $exp,
            'iat' => $iat ?? now()->toDateString(),
        ])), '+/', '-_'), '=');
        $sig = rtrim(strtr(base64_encode(sodium_crypto_sign_detached($segment, $secret)), '+/', '-_'), '=');

        return ['demo1.'.$segment.'.'.$sig, $secret];
    }

    public function test_key_entry_sets_session_and_passes_gate(): void
    {
        config(['college.demo_mode' => true]);
        config(['college.demo_key' => null]);
        $this->createTestSchool();

        $this->post('/demo/key', ['code' => 'short'])->assertRedirect(route('demo.key.show'));

        [$key] = $this->mintDemoKey('localhost', now()->addMonth()->toDateString());

        $this->post('/demo/key', ['code' => $key])
            ->assertRedirect(route('login'));

        $this->assertTrue(session('demo_key_accepted', false));

        $this->get('/login')->assertOk()->assertSee('Quick Demo Login', false);
    }

    public function test_key_entry_shows_wrong_door_copy_for_heartbeat_token(): void
    {
        config(['college.demo_mode' => true]);
        config(['college.demo_key' => null]);
        $this->createTestSchool();

        $response = $this->post('/demo/key', ['code' => 'tok-secret-0123456789abcdef']);
        $response->assertRedirect(route('demo.key.show'));
        $this->assertStringContainsString(
            'heartbeat',
            (string) session('demo_key_error')
        );
        $this->assertFalse(session('demo_key_accepted', false));
    }

    public function test_key_entry_rejects_expired_key_with_copy(): void
    {
        config(['college.demo_mode' => true]);
        config(['college.demo_key' => null]);
        $this->createTestSchool();

        [$key] = $this->mintDemoKey('localhost', now()->subDay()->toDateString());

        $this->post('/demo/key', ['code' => $key])->assertRedirect(route('demo.key.show'));
        $this->assertStringContainsString('expired', (string) session('demo_key_error'));
        $this->assertFalse(session('demo_key_accepted', false));
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

    public function test_verifier_checks_signature_expiry_and_host(): void
    {
        $verifier = app(DemoKeyVerifier::class);

        [$key] = $this->mintDemoKey('demo.example.com', now()->addMonth()->toDateString());

        $this->assertTrue($verifier->verify($key, 'demo.example.com'));
        $this->assertSame(DemoKeyVerifier::REASON_OK, $verifier->check($key, 'demo.example.com')['reason']);

        // Tampered signature.
        $tampered = substr_replace($key, $key[-1] === 'A' ? 'B' : 'A', -1);
        $this->assertFalse($verifier->verify($tampered, 'demo.example.com'));

        // Expired.
        [$old] = $this->mintDemoKey('demo.example.com', now()->subDay()->toDateString());
        $this->assertSame(DemoKeyVerifier::REASON_EXPIRED, $verifier->check($old, 'demo.example.com')['reason']);

        // Wrong host.
        $this->assertSame(DemoKeyVerifier::REASON_HOST_MISMATCH, $verifier->check($key, 'other.example.com')['reason']);

        // Issued-in-the-future: clock suspect.
        [$future] = $this->mintDemoKey('demo.example.com', now()->addYear()->toDateString(), now()->addDays(5)->toDateString());
        $this->assertSame(DemoKeyVerifier::REASON_CLOCK_SKEW, $verifier->check($future, 'demo.example.com')['reason']);

        // Bare opaque token: wrong door, not a demo key at all.
        $this->assertSame(DemoKeyVerifier::REASON_WRONG_DOOR, $verifier->check('tok-secret-0123456789abcdef', 'demo.example.com')['reason']);

        // Garbage and blanks.
        $this->assertFalse($verifier->verify('short', 'demo.example.com'));
        $this->assertFalse($verifier->verify('', 'demo.example.com'));
        $this->assertFalse($verifier->verify('bad code! with spaces', 'demo.example.com'));
        $this->assertFalse($verifier->verify('demo1.not-base64!!.also-bad!!', 'demo.example.com'));
    }

    public function test_verifier_rejects_everything_when_unconfigured(): void
    {
        config(['college.demo_public_key' => null]);
        $verifier = app(DemoKeyVerifier::class);

        [$key] = $this->mintDemoKey('demo.example.com', now()->addMonth()->toDateString());
        // mintDemoKey sets the config; clear it again to simulate no key.
        config(['college.demo_public_key' => null]);

        $this->assertSame(DemoKeyVerifier::REASON_UNCONFIGURED, $verifier->check($key, 'demo.example.com')['reason']);
        $this->assertFalse($verifier->verify($key, 'demo.example.com'));
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
