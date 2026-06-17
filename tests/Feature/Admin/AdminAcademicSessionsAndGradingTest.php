<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Academic\SessionIndex;
use App\Models\Admin;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\AdminSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class AdminAcademicSessionsAndGradingTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    private function actingAdmin(): User
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();

        $roleId = UserRole::query()->where('name', 'owner')->value('id');
        $user = User::factory()->create([
            'type' => 'admin',
            'username' => 'testadmin',
        ]);

        $admin = new Admin;
        $admin->user_id = $user->id;
        $admin->type = $roleId;
        $admin->save();

        return $user;
    }

    public function test_approve_grades_page_shows_enter_and_upload_ctas(): void
    {
        $user = $this->actingAdmin();

        $response = $this->actingAs($user)->get(route('admin.grading.approve'));

        $response->assertOk();
        $response->assertSee(__('Enter results'), false);
        $response->assertSee(__('Upload results'), false);
    }

    public function test_grading_enter_and_upload_routes_return_ok_for_admin(): void
    {
        $user = $this->actingAdmin();

        $this->actingAs($user)->get(route('admin.grading.enter'))->assertOk();
        $this->actingAs($user)->get(route('admin.grading.upload'))->assertOk();
    }

    public function test_admin_can_create_academic_session_via_livewire(): void
    {
        $user = $this->actingAdmin();

        Livewire::actingAs($user)
            ->test(SessionIndex::class)
            ->set('formName', '2025/2026')
            ->set('formStartDate', '2025-09-01')
            ->set('formEndDate', '2026-08-31')
            ->set('formIsCurrent', true)
            ->set('semesterRows', [
                [
                    'name' => 'Semester 1',
                    'start_date' => '2025-09-01',
                    'end_date' => '2026-01-15',
                    'is_active' => true,
                ],
            ])
            ->call('saveSession')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('academic_sessions', [
            'name' => '2025/2026',
            'is_current' => true,
        ]);

        $this->assertDatabaseHas('semesters', [
            'name' => 'Semester 1',
            'is_active' => true,
        ]);
    }

    public function test_admin_cannot_edit_or_reactivate_old_academic_session(): void
    {
        $user = $this->actingAdmin();

        $oldSession = \App\Models\AcademicSession::query()->create([
            'name' => '2020/2021',
            'start_date' => '2020-09-01',
            'end_date' => '2021-08-31',
            'is_current' => false,
        ]);

        Livewire::actingAs($user)
            ->test(SessionIndex::class)
            ->call('openEdit', $oldSession->id)
            ->assertDispatched('college-toast')
            ->assertSet('editingId', null);

        Livewire::actingAs($user)
            ->test(SessionIndex::class)
            ->call('setCurrent', $oldSession->id)
            ->assertDispatched('college-toast');

        $this->assertFalse($oldSession->fresh()->is_current);
    }

    public function test_admin_cannot_delete_started_academic_session(): void
    {
        $user = $this->actingAdmin();

        $startedSession = \App\Models\AcademicSession::query()->create([
            'name' => '2023/2024',
            'start_date' => now()->subMonths(3)->format('Y-m-d'),
            'end_date' => now()->addMonths(9)->format('Y-m-d'),
            'is_current' => false,
        ]);

        Livewire::actingAs($user)
            ->test(SessionIndex::class)
            ->call('confirmDeleteSession', $startedSession->id)
            ->assertDispatched('college-toast');

        $this->assertDatabaseHas('academic_sessions', ['id' => $startedSession->id]);
    }

    public function test_admin_can_delete_unstarted_academic_session(): void
    {
        $user = $this->actingAdmin();

        $futureSession = \App\Models\AcademicSession::query()->create([
            'name' => '2030/2031',
            'start_date' => now()->addYears(3)->format('Y-m-d'),
            'end_date' => now()->addYears(4)->format('Y-m-d'),
            'is_current' => false,
        ]);

        Livewire::actingAs($user)
            ->test(SessionIndex::class)
            ->call('confirmDeleteSession', $futureSession->id)
            ->assertSet('deletingSessionId', $futureSession->id)
            ->call('deleteSession')
            ->assertDispatched('college-toast');

        $this->assertDatabaseMissing('academic_sessions', ['id' => $futureSession->id]);
    }
}

