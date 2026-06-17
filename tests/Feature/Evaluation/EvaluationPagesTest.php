<?php

declare(strict_types=1);

namespace Tests\Feature\Evaluation;

use App\Livewire\Admin\Staff\EvaluationPreviewPage;
use App\Livewire\Student\StudentEvaluationPerformPage;
use App\Models\EvaluationForm;
use App\Models\EvaluationQuestion;
use App\Models\EvaluationResponse;
use App\Models\Hall;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\ActsAsOwnerAdmin;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class EvaluationPagesTest extends TestCase
{
    use ActsAsOwnerAdmin;
    use CreatesTestSchool;
    use RefreshDatabase;

    public function test_admin_preview_renders_for_form_code(): void
    {
        $admin = $this->actingOwnerAdmin();

        $form = EvaluationForm::query()->create([
            'title' => 'Test form',
            'unique_code' => 'DEMO1234',
            'start_time' => now()->subDay(),
            'end_time' => now()->addMonth(),
            'control_type' => 'auto',
            'is_active' => true,
            'created_by' => $admin->id,
            'last_edited_by' => $admin->id,
        ]);

        EvaluationQuestion::query()->create([
            'form_id' => $form->id,
            'question_text' => 'Quality of teaching?',
            'question_order' => 1,
            'rating_type' => 'scale_5',
            'is_required' => true,
            'created_by' => $admin->id,
            'last_edited_by' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(EvaluationPreviewPage::class, ['form_code' => 'DEMO1234'])
            ->assertSee('Test form', false)
            ->assertSee('Quality of teaching?', false);
    }

    public function test_student_can_submit_evaluation(): void
    {
        $admin = $this->actingOwnerAdmin();

        $hall = Hall::query()->create([
            'name' => 'Hall A',
            'cost' => 0,
            'period' => 'per_year',
        ]);

        $user = User::factory()->create(['type' => 'student', 'username' => 'stu1']);
        Student::query()->forceCreate([
            'user_id' => $user->id,
            'index_number' => 'S001',
            'admission_index' => 'S001',
            'lastname' => 'Test',
            'firstname' => 'Student',
            'date_of_birth' => '2001-01-01',
            'gender' => 'male',
            'nationality' => 'GH',
            'contact_address' => 'Addr',
            'phone_number' => '0240000000',
            'hall_id' => $hall->id,
            'profile_pic' => 'p.png',
            'approved' => true,
            'is_new' => false,
        ]);

        $form = EvaluationForm::query()->create([
            'title' => 'Open form',
            'unique_code' => 'OPEN1234',
            'start_time' => now()->subHour(),
            'end_time' => now()->addMonth(),
            'control_type' => 'auto',
            'is_active' => true,
            'created_by' => $admin->id,
            'last_edited_by' => $admin->id,
        ]);

        EvaluationQuestion::query()->create([
            'form_id' => $form->id,
            'question_text' => 'Rate the course',
            'question_order' => 1,
            'rating_type' => 'scale_5',
            'is_required' => true,
            'created_by' => $admin->id,
            'last_edited_by' => $admin->id,
        ]);

        Livewire::actingAs($user)
            ->test(StudentEvaluationPerformPage::class, ['code' => 'OPEN1234'])
            ->set('answers.'.EvaluationQuestion::query()->where('form_id', $form->id)->value('id'), '4')
            ->call('submit')
            ->assertRedirect(route('student.evaluation', ['tab' => 'completed']));

        $this->assertSame(
            'submitted',
            EvaluationResponse::query()->where('form_id', $form->id)->where('student_id', $user->id)->value('status')
        );
    }

    public function test_admin_can_view_and_filter_evaluation_reporting(): void
    {
        $admin = $this->actingOwnerAdmin();

        $form = EvaluationForm::query()->create([
            'title' => 'Reporting Form',
            'unique_code' => 'REP1234',
            'start_time' => now()->subDay(),
            'end_time' => now()->addMonth(),
            'control_type' => 'auto',
            'is_active' => true,
            'created_by' => $admin->id,
            'last_edited_by' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\Staff\EvaluationManagePage::class, ['form_code' => 'REP1234'])
            ->set('reportView', 'detailed')
            ->set('filterDepartmentId', 1)
            ->set('filterProgramId', 1)
            ->set('filterYearLevel', '200')
            ->call('downloadReport', 'csv')
            ->assertStatus(200);
    }
}
