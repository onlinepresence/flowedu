<?php

namespace Tests\Feature\College;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class ServerDownMiddlewareTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    public function test_server_down_redirects_to_shutdown_except_shutdown_and_up(): void
    {
        $this->createTestSchool();
        config(['college.server_down' => true]);

        $this->get('/')
            ->assertRedirect(route('shutdown'));

        $this->get('/shutdown')
            ->assertOk();

        $this->get('/up')
            ->assertOk();
    }

    public function test_server_down_disabled_allows_home(): void
    {
        $this->createTestSchool();
        config(['college.server_down' => false]);

        $this->get('/')
            ->assertRedirect(route('login'));
    }
}
