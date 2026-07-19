<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Staff\EvaluationIndexPage;
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
}
