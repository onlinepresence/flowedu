<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    /**
     * Home redirects guests to login when a school exists (legacy `/` was login).
     */
    public function test_the_application_home_redirects_guests_to_login(): void
    {
        $this->createTestSchool();

        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
