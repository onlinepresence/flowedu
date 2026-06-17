<?php

namespace Tests\Feature\College;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class CollegeBootstrapAndAdmissionTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    public function test_login_redirects_to_register_when_no_school_exists(): void
    {
        $this->get('/login')
            ->assertRedirect(route('register'));

        $this->assertTrue(session('admin_register'));
    }

    public function test_register_page_renders_when_no_school_exists(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSeeVolt('pages.auth.register');
    }

    public function test_first_admin_registration_with_valid_secret(): void
    {
        session(['admin_register' => true]);

        $component = Volt::test('pages.auth.register')
            ->set('email', 'owner@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('system_secret', config('college.system_registration_secret'));

        $component->call('register');

        $component->assertRedirect(route('admin.setup.personal', absolute: false));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'owner@example.com',
            'type' => 'admin',
        ]);
    }

    public function test_first_admin_registration_rejects_invalid_secret(): void
    {
        session(['admin_register' => true]);

        $component = Volt::test('pages.auth.register')
            ->set('email', 'owner@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('system_secret', 'wrong-secret');

        $component->call('register');

        $component->assertHasErrors('system_secret');
        $this->assertGuest();
    }

    public function test_register_redirects_to_login_when_admissions_closed_and_not_bootstrap(): void
    {
        $this->createTestSchool(['is_admit' => false]);

        $this->get('/register')
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');
    }

    public function test_register_allowed_when_admissions_closed_but_admin_register_session(): void
    {
        $this->createTestSchool(['is_admit' => false]);

        $this->withSession(['admin_register' => true])
            ->get('/register')
            ->assertOk();
    }
}
