<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Jobs\SendVerificationEmailJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Volt\Volt;
use Tests\Concerns\ActsAsOwnerAdmin;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class EmailVerificationBannerTest extends TestCase
{
    use ActsAsOwnerAdmin;
    use CreatesTestSchool;
    use RefreshDatabase;

    public function test_register_queues_verification_email(): void
    {
        $this->createTestSchool();
        Queue::fake();

        Volt::test('pages.auth.register')
            ->set('email', 'verify-me@example.test')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register');

        $user = User::query()->where('email', 'verify-me@example.test')->firstOrFail();
        $this->assertNull($user->email_verified_at);
        Queue::assertPushed(SendVerificationEmailJob::class, fn (SendVerificationEmailJob $job) => $job->userId === $user->id);
    }

    public function test_resend_endpoint_queues_job_for_unverified(): void
    {
        Queue::fake();
        $owner = $this->actingOwnerAdmin();
        $owner->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($owner)
            ->post(route('verification.send'))
            ->assertRedirect()
            ->assertSessionHas('status', 'verification-link-sent');

        Queue::assertPushed(SendVerificationEmailJob::class, fn (SendVerificationEmailJob $job) => $job->userId === $owner->id);
    }

    public function test_resend_endpoint_skips_verified_users(): void
    {
        Queue::fake();
        $owner = $this->actingOwnerAdmin();

        $this->actingAs($owner)
            ->post(route('verification.send'))
            ->assertRedirect(route('dashboard', absolute: false));

        Queue::assertNotPushed(SendVerificationEmailJob::class);
    }

    public function test_banner_shows_for_unverified_post_onboarding_admin(): void
    {
        $owner = $this->actingOwnerAdmin();
        $owner->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($owner)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Resend verification email');
    }

    public function test_banner_hidden_for_verified_admin(): void
    {
        $owner = $this->actingOwnerAdmin();

        $this->actingAs($owner)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Resend verification email');
    }
}
