<?php

declare(strict_types=1);

namespace App\Livewire\Teacher;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Student;
use App\Models\TeacherAssignment;
use App\Models\TeacherAttendanceSheet;
use App\Support\FilepondPendingFile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;

class TeacherAttendancePage extends Component
{
    use DispatchesCollegeToasts;

    public ?int $courseId = null;
    public string $classDate = '';
    public ?string $sheetPond = null;

    // Manual marking properties
    public bool $showMarkAttendanceModal = false;
    public ?int $markCourseId = null;
    public string $markClassDate = '';
    public array $attendanceData = [];

    public function mount(): void
    {
        $this->classDate = now()->format('Y-m-d');
        $this->markClassDate = now()->format('Y-m-d');
    }

    public function updatedCourseId(mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }
        $this->sheetPond = null;
        session()->forget('teacher_attendance_sheet_tmp');
    }

    public function updatedSheetPond(?string $value): void
    {
        if ($value === null || $value === '') {
            session()->forget('teacher_attendance_sheet_tmp');

            return;
        }

        $userId = Auth::id();
        if ($userId !== null && FilepondPendingFile::assertOwnedPendingPath($value, $userId)) {
            session(['teacher_attendance_sheet_tmp' => $value]);
        }
    }

    // Modal Control & Cohort Loading
    public function openMarkAttendanceModal(): void
    {
        $this->markCourseId = null;
        $this->markClassDate = now()->format('Y-m-d');
        $this->attendanceData = [];
        $this->showMarkAttendanceModal = true;
    }

    public function updatedMarkCourseId(mixed $value): void
    {
        $this->attendanceData = [];
        if ($value === null || $value === '') {
            return;
        }

        $course = Course::find((int) $value);
        $teacher = auth()->user()?->teacher;
        if ($course === null || $teacher === null) {
            return;
        }

        $activeSession = AcademicSession::query()->where('is_active', true)->first();
        if ($activeSession === null) {
            $activeSession = AcademicSession::query()->orderByDesc('id')->first();
        }

        // Get matching assignment to identify exact program and level cohort
        $asg = TeacherAssignment::query()
            ->where('teacher_id', $teacher->id)
            ->where('session_id', $activeSession->id)
            ->where('course_id', $course->id)
            ->first();

        if ($asg !== null) {
            $students = Student::query()
                ->where('program_id', $asg->program_id)
                ->where('current_year', (string) $asg->level)
                ->where('approved', true)
                ->orderBy('lastname')
                ->orderBy('firstname')
                ->get();

            foreach ($students as $student) {
                $this->attendanceData[$student->id] = [
                    'index_number' => $student->index_number,
                    'name' => $student->lastname . ', ' . $student->firstname . ' ' . ($student->othernames ?? ''),
                    'present' => true,
                ];
            }
        }
    }

    // Submit Manual Attendance
    public function submitManualAttendance(): void
    {
        $teacher = auth()->user()?->teacher;
        if ($teacher === null) {
            return;
        }

        $allowedIds = Course::query()->where('teacher_id', $teacher->id)->pluck('id')->all();

        $this->validate([
            'markCourseId' => ['required', 'integer', Rule::in($allowedIds)],
            'markClassDate' => ['required', 'date'],
        ]);

        if (empty($this->attendanceData)) {
            $this->collegeToast(__('No students found in the selected course cohort to mark.'), 'error');
            return;
        }

        // Compile CSV
        $csvHeader = "Index Number,Student Name,Attendance Status (Present/Absent),Date\n";
        $csvBody = '';
        foreach ($this->attendanceData as $studentId => $data) {
            $status = $data['present'] ? 'Present' : 'Absent';
            $csvBody .= "{$data['index_number']},\"{$data['name']}\",{$status},{$this->markClassDate}\n";
        }
        $csvContent = $csvHeader . $csvBody;

        $course = Course::find($this->markCourseId);
        $courseCode = $course ? $course->code : 'course';
        $filename = "Attendance_Manual_{$courseCode}_" . str_replace('-', '', $this->markClassDate) . '_' . time() . '.csv';
        $path = "teachers/attendance-sheets/{$filename}";

        // Save generated CSV file to disk
        Storage::disk('college_uploads')->put($path, $csvContent);

        // Record sheet entry in DB
        TeacherAttendanceSheet::query()->create([
            'teacher_id' => $teacher->id,
            'course_id' => $this->markCourseId,
            'class_date' => $this->markClassDate,
            'file_path' => $path,
            'original_name' => $filename,
        ]);

        $this->showMarkAttendanceModal = false;
        $this->collegeToast(__('Attendance logged and sheet compiled successfully.'));
    }

    // Dynamic CSV Template Download
    public function downloadTemplate(): mixed
    {
        $teacher = auth()->user()?->teacher;
        if ($teacher === null) {
            return null;
        }

        $allowedIds = Course::query()->where('teacher_id', $teacher->id)->pluck('id')->all();

        $this->validate([
            'courseId' => ['required', 'integer', Rule::in($allowedIds)],
        ]);

        $course = Course::find($this->courseId);
        if ($course === null) {
            return null;
        }

        $activeSession = AcademicSession::query()->where('is_active', true)->first();
        if ($activeSession === null) {
            $activeSession = AcademicSession::query()->orderByDesc('id')->first();
        }

        $asg = TeacherAssignment::query()
            ->where('teacher_id', $teacher->id)
            ->where('session_id', $activeSession->id)
            ->where('course_id', $course->id)
            ->first();

        if ($asg === null) {
            $this->collegeToast(__('No active class assignment found for this course.'), 'warning');
            return null;
        }

        $students = Student::query()
            ->where('program_id', $asg->program_id)
            ->where('current_year', (string) $asg->level)
            ->where('approved', true)
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();

        if ($students->isEmpty()) {
            $this->collegeToast(__('No students are enrolled in this class cohort to template.'), 'warning');
            return null;
        }

        // Generate template layout
        $csvHeader = "Index Number,Student Name,Status (Present/Absent)\n";
        $csvBody = '';
        foreach ($students as $student) {
            $name = "{$student->lastname}, {$student->firstname} " . ($student->othernames ?? '');
            $csvBody .= "{$student->index_number},\"{$name}\",\n";
        }
        $csvContent = $csvHeader . $csvBody;
        $filename = "Roster_Template_{$course->code}.csv";

        return response()->streamDownload(function () use ($csvContent): void {
            echo $csvContent;
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function submitSheet(): void
    {
        $teacher = auth()->user()?->teacher;
        if ($teacher === null) {
            return;
        }

        $allowedIds = Course::query()->where('teacher_id', $teacher->id)->pluck('id')->all();

        $this->validate([
            'courseId' => ['required', 'integer', Rule::in($allowedIds)],
            'classDate' => ['required', 'date'],
            'sheetPond' => ['required', 'string', 'max:500'],
        ]);

        $userId = Auth::id();
        if ($userId === null || ! FilepondPendingFile::assertOwnedPendingPath($this->sheetPond, $userId)) {
            $this->addError('sheetPond', __('Uploaded file is invalid or expired. Please upload again.'));

            return;
        }

        $fullPath = Storage::disk('local')->path($this->sheetPond);
        $originalName = basename($this->sheetPond);
        $file = new UploadedFile(
            $fullPath,
            $originalName,
            mime_content_type($fullPath) ?: null,
            null,
            true
        );

        $stored = $file->store('teachers/attendance-sheets', 'college_uploads');
        Storage::disk('local')->delete($this->sheetPond);

        TeacherAttendanceSheet::query()->create([
            'teacher_id' => $teacher->id,
            'course_id' => $this->courseId,
            'class_date' => $this->classDate,
            'file_path' => $stored,
            'original_name' => $originalName,
        ]);

        session()->forget('teacher_attendance_sheet_tmp');
        $this->sheetPond = null;

        $this->collegeToast(__('Attendance sheet submitted successfully.'));
    }

    public function render(): View
    {
        $teacher = auth()->user()?->teacher;
        $courses = $teacher
            ? Course::query()->where('teacher_id', $teacher->id)->with('program')->orderBy('code')->get()
            : collect();

        $recentSheets = $teacher
            ? TeacherAttendanceSheet::query()
                ->where('teacher_id', $teacher->id)
                ->with('course')
                ->orderByDesc('id')
                ->limit(8)
                ->get()
            : collect();

        $pendingValid = $this->sheetPond !== null
            && $this->sheetPond !== ''
            && Auth::id() !== null
            && FilepondPendingFile::assertOwnedPendingPath($this->sheetPond, (int) Auth::id());

        return view('livewire.teacher.teacher-attendance-page', [
            'courses' => $courses,
            'recentSheets' => $recentSheets,
            'pendingValid' => $pendingValid,
            'pendingBasename' => $pendingValid ? basename($this->sheetPond ?? '') : null,
        ])->layout('components.layouts.teacher', [
            'title' => __('Attendance management'),
            'headerDescription' => __('Mark and manage student attendance. Upload a file sheet or record it interactively in the portal.'),
        ]);
    }
}
