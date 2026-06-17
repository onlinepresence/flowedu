<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Grading\EnterGradesPage;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Grade;
use App\Models\GradePoint;
use App\Models\Hall;
use App\Models\Program;
use App\Models\Result;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\ActsAsOwnerAdmin;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class EnterGradesPageTest extends TestCase
{
    use ActsAsOwnerAdmin;
    use CreatesTestSchool;
    use RefreshDatabase;

    public function test_admin_can_save_result_score_via_livewire(): void
    {
        $admin = $this->actingOwnerAdmin();

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
            'staff_id' => 'TCH001',
            'lastname' => 'Lecturer',
            'othernames' => 'One',
            'gender' => 'male',
            'phone_number' => '0240000001',
            'nationality' => 'GH',
        ]);

        $course = Course::query()->forceCreate([
            'code' => 'CS101',
            'name' => 'Computing',
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
            'course_semester' => '1',
            'year_level' => '1',
        ]);

        TeacherAssignment::query()->create([
            'teacher_id' => $teacher->id,
            'program_id' => $program->id,
            'course_id' => $course->id,
            'session_id' => $session->id,
            'level' => 100,
        ]);

        GradePoint::query()->create([
            'grade' => 'A',
            'points' => 4.0,
            'min_score' => 80.0,
            'max_score' => 100.0,
        ]);
        GradePoint::query()->create([
            'grade' => 'B',
            'points' => 3.0,
            'min_score' => 60.0,
            'max_score' => 79.99,
        ]);

        $hall = Hall::query()->create([
            'name' => 'Hall',
            'cost' => 0,
            'period' => 'per_year',
        ]);

        $user = User::factory()->create(['type' => 'student']);
        $student = Student::query()->forceCreate([
            'user_id' => $user->id,
            'index_number' => 'STU900',
            'admission_index' => 'STU900',
            'lastname' => 'Student',
            'current_year' => '100',
            'date_of_birth' => '2002-01-01',
            'gender' => 'male',
            'nationality' => 'GH',
            'contact_address' => 'Addr',
            'phone_number' => '0240000999',
            'hall_id' => $hall->id,
            'profile_pic' => 'p.png',
            'approved' => true,
            'program_id' => $program->id,
        ]);

        Livewire::actingAs($admin)
            ->test(EnterGradesPage::class)
            ->set('academicSessionId', $session->id)
            ->set('teacherId', $teacher->id)
            ->set('programId', $program->id)
            ->set('semester', '1')
            ->set('courseId', $course->id)
            ->set('level', 100)
            ->call('loadStudentsAndScores')
            ->set('scores.'.$student->id.'.attendance', '8')
            ->set('scores.'.$student->id.'.midsem', '15')
            ->set('scores.'.$student->id.'.project', '7')
            ->set('scores.'.$student->id.'.exam', '45')
            ->call('saveScores')
            ->assertHasNoErrors();

        $result = Result::query()->where('student_id', $student->id)->where('course_id', $course->id)->first();
        $this->assertNotNull($result);
        $this->assertSame(75.0, (float) $result->score);
        $this->assertSame('B', $result->grade);
        $this->assertSame($admin->id, (int) $result->entered_by);
    }

    public function test_save_result_rejects_score_above_max(): void
    {
        $admin = $this->actingOwnerAdmin();

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
            'staff_id' => 'TCH001',
            'lastname' => 'Lecturer',
            'othernames' => 'One',
            'gender' => 'male',
            'phone_number' => '0240000001',
            'nationality' => 'GH',
        ]);

        $course = Course::query()->forceCreate([
            'code' => 'CS101',
            'name' => 'Computing',
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
            'course_semester' => '1',
            'year_level' => '1',
        ]);

        TeacherAssignment::query()->create([
            'teacher_id' => $teacher->id,
            'program_id' => $program->id,
            'course_id' => $course->id,
            'session_id' => $session->id,
            'level' => 100,
        ]);

        GradePoint::query()->create([
            'grade' => 'A',
            'points' => 4.0,
            'min_score' => 80.0,
            'max_score' => 100.0,
        ]);

        $hall = Hall::query()->create([
            'name' => 'Hall',
            'cost' => 0,
            'period' => 'per_year',
        ]);

        $user = User::factory()->create(['type' => 'student']);
        $student = Student::query()->forceCreate([
            'user_id' => $user->id,
            'index_number' => 'STU901',
            'admission_index' => 'STU901',
            'lastname' => 'Student',
            'current_year' => '100',
            'date_of_birth' => '2002-01-01',
            'gender' => 'male',
            'nationality' => 'GH',
            'contact_address' => 'Addr',
            'phone_number' => '0240000998',
            'hall_id' => $hall->id,
            'profile_pic' => 'p.png',
            'approved' => true,
            'program_id' => $program->id,
        ]);

        Livewire::actingAs($admin)
            ->test(EnterGradesPage::class)
            ->set('academicSessionId', $session->id)
            ->set('teacherId', $teacher->id)
            ->set('programId', $program->id)
            ->set('semester', '1')
            ->set('courseId', $course->id)
            ->set('level', 100)
            ->call('loadStudentsAndScores')
            ->set('scores.'.$student->id.'.exam', '65') // max exam is 60
            ->call('saveScores')
            ->assertHasErrors(['scores.'.$student->id.'.exam']);

        $result = Result::query()->where('student_id', $student->id)->where('course_id', $course->id)->first();
        $this->assertNull($result);
    }

    public function test_can_save_results_as_draft_and_resubmit_rejected(): void
    {
        $admin = $this->actingOwnerAdmin();

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

        $session = AcademicSession::query()->create([
            'name' => '2025/26',
            'start_date' => '2025-09-01',
            'end_date' => '2026-06-30',
            'is_current' => true,
        ]);

        $teacherUser = User::factory()->create(['type' => 'teacher']);
        $teacher = Teacher::query()->forceCreate([
            'user_id' => $teacherUser->id,
            'staff_id' => 'TCH001',
            'lastname' => 'Lecturer',
            'othernames' => 'One',
            'gender' => 'male',
            'phone_number' => '0240000001',
            'nationality' => 'GH',
        ]);

        $course = Course::query()->forceCreate([
            'code' => 'CS101',
            'name' => 'Computing',
            'program_id' => $program->id,
            'teacher_id' => $teacher->id,
            'course_semester' => '1',
            'year_level' => '1',
        ]);

        TeacherAssignment::query()->create([
            'teacher_id' => $teacher->id,
            'program_id' => $program->id,
            'course_id' => $course->id,
            'session_id' => $session->id,
            'level' => 100,
        ]);

        GradePoint::query()->create([
            'grade' => 'A',
            'points' => 4.0,
            'min_score' => 80.0,
            'max_score' => 100.0,
        ]);

        $hall = Hall::query()->create([
            'name' => 'Hall',
            'cost' => 0,
            'period' => 'per_year',
        ]);

        $user = User::factory()->create(['type' => 'student']);
        $student = Student::query()->forceCreate([
            'user_id' => $user->id,
            'index_number' => 'STU902',
            'admission_index' => 'STU902',
            'lastname' => 'Student',
            'current_year' => '100',
            'date_of_birth' => '2002-01-01',
            'gender' => 'male',
            'nationality' => 'GH',
            'contact_address' => 'Addr',
            'phone_number' => '0240000997',
            'hall_id' => $hall->id,
            'profile_pic' => 'p.png',
            'approved' => true,
            'program_id' => $program->id,
        ]);

        // 1. Save as Draft
        Livewire::actingAs($admin)
            ->test(EnterGradesPage::class)
            ->set('academicSessionId', $session->id)
            ->set('teacherId', $teacher->id)
            ->set('programId', $program->id)
            ->set('semester', '1')
            ->set('courseId', $course->id)
            ->set('level', 100)
            ->call('loadStudentsAndScores')
            ->set('scores.'.$student->id.'.attendance', '9')
            ->set('scores.'.$student->id.'.exam', '50')
            ->call('saveScores', true) // isDraft = true
            ->assertHasNoErrors();

        $slip = \App\Models\ResultSlip::query()->where('course_id', $course->id)->first();
        $this->assertNotNull($slip);
        $this->assertSame('draft', $slip->status);
        
        $grade = Grade::query()->where('student_id', $student->id)->where('result_slip_id', $slip->id)->first();
        $this->assertNotNull($grade);
        $this->assertNull(Result::query()->where('student_id', $student->id)->where('course_id', $course->id)->first());

        // 2. Lock status - pending awaiting approval is NOT editable
        // Set slip status to pending manually to check editable property
        $slip->update(['status' => 'pending']);

        $comp = Livewire::actingAs($admin)
            ->test(EnterGradesPage::class)
            ->set('academicSessionId', $session->id)
            ->set('teacherId', $teacher->id)
            ->set('programId', $program->id)
            ->set('semester', '1')
            ->set('courseId', $course->id)
            ->set('level', 100)
            ->call('loadStudentsAndScores');

        $scoresArray = $comp->get('scores');
        $this->assertFalse($scoresArray[$student->id]['is_editing']);

        // 3. Rejected result - NOT editable until teacher clicks Edit
        $slip->update(['status' => 'rejected', 'review_comments' => 'Invalid exam score']);

        $comp = Livewire::actingAs($admin)
            ->test(EnterGradesPage::class)
            ->set('academicSessionId', $session->id)
            ->set('teacherId', $teacher->id)
            ->set('programId', $program->id)
            ->set('semester', '1')
            ->set('courseId', $course->id)
            ->set('level', 100)
            ->call('loadStudentsAndScores');

        $scoresArray = $comp->get('scores');
        $this->assertFalse($scoresArray[$student->id]['is_editing']);
        $this->assertSame('Invalid exam score', $scoresArray[$student->id]['review_comments']);

        // Enable edit
        $comp->call('convertToDraft');
        $scoresArray = $comp->get('scores');
        $this->assertTrue($scoresArray[$student->id]['is_editing']);

        // Resubmit -> status changes back to pending (if teacher, but here we act as admin so it will be approved. Let's make it teacher to verify)
        Livewire::actingAs($teacherUser)
            ->test(EnterGradesPage::class)
            ->set('academicSessionId', $session->id)
            ->set('programId', $program->id)
            ->set('semester', '1')
            ->set('courseId', $course->id)
            ->set('level', 100)
            ->call('loadStudentsAndScores')
            ->call('convertToDraft')
            ->set('scores.'.$student->id.'.exam', '55')
            ->call('saveScores', false) // submit for approval
            ->assertHasNoErrors();

        $slip->refresh();
        $this->assertSame('pending', $slip->status);
        $this->assertNull($slip->review_comments); // comments cleared on resubmission
    }
}
