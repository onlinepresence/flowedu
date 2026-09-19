<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Grading\ApproveGradesPage;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Grade;
use App\Models\GradePoint;
use App\Models\Hall;
use App\Models\Program;
use App\Models\ResultSlip;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\ActsAsOwnerAdmin;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class ApproveGradesPageTest extends TestCase
{
    use ActsAsOwnerAdmin;
    use CreatesTestSchool;
    use RefreshDatabase;

    public function test_pending_cohort_modal_survives_follow_up_roundtrips(): void
    {
        // Regression: cohort modal on a PENDING slip (grades entered, no
        // Result rows yet) crashed with "read property course on null" on the
        // second Livewire roundtrip, because per-row display state was stapled
        // onto Eloquent models and lost on dehydration.
        $admin = $this->actingOwnerAdmin();
        $this->createTestSchool();

        $faculty = Faculty::query()->create(['name' => 'Science']);
        $department = Department::query()->forceCreate([
            'name' => 'CS',
            'faculty_id' => $faculty->id,
        ]);
        $program = Program::query()->forceCreate([
            'name' => 'BSc CS',
            'department_id' => $department->id,
            'certificate' => 'Degree',
            'cost' => 0,
        ]);

        $session = new AcademicSession;
        $session->forceFill([
            'name' => '2025/26',
            'start_date' => '2025-09-01',
            'end_date' => '2026-06-30',
            'is_current' => true,
        ]);
        $session->save();

        $teacherUser = User::factory()->create(['type' => 'teacher']);
        $teacher = Teacher::query()->forceCreate([
            'user_id' => $teacherUser->id,
            'staff_id' => 'TCH901',
            'lastname' => 'Lecturer',
            'othernames' => 'Nine',
            'gender' => 'male',
            'phone_number' => '0240000901',
            'nationality' => 'GH',
        ]);

        $course = Course::query()->forceCreate([
            'code' => 'CS901',
            'name' => 'Testing',
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
            'course_semester' => '1',
            'year_level' => '1',
        ]);

        GradePoint::query()->create([
            'grade' => 'A',
            'points' => 4.0,
            'min_score' => 80.0,
            'max_score' => 100.0,
        ]);
        GradePoint::query()->create([
            'grade' => 'F',
            'points' => 0.0,
            'min_score' => 0.0,
            'max_score' => 79.99,
        ]);

        $hall = Hall::query()->create([
            'name' => 'Hall 901',
            'cost' => 0,
            'period' => 'per_year',
        ]);

        $user = User::factory()->create(['type' => 'student']);
        $student = Student::query()->forceCreate([
            'user_id' => $user->id,
            'index_number' => 'STU901',
            'admission_index' => 'STU901',
            'lastname' => 'Student',
            'firstname' => 'Nina',
            'date_of_birth' => '2002-02-02',
            'gender' => 'female',
            'nationality' => 'GH',
            'contact_address' => 'Addr 901',
            'phone_number' => '0240000902',
            'approved' => true,
            'department_id' => $department->id,
            'program_id' => $program->id,
            'hall_id' => $hall->id,
            'profile_pic' => 'placeholder.png',
        ]);

        $slip = ResultSlip::query()->create([
            'teacher_id' => $teacher->id,
            'program_id' => $program->id,
            'course_id' => $course->id,
            'academic_session_id' => $session->id,
            'level' => '100',
            'semester' => 1,
            'status' => 'pending',
        ]);

        Grade::query()->create([
            'result_slip_id' => $slip->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'attendance_score' => 8,
            'midsem_score' => 15,
            'project_score' => 7,
            'class_score' => 30,
            'exam_score' => 45,
        ]);

        Livewire::actingAs($admin)
            ->test(ApproveGradesPage::class)
            ->call('viewCohort', $teacher->id, $course->id, $session->id, $program->id, '100')
            ->assertSee('CS901')
            ->assertSee('75')
            // Second roundtrip re-renders from dehydrated models; the modal
            // must not crash on grades that have no Result row yet.
            ->set('searchLecturer', 'zzz')
            ->assertOk()
            ->assertSee('CS901')
            ->assertSee('75');
    }
}
