<?php

namespace Tests\Feature\Route;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class LegacyRouteParityTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    /**
     * Named routes from legacy routes.php (explicit `name` keys).
     */
    public function test_legacy_named_routes_are_registered(): void
    {
        $names = [
            'admin.evaluations',
            'admin.evaluation.preview',
            'admin.evaluation',
            'program.classes',
            'program.manage',
            'student.evaluation.perform',
            'student.evaluation',
        ];

        foreach ($names as $name) {
            $this->assertTrue(Route::has($name), "Missing named route: {$name}");
        }
    }

    public function test_key_legacy_uris_resolve(): void
    {
        $this->assertSame('/shutdown', route('shutdown', [], false));
        $this->assertSame('/send-verification', route('legacy.send-verification', [], false));
        $this->assertSame('/tools/passport-validator', route('tools.passport-validator', [], false));
        $this->assertSame('/teacher/setup', route('teacher.setup', [], false));
    }

    public function test_legacy_token_verify_redirects_to_verification_notice(): void
    {
        $this->createTestSchool();

        $this->get('/verify-email/old-token-format')
            ->assertRedirect(route('verification.notice'));
    }
}
