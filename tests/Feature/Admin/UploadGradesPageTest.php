<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Grading\UploadGradesPage;
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
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Concerns\ActsAsOwnerAdmin;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class UploadGradesPageTest extends TestCase
{
    use ActsAsOwnerAdmin;
    use CreatesTestSchool;
    use RefreshDatabase;

    public function test_admin_can_analyze_and_import_grades(): void
    {
        Storage::fake('local');

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
            'firstname' => 'Test',
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

        // Create a dummy Excel file in local storage
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Metadata');
        $sheet->setCellValue('A2', 'Index Number');
        $sheet->setCellValue('B2', 'Student Name');
        $sheet->setCellValue('C2', 'Attendance');
        $sheet->setCellValue('D2', 'Midsem');
        $sheet->setCellValue('E2', 'Project');
        $sheet->setCellValue('F2', 'Exam');

        // Row 3: student data
        $sheet->setCellValue('A3', 'STU900');
        $sheet->setCellValue('B3', 'Test Student');
        $sheet->setCellValue('C3', '8');
        $sheet->setCellValue('D3', '15');
        $sheet->setCellValue('E3', '7');
        $sheet->setCellValue('F3', '55');

        $tmpDir = 'filepond-tmp/' . $admin->id;
        Storage::disk('local')->makeDirectory($tmpDir);
        $tempPath = $tmpDir . '/test.xlsx';
        $fullPath = Storage::disk('local')->path($tempPath);

        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);

        // Run the Livewire test
        Livewire::actingAs($admin)
            ->test(UploadGradesPage::class)
            ->set('academicSessionId', $session->id)
            ->set('teacherId', $teacher->id)
            ->set('programId', $program->id)
            ->set('courseId', $course->id)
            ->set('level', 100)
            ->set('spreadsheetPond', $tempPath)
            ->call('analyze')
            ->assertHasNoErrors()
            ->assertSet('detectedRows', 3)
            ->call('confirmUpload')
            ->assertHasNoErrors();

        // Assert grades and results are created
        $result = Result::query()->where('student_id', $student->id)->where('course_id', $course->id)->first();
        $this->assertNotNull($result);
        $this->assertSame(85.0, (float) $result->score);
        $this->assertSame('A', $result->grade);

        $grade = Grade::query()->where('student_id', $student->id)->first();
        $this->assertNotNull($grade);
        $this->assertSame(8.0, (float) $grade->attendance_score);
        $this->assertSame(15.0, (float) $grade->midsem_score);
        $this->assertSame(7.0, (float) $grade->project_score);
        $this->assertSame(55.0, (float) $grade->exam_score);
    }

    public function test_attempt_upload_dispatches_confirm_modal_if_grades_exist(): void
    {
        Storage::fake('local');

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

        // Pre-create the ResultSlip to trigger the existing warning condition
        \App\Models\ResultSlip::query()->create([
            'teacher_id' => $teacher->id,
            'program_id' => $program->id,
            'course_id' => $course->id,
            'academic_session_id' => $session->id,
            'level' => '100',
            'semester' => 1,
            'status' => 'draft',
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
            'firstname' => 'Test',
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

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Metadata');
        $sheet->setCellValue('A2', 'Index Number');
        $sheet->setCellValue('B2', 'Student Name');
        $sheet->setCellValue('C2', 'Attendance');
        $sheet->setCellValue('D2', 'Midsem');
        $sheet->setCellValue('E2', 'Project');
        $sheet->setCellValue('F2', 'Exam');
        $sheet->setCellValue('A3', 'STU900');
        $sheet->setCellValue('B3', 'Test Student');
        $sheet->setCellValue('C3', '8');
        $sheet->setCellValue('D3', '15');
        $sheet->setCellValue('E3', '7');
        $sheet->setCellValue('F3', '55');

        $tmpDir = 'filepond-tmp/' . $admin->id;
        Storage::disk('local')->makeDirectory($tmpDir);
        $tempPath = $tmpDir . '/test.xlsx';
        $fullPath = Storage::disk('local')->path($tempPath);

        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);

        Livewire::actingAs($admin)
            ->test(UploadGradesPage::class)
            ->set('academicSessionId', $session->id)
            ->set('teacherId', $teacher->id)
            ->set('programId', $program->id)
            ->set('courseId', $course->id)
            ->set('level', 100)
            ->set('spreadsheetPond', $tempPath)
            ->call('analyze')
            ->assertHasNoErrors()
            ->call('attemptUpload')
            ->assertDispatched('open-modal', 'confirm-overwrite-modal')
            ->call('confirmUpload')
            ->assertHasNoErrors()
            ->assertSet('spreadsheetPond', null)
            ->assertSet('previewRows', [])
            ->assertDispatched('clear-filepond');
    }
}
