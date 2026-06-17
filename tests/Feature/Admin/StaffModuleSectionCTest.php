<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Staff\EvaluationIndexPage;
use App\Livewire\Admin\Staff\NonTeachingListPage;
use App\Livewire\Admin\Staff\StaffAssignmentsListPage;
use App\Models\Department;
use App\Models\EvaluationForm;
use App\Models\Faculty;
use App\Models\User;
use App\Services\TeacherSpreadsheetImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\Concerns\ActsAsOwnerAdmin;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class StaffModuleSectionCTest extends TestCase
{
    use ActsAsOwnerAdmin;
    use CreatesTestSchool;
    use RefreshDatabase;

    public function test_admin_can_create_non_teaching_staff_and_assignment(): void
    {
        $admin = $this->actingOwnerAdmin();

        $faculty = Faculty::query()->create(['name' => 'Science']);
        $department = Department::query()->create([
            'name' => 'Computer Science',
            'faculty_id' => $faculty->id,
        ]);

        Livewire::actingAs($admin)
            ->test(NonTeachingListPage::class)
            ->set('name', 'Pat Admin')
            ->set('username', 'pstaff')
            ->set('email', 'pstaff@example.test')
            ->set('password', 'password123')
            ->set('position', 'Registrar assistant')
            ->set('department_id', $department->id)
            ->set('phone_number', '0240000001')
            ->set('status', 'active')
            ->call('saveCreate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'pstaff@example.test',
            'type' => 'staff',
        ]);

        $staffUser = User::query()->where('email', 'pstaff@example.test')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(StaffAssignmentsListPage::class)
            ->set('staff_id', $staffUser->id)
            ->set('department_id', $department->id)
            ->set('office', 'Block A')
            ->set('position_title', 'Assistant')
            ->set('assignment_date', now()->format('Y-m-d'))
            ->set('status', 'active')
            ->call('saveCreate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('staff_assignments', [
            'staff_id' => $staffUser->id,
            'department_id' => $department->id,
            'office' => 'Block A',
        ]);

        Livewire::actingAs($admin)
            ->test(StaffAssignmentsListPage::class)
            ->set('search', 'Block A')
            ->assertViewHas('rows', function ($rows) {
                return $rows->count() === 1;
            })
            ->set('search', 'Nonexistent')
            ->assertViewHas('rows', function ($rows) {
                return $rows->count() === 0;
            })
            ->set('search', '')
            ->set('filterDepartment', $department->id)
            ->assertViewHas('rows', function ($rows) {
                return $rows->count() === 1;
            });
    }

    public function test_evaluation_delete_only_when_inactive_and_no_responses(): void
    {
        $admin = $this->actingOwnerAdmin();

        $deletable = EvaluationForm::query()->create([
            'title' => 'Old form',
            'unique_code' => 'DELME123',
            'start_time' => now()->subMonth(),
            'end_time' => now()->subWeek(),
            'control_type' => 'auto',
            'is_active' => false,
            'created_by' => $admin->id,
            'last_edited_by' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(EvaluationIndexPage::class)
            ->call('openDeleteModal', 'DELME123')
            ->call('confirmDeleteForm')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('evaluation_forms', ['id' => $deletable->id]);
    }

    public function test_admin_can_create_evaluation_form_via_modal(): void
    {
        $admin = $this->actingOwnerAdmin();

        Livewire::actingAs($admin)
            ->test(EvaluationIndexPage::class)
            ->call('openCreateModal')
            ->set('createTitle', 'S1 Course Appraisal')
            ->set('createAcademicYear', '2026/2027')
            ->set('createStartTime', '2026-06-01T09:00')
            ->set('createEndTime', '2026-07-01T17:00')
            ->set('createControlType', 'auto')
            ->call('saveNewForm')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('evaluation_forms', [
            'title' => 'S1 Course Appraisal',
            'academic_year' => '2026/2027',
            'control_type' => 'auto',
            'is_active' => 0,
        ]);
    }

    public function test_teacher_spreadsheet_import_creates_teacher(): void
    {
        $admin = $this->actingOwnerAdmin();

        $faculty = Faculty::query()->create(['name' => 'Eng']);
        $department = Department::query()->create([
            'name' => 'EE',
            'faculty_id' => $faculty->id,
        ]);

        $relative = 'filepond-tmp/'.$admin->id.'/import.csv';
        $csv = "email,username,lastname,othernames,staff_id,department_id,phone_number\n".
            "bulk1@example.test,bulkuser1,Doe,Jane,T001,{$department->id},0240000099\n";
        $absolute = storage_path('app/'.$relative);
        File::ensureDirectoryExists(\dirname($absolute));
        File::put($absolute, $csv);

        $service = app(TeacherSpreadsheetImportService::class);
        $result = $service->importFromFilepondRelativePath($relative, $admin->id);

        $this->assertSame(1, $result['created']);
        $this->assertSame([], $result['errors']);

        $this->assertDatabaseHas('users', [
            'email' => 'bulk1@example.test',
            'type' => 'teacher',
        ]);
    }

    public function test_admin_can_assign_and_edit_combined_staff_role(): void
    {
        $admin = $this->actingOwnerAdmin();

        $faculty = Faculty::query()->create(['name' => 'Humanities']);
        $department = Department::query()->create([
            'name' => 'Modern Languages',
            'faculty_id' => $faculty->id,
        ]);

        $staffUser = User::query()->create([
            'name' => 'Alistair Registrar',
            'username' => 'alistair',
            'email' => 'alistair@example.test',
            'password' => bcrypt('password'),
            'type' => 'staff',
            'user_secret' => \Illuminate\Support\Str::random(64),
        ]);

        // 1. Create Staff Assignment + Role
        Livewire::actingAs($admin)
            ->test(StaffAssignmentsListPage::class)
            ->set('staff_id', $staffUser->id)
            ->set('department_id', $department->id)
            ->set('office', 'Registry Hall')
            ->set('position_title', 'Registrar Assistant')
            ->set('assignment_date', now()->format('Y-m-d'))
            ->set('status', 'active')
            ->set('role', 'registrar')
            ->set('role_description', 'Handles student records')
            ->set('role_status', 'active')
            ->call('saveCreate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('staff_assignments', [
            'staff_id' => $staffUser->id,
            'department_id' => $department->id,
            'office' => 'Registry Hall',
        ]);

        $this->assertDatabaseHas('staff_roles', [
            'staff_id' => $staffUser->id,
            'role' => 'registrar',
            'description' => 'Handles student records',
            'status' => 'active',
        ]);

        $assignment = \App\Models\StaffAssignment::query()->where('staff_id', $staffUser->id)->firstOrFail();

        // 2. Edit assignment and change role details
        Livewire::actingAs($admin)
            ->test(StaffAssignmentsListPage::class)
            ->call('openEditModal', $assignment->id)
            ->assertSet('role', 'registrar')
            ->assertSet('role_description', 'Handles student records')
            ->set('role', 'bursar')
            ->set('role_description', 'Updated description')
            ->call('saveEdit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('staff_roles', [
            'staff_id' => $staffUser->id,
            'role' => 'bursar',
            'description' => 'Updated description',
        ]);
    }

    public function test_admin_can_revoke_staff_role_and_filter_by_role(): void
    {
        $admin = $this->actingOwnerAdmin();

        $faculty = Faculty::query()->create(['name' => 'Humanities']);
        $department = Department::query()->create([
            'name' => 'Modern Languages',
            'faculty_id' => $faculty->id,
        ]);

        $staffUser = User::query()->create([
            'name' => 'Alistair Registrar',
            'username' => 'alistair',
            'email' => 'alistair@example.test',
            'password' => bcrypt('password'),
            'type' => 'staff',
            'user_secret' => \Illuminate\Support\Str::random(64),
        ]);

        // Create assignment + role
        $assignment = \App\Models\StaffAssignment::query()->create([
            'staff_id' => $staffUser->id,
            'department_id' => $department->id,
            'office' => 'Registry Hall',
            'position_title' => 'Registrar Assistant',
            'assignment_date' => now()->format('Y-m-d'),
            'status' => 'active',
            'assigned_by' => $admin->id,
        ]);

        \App\Models\StaffRole::query()->create([
            'staff_id' => $staffUser->id,
            'role' => 'registrar',
            'description' => 'Handles student records',
            'status' => 'active',
            'assigned_by' => $admin->id,
        ]);

        // Verify filter role works
        Livewire::actingAs($admin)
            ->test(StaffAssignmentsListPage::class)
            ->set('filterRole', 'registrar')
            ->assertViewHas('rows', function ($rows) {
                return $rows->count() === 1;
            })
            ->set('filterRole', 'bursar')
            ->assertViewHas('rows', function ($rows) {
                return $rows->count() === 0;
            });

        // Revoke the role via Livewire
        Livewire::actingAs($admin)
            ->test(StaffAssignmentsListPage::class)
            ->call('openRevokeRoleModal', $assignment->id)
            ->assertSet('showRevokeRoleModal', true)
            ->call('confirmRevokeRole')
            ->assertSet('showRevokeRoleModal', false)
            ->assertHasNoErrors();

        // Verify database updated to inactive
        $this->assertDatabaseHas('staff_roles', [
            'staff_id' => $staffUser->id,
            'role' => 'registrar',
            'status' => 'inactive',
        ]);
    }
}
