<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class SchoolLockupTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    public function test_login_shows_school_lockup_when_ready(): void
    {
        $this->createTestSchool(['name' => 'Acme College', 'ready' => true]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Acme College')
            ->assertSee('Secured portal of');
    }

    public function test_login_falls_back_to_app_brand_when_school_not_ready(): void
    {
        $this->createTestSchool(['name' => 'Acme College', 'ready' => false]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee((string) config('app.name'))
            ->assertDontSee('Acme College')
            ->assertDontSee('Secured portal of');
    }
}
