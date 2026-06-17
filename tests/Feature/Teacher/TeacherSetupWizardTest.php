<?php

namespace Tests\Feature\Teacher;

use App\Livewire\Teacher\TeacherSetupWizard;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class TeacherSetupWizardTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    public function test_teacher_can_complete_setup_with_username_and_password(): void
    {
        $this->createTestSchool();

        $user = User::factory()->create([
            'type' => 'teacher',
            'username' => null,
        ]);

        Teacher::query()->forceCreate([
            'user_id' => $user->id,
            'password_reset_required' => true,
            'is_onboarded' => false,
        ]);

        Livewire::actingAs($user)
            ->test(TeacherSetupWizard::class)
            ->set('username', 'lecturer1')
            ->set('password', 'New-password-1')
            ->set('password_confirmation', 'New-password-1')
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertSame('lecturer1', $user->username);
        $this->assertTrue($user->teacher->fresh()->is_onboarded);
        $this->assertFalse($user->teacher->fresh()->password_reset_required);
    }
}
