<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_flagged_users_are_redirected_to_the_change_screen(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('password.change'));
        $this->actingAs($user)->get('/licence-required')->assertRedirect(route('password.change'));
        $this->actingAs($user)->get(route('password.change'))->assertOk();
    }

    public function test_flagged_users_can_reach_profile_and_logout(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)->get('/profile')->assertOk();

        $this->actingAs($user)->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_flagged_livewire_calls_are_blocked(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)
            ->postJson('/livewire/update', ['components' => []])
            ->assertForbidden()
            ->assertJsonFragment(['message' => 'Password change required.']);
    }

    public function test_unflagged_users_are_unaffected(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('post.login.redirect'));
        $this->actingAs($user)->get('/profile')->assertOk();
        $this->actingAs($user)->get(route('password.change'))->assertOk();
    }

    public function test_flag_clears_only_after_self_initiated_change(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
        ])->assertRedirect(route('dashboard'));

        $this->assertFalse($user->refresh()->requiresPasswordChange());

        // Gate now passes: normal dashboard flow resumes.
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('post.login.redirect'));
    }

    public function test_flag_clears_via_profile_password_form(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user);

        Volt::test('profile.update-password-form')
            ->set('current_password', 'password')
            ->set('password', 'new-secret-123')
            ->set('password_confirmation', 'new-secret-123')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertFalse($user->refresh()->requiresPasswordChange());
    }

    public function test_wrong_current_password_keeps_flag(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue($user->refresh()->requiresPasswordChange());
    }
}
