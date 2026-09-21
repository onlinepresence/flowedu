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

    private string $envFile;

    protected function setUp(): void
    {
        parent::setUp();

        // Never touch the real .env: EnvWriter honours this override.
        $this->envFile = tempnam(sys_get_temp_dir(), 'demo-env-');
        file_put_contents($this->envFile, "APP_NAME=Test\n");
        config(['controlplane.env_path' => $this->envFile]);
    }

    protected function tearDown(): void
    {
        @unlink($this->envFile);

        parent::tearDown();
    }

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
     * Mint a signed licence document fixture. Returns [document array, secret].
     *
     * @return array{0: array, 1: string}
     */
    private function mintDocument(?string $host, ?string $exp, ?string $iat = null): array
    {
        static $secret = null;
        static $public = null;

        if ($secret === null) {
            $keypair = sodium_crypto_sign_keypair();
            $secret = sodium_crypto_sign_secretkey($keypair);
            $public = sodium_crypto_sign_publickey($keypair);
        }

        config(['college.demo_public_key' => base64_encode($public)]);

        $payload = ['expires_at' => $exp, 'host' => $host, 'issued_at' => $iat ?? now()->toDateString()];
        $message = DemoKeyVerifier::canonicalJson($payload);

        return [
            [
                'payload' => $payload,
                'signature' => bin2hex(sodium_crypto_sign_detached($message, $secret)),
                'algorithm' => 'ed25519',
            ],
            $secret,
        ];
    }

    public function test_key_entry_sets_session_and_passes_gate(): void
    {
        config(['college.demo_mode' => true]);
        config(['college.demo_key' => null]);
        $this->createTestSchool();

        $this->post('/demo/key', ['code' => 'short'])->assertRedirect(route('demo.key.show'));

        [$doc] = $this->mintDocument('localhost', now()->addMonth()->toDateString());

        $this->post('/demo/key', ['code' => json_encode($doc)])
            ->assertRedirect(route('login'));

        $this->assertTrue(session('demo_key_accepted', false));

        $this->get('/login')->assertOk()->assertSee('Quick Demo Login', false);
    }

    public function test_key_entry_shows_wrong_door_copy_for_heartbeat_token(): void
    {
        config(['college.demo_mode' => true]);
        config(['college.demo_key' => null]);
        $this->createTestSchool();

        $response = $this->post('/demo/key', ['code' => 'tok-secret-0123456789abcdefABCDEF0123456789ab']);
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

        [$doc] = $this->mintDocument('localhost', now()->subDay()->toDateString());

        $this->post('/demo/key', ['code' => json_encode($doc)])->assertRedirect(route('demo.key.show'));
        $this->assertStringContainsString('expired', (string) session('demo_key_error'));
        $this->assertFalse(session('demo_key_accepted', false));
    }

    public function test_bare_code_verifies_online_then_caches_for_offline(): void
    {
        config(['college.demo_mode' => true]);
        config(['college.demo_key' => null]);
        config(['controlplane.url' => 'https://control.test']);
        $this->createTestSchool();

        [$doc] = $this->mintDocument('localhost', now()->addMonth()->toDateString());

        // Online: ControlDesk returns the document, which is cached + verified.
        \Illuminate\Support\Facades\Http::fake([
            '*/api/v1/demo-keys/verify' => \Illuminate\Support\Facades\Http::response($doc, 200),
        ]);

        $this->post('/demo/key', ['code' => 'SOME-BARE-CODE-123'])
            ->assertRedirect(route('login'));
        $this->assertTrue(session('demo_key_accepted', false));
        $this->assertNotNull(
            \App\Models\Setting::query()->where('setting_key', DemoKeyVerifier::CACHE_KEY)->value('setting_value')
        );

        // Offline (network down): the cached document still opens the door.
        session()->forget('demo_key_accepted');
        \Illuminate\Support\Facades\Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('down');
        });

        $this->post('/demo/key', ['code' => 'ANY-OTHER-CODE-456'])
            ->assertRedirect(route('login'));
        $this->assertTrue(session('demo_key_accepted', false));
    }

    public function test_bare_code_offline_without_cache_stays_on_screen(): void
    {
        config(['college.demo_mode' => true]);
        config(['college.demo_key' => null]);
        config(['controlplane.url' => 'https://control.test']);
        $this->createTestSchool();

        $this->assertNull(
            \App\Models\Setting::query()->where('setting_key', DemoKeyVerifier::CACHE_KEY)->value('setting_value')
        );

        \Illuminate\Support\Facades\Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('down');
        });

        $this->post('/demo/key', ['code' => 'SOME-BARE-CODE-123'])->assertRedirect(route('demo.key.show'));
        $this->assertFalse(session('demo_key_accepted', false));
    }

    public function test_marketing_key_without_expiry_is_accepted_loudly(): void
    {
        $verifier = app(DemoKeyVerifier::class);

        [$doc] = $this->mintDocument('localhost', null);

        $this->assertTrue($verifier->verify(json_encode($doc), 'localhost'));
        $this->assertSame(DemoKeyVerifier::REASON_OK, $verifier->check(json_encode($doc), 'localhost')['reason']);
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

        [$doc] = $this->mintDocument('demo.example.com', now()->addMonth()->toDateString());
        $code = (string) json_encode($doc);

        $this->assertTrue($verifier->verify($code, 'demo.example.com'));
        $this->assertSame(DemoKeyVerifier::REASON_OK, $verifier->check($code, 'demo.example.com')['reason']);

        // Tampered signature.
        $tampered = $doc;
        $tampered['signature'] = str_repeat('0', 128);
        $this->assertFalse($verifier->verify((string) json_encode($tampered), 'demo.example.com'));

        // Wrong algorithm.
        $wrongAlgo = $doc;
        $wrongAlgo['algorithm'] = 'hmac-sha256';
        $this->assertFalse($verifier->verify((string) json_encode($wrongAlgo), 'demo.example.com'));

        // Expired.
        [$old] = $this->mintDocument('demo.example.com', now()->subDay()->toDateString());
        $this->assertSame(DemoKeyVerifier::REASON_EXPIRED, $verifier->check((string) json_encode($old), 'demo.example.com')['reason']);

        // Wrong host.
        $this->assertSame(DemoKeyVerifier::REASON_HOST_MISMATCH, $verifier->check($code, 'other.example.com')['reason']);

        // Unbound host passes anywhere.
        [$open] = $this->mintDocument(null, now()->addMonth()->toDateString());
        $this->assertTrue($verifier->verify((string) json_encode($open), 'other.example.com'));

        // Issued-in-the-future: clock suspect.
        [$future] = $this->mintDocument('demo.example.com', now()->addYear()->toDateString(), now()->addDays(5)->toDateString());
        $this->assertSame(DemoKeyVerifier::REASON_CLOCK_SKEW, $verifier->check((string) json_encode($future), 'demo.example.com')['reason']);

        // Long bare opaque token: wrong door, not a demo key at all.
        $this->assertSame(DemoKeyVerifier::REASON_WRONG_DOOR, $verifier->check('tok-secret-0123456789abcdefABCDEF0123456789ab', 'demo.example.com')['reason']);

        // Garbage and blanks.
        $this->assertFalse($verifier->verify('short', 'demo.example.com'));
        $this->assertFalse($verifier->verify('', 'demo.example.com'));
        $this->assertFalse($verifier->verify('bad code! with spaces', 'demo.example.com'));
        $this->assertFalse($verifier->verify('{"payload": "nope"}', 'demo.example.com'));
    }

    public function test_verifier_rejects_everything_when_unconfigured(): void
    {
        config(['college.demo_public_key' => null]);
        $verifier = app(DemoKeyVerifier::class);

        [$doc] = $this->mintDocument('demo.example.com', now()->addMonth()->toDateString());
        // mintDocument sets the config; clear it again to simulate no key.
        config(['college.demo_public_key' => null]);

        $code = (string) json_encode($doc);
        $this->assertSame(DemoKeyVerifier::REASON_UNCONFIGURED, $verifier->check($code, 'demo.example.com')['reason']);
        $this->assertFalse($verifier->verify($code, 'demo.example.com'));
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

    public function test_key_entry_persists_env_and_cache_on_document_success(): void
    {
        config(['college.demo_mode' => true]);
        config(['college.demo_key' => null]);
        $this->createTestSchool();

        [$doc] = $this->mintDocument('localhost', now()->addMonth()->toDateString());
        $code = (string) json_encode($doc);
        $expectedEnvValue = DemoKeyVerifier::canonicalJson($doc);

        $logs = new \Tests\Support\RecordingLogger();
        \Illuminate\Support\Facades\Log::swap($logs);

        $this->post('/demo/key', ['code' => $code])->assertRedirect(route('login'));

        $this->assertTrue(session('demo_key_accepted', false));

        $env = (string) file_get_contents($this->envFile);
        $this->assertMatchesRegularExpression('/^DEMO_KEY=\S+$/m', $env);
        preg_match('/^DEMO_KEY=(.*)$/m', $env, $m);
        $this->assertSame($expectedEnvValue, DemoKeyVerifier::decodeStoredKey($m[1] ?? null));
        $this->assertStringNotContainsString($expectedEnvValue, $env);
        $this->assertStringNotContainsString($doc['signature'], $env);

        $cached = \App\Models\Setting::query()->where('setting_key', DemoKeyVerifier::CACHE_KEY)->value('setting_value');
        $this->assertNotNull($cached);
        $this->assertSame($doc['signature'], (array) json_decode((string) $cached, true) !== [] ? json_decode((string) $cached, true)['signature'] : null);

        $haystack = (string) json_encode($logs->records);
        $this->assertStringNotContainsString($doc['signature'], $haystack);
        $this->assertStringNotContainsString($expectedEnvValue, $haystack);
    }

    public function test_key_entry_bare_code_persists_code_itself(): void
    {
        config(['college.demo_mode' => true]);
        config(['college.demo_key' => null]);
        config(['controlplane.url' => 'https://control.test']);
        $this->createTestSchool();

        [$doc] = $this->mintDocument('localhost', now()->addMonth()->toDateString());
        \Illuminate\Support\Facades\Http::fake([
            '*/api/v1/demo-keys/verify' => \Illuminate\Support\Facades\Http::response($doc, 200),
        ]);

        $logs = new \Tests\Support\RecordingLogger();
        \Illuminate\Support\Facades\Log::swap($logs);

        $this->post('/demo/key', ['code' => 'SOME-BARE-CODE-123'])->assertRedirect(route('login'));

        $this->assertTrue(session('demo_key_accepted', false));

        $env = (string) file_get_contents($this->envFile);
        $this->assertMatchesRegularExpression('/^DEMO_KEY=\S+$/m', $env);
        preg_match('/^DEMO_KEY=(.*)$/m', $env, $m);
        $this->assertSame('SOME-BARE-CODE-123', DemoKeyVerifier::decodeStoredKey($m[1] ?? null));
        $this->assertStringNotContainsString('DEMO_KEY=SOME-BARE-CODE-123', $env);

        $this->assertNotNull(
            \App\Models\Setting::query()->where('setting_key', DemoKeyVerifier::CACHE_KEY)->value('setting_value')
        );

        $haystack = (string) json_encode($logs->records);
        $this->assertStringNotContainsString('SOME-BARE-CODE-123', $haystack);
        $this->assertStringNotContainsString($doc['signature'], $haystack);
    }

    public function test_key_entry_falls_back_to_session_only_when_env_unwritable(): void
    {
        config(['college.demo_mode' => true]);
        config(['college.demo_key' => null]);
        config(['controlplane.env_path' => sys_get_temp_dir().'/no-such-dir-'.uniqid().'/missing.env']);
        $this->createTestSchool();

        [$doc] = $this->mintDocument('localhost', now()->addMonth()->toDateString());
        $code = (string) json_encode($doc);

        $logs = new \Tests\Support\RecordingLogger();
        \Illuminate\Support\Facades\Log::swap($logs);

        $response = $this->post('/demo/key', ['code' => $code]);
        $response->assertRedirect(route('login'));

        // Valid key is never blocked by a filesystem problem.
        $this->assertTrue(session('demo_key_accepted', false));
        $this->assertSame(
            'key accepted for this session only — could not persist; rotation/reboot will ask again',
            (string) session('demo_key_warning')
        );

        // Cache durability still lands (DB-backed) even when .env cannot.
        $this->assertNotNull(
            \App\Models\Setting::query()->where('setting_key', DemoKeyVerifier::CACHE_KEY)->value('setting_value')
        );

        $haystack = (string) json_encode($logs->records);
        $this->assertStringNotContainsString($doc['signature'], $haystack);
        $this->assertStringNotContainsString($code, $haystack);
    }

    public function test_key_entry_never_persists_wrong_door_token(): void
    {
        config(['college.demo_mode' => true]);
        config(['college.demo_key' => null]);
        $this->createTestSchool();

        $token = 'tok-secret-0123456789abcdefABCDEF0123456789ab';

        $this->post('/demo/key', ['code' => $token])->assertRedirect(route('demo.key.show'));

        $this->assertFalse(session('demo_key_accepted', false));

        $env = (string) file_get_contents($this->envFile);
        $this->assertStringNotContainsString('DEMO_KEY', $env);
        $this->assertStringNotContainsString($token, $env);

        $this->assertNull(
            \App\Models\Setting::query()->where('setting_key', DemoKeyVerifier::CACHE_KEY)->value('setting_value')
        );
    }
}
