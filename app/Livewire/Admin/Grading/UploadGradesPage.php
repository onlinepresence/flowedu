<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Grading;

use App\Actions\Grading\UpdateResultScoreAction;
use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Grade;
use App\Models\GradePoint;
use App\Models\Program;
use App\Models\Result;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Support\FilepondPendingFile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UploadGradesPage extends Component
{
    use DispatchesCollegeToasts;

    public bool $isTeacherMode = false;

    public ?string $spreadsheetPond = null;

    public ?int $teacherId = null;

    public ?int $programId = null;

    public ?int $courseId = null;

    public ?int $level = null;

    public ?int $academicSessionId = null;

    public ?int $detectedRows = null;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $previewRows = [];

    public array $gradeScale = [];

    public bool $gradesSetup = false;

    public function mount(): void
    {
        $this->isTeacherMode = request()->routeIs('teacher.*') || str_starts_with(request()->path(), 'teacher') || (auth()->check() && auth()->user()->type === 'teacher');

        if ($this->isTeacherMode) {
            $user = auth()->user();
            $teacher = $user->teacher;
            if ($teacher === null) {
                abort(403, __('Authenticated user is not registered as a teacher.'));
            }
            $this->teacherId = $teacher->id;
        }

        $this->academicSessionId = AcademicSession::query()
            ->where('is_current', true)
            ->value('id');

        if ($this->academicSessionId === null) {
            $this->academicSessionId = AcademicSession::query()->orderByDesc('id')->value('id');
        }

        $this->gradeScale = GradePoint::query()->orderByDesc('min_score')->get()->toArray();
        $this->gradesSetup = count($this->gradeScale) > 0;
    }

    public function updatedTeacherId(): void
    {
        $this->resetFilters(['programId', 'courseId', 'level']);
    }

    public function updatedProgramId(): void
    {
        $this->resetFilters(['courseId', 'level']);
    }

    public function updatedCourseId(): void
    {
        $this->resetFilters(['level']);
    }

    public function updatedLevel(): void
    {
        $this->previewRows = [];
        $this->detectedRows = null;
    }

    public function updatedAcademicSessionId(): void
    {
        $this->previewRows = [];
        $this->detectedRows = null;
    }

    private function resetFilters(array $fields): void
    {
        $this->reset($fields);
        $this->previewRows = [];
        $this->detectedRows = null;
    }

    public function downloadTemplate(): ?StreamedResponse
    {
        $this->validate([
            'academicSessionId' => ['required', 'integer', 'exists:academic_sessions,id'],
            'teacherId' => ['required', 'integer', 'exists:teachers,id'],
            'programId' => ['required', 'integer', 'exists:programs,id'],
            'courseId' => ['required', 'integer', 'exists:courses,id'],
            'level' => ['required', 'integer'],
        ]);

        $program = Program::query()->findOrFail($this->programId);
        $course = Course::query()->findOrFail($this->courseId);
        $session = AcademicSession::query()->findOrFail($this->academicSessionId);

        $students = Student::query()
            ->where('program_id', $this->programId)
            ->where('current_year', (string) $this->level)
            ->orderBy('index_number')
            ->get();

        if ($students->isEmpty()) {
            $this->collegeToast(__('No students registered in this Program under Level :level.', ['level' => $this->level]), 'warning');
            return null;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Class List');

        // Header Metadata
        $metaText = sprintf(
            'Program: %s | Course: %s (%s) | Level: %d | Session: %s',
            $program->name,
            $course->name,
            $course->code,
            $this->level,
            $session->name
        );
        $sheet->setCellValue('A1', $metaText);
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);

        // Columns Headers
        $sheet->setCellValue('A2', 'Index Number');
        $sheet->setCellValue('B2', 'Student Name');
        $sheet->setCellValue('C2', 'Attendance (Max 10)');
        $sheet->setCellValue('D2', 'Midsem (Max 20)');
        $sheet->setCellValue('E2', 'Project (Max 10)');
        $sheet->setCellValue('F2', 'Exam (Max 60)');
        $sheet->setCellValue('G2', 'Total (100)');

        $headerStyle = $sheet->getStyle('A2:G2');
        $headerStyle->getFont()->setBold(true);

        $rowNum = 3;
        $isDemo = config('college.demo_mode');

        foreach ($students as $student) {
            $sheet->setCellValue('A'.$rowNum, $student->index_number);
            $sheet->setCellValue('B'.$rowNum, trim(($student->lastname ?? '').' '.($student->firstname ?? '').' '.($student->othernames ?? '')));

            if ($isDemo) {
                // Populate random mock marks
                $sheet->setCellValue('C'.$rowNum, rand(6, 10));
                $sheet->setCellValue('D'.$rowNum, rand(12, 20));
                $sheet->setCellValue('E'.$rowNum, rand(6, 10));
                $sheet->setCellValue('F'.$rowNum, rand(30, 60));
            } else {
                $sheet->setCellValue('C'.$rowNum, '');
                $sheet->setCellValue('D'.$rowNum, '');
                $sheet->setCellValue('E'.$rowNum, '');
                $sheet->setCellValue('F'.$rowNum, '');
            }
            
            // Add autocalculating SUM formula
            $sheet->setCellValue('G'.$rowNum, '=SUM(C'.$rowNum.':F'.$rowNum.')');
            
            $rowNum++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'Class_List_'.Str::slug($course->code).'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function analyze(): void
    {
        $this->detectedRows = null;
        $this->previewRows = [];

        $this->validate([
            'spreadsheetPond' => ['required', 'string', 'max:500'],
            'academicSessionId' => ['required', 'integer', 'exists:academic_sessions,id'],
            'teacherId' => ['required', 'integer', 'exists:teachers,id'],
            'programId' => ['required', 'integer', 'exists:programs,id'],
            'courseId' => ['required', 'integer', 'exists:courses,id'],
            'level' => ['required', 'integer'],
        ]);

        $userId = Auth::id();
        if ($userId === null || ! FilepondPendingFile::assertOwnedPendingPath($this->spreadsheetPond, $userId)) {
            $this->addError('spreadsheetPond', __('Could not read uploaded file.'));
            return;
        }

        $path = Storage::disk('local')->path($this->spreadsheetPond);

        try {
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $highest = max(0, $sheet->getHighestDataRow());
            $this->detectedRows = $highest;

            $rows = [];
            for ($row = 3; $row <= $highest; $row++) {
                $index = trim((string) $sheet->getCell('A'.$row)->getFormattedValue());
                $name = trim((string) $sheet->getCell('B'.$row)->getFormattedValue());
                $att = trim((string) $sheet->getCell('C'.$row)->getFormattedValue());
                $mid = trim((string) $sheet->getCell('D'.$row)->getFormattedValue());
                $proj = trim((string) $sheet->getCell('E'.$row)->getFormattedValue());
                $exam = trim((string) $sheet->getCell('F'.$row)->getFormattedValue());

                if ($index === '' && $name === '') {
                    continue;
                }

                $rows[] = [
                    'row_number' => $row,
                    'student_index' => $index,
                    'student_name' => $name,
                    'attendance' => $att,
                    'midsem' => $mid,
                    'project' => $proj,
                    'exam' => $exam,
                    'status' => 'pending',
                    'message' => '',
                ];
            }

            $this->hydrateStudentNames($rows);
            $this->previewRows = $rows;
        } catch (\Throwable $e) {
            $this->addError('spreadsheetPond', __('Error parsing spreadsheet: :err', ['err' => $e->getMessage()]));
            return;
        }

        $this->collegeToast(__('Parsed :n students from template. Please review and confirm below.', ['n' => count($this->previewRows)]));
    }

    public function attemptUpload(): void
    {
        $this->validate([
            'academicSessionId' => ['required', 'integer', 'exists:academic_sessions,id'],
            'teacherId' => ['required', 'integer', 'exists:teachers,id'],
            'programId' => ['required', 'integer', 'exists:programs,id'],
            'courseId' => ['required', 'integer', 'exists:courses,id'],
            'level' => ['required', 'integer'],
            'previewRows' => ['required', 'array', 'min:1'],
        ]);

        if (! $this->gradesSetup) {
            $this->collegeToast(__('Cannot import grades because grade points have not been setup.'), 'error');
            return;
        }

        $course = Course::query()->find($this->courseId);
        $semesterVal = 1;
        if ($course) {
            $semesterVal = str_contains((string) $course->course_semester, '2') ? 2 : 1;
        }

        $existing = \App\Models\ResultSlip::query()
            ->where('teacher_id', $this->teacherId)
            ->where('program_id', $this->programId)
            ->where('course_id', $this->courseId)
            ->where('academic_session_id', $this->academicSessionId)
            ->where('level', (string) $this->level)
            ->where('semester', $semesterVal)
            ->exists();

        if ($existing) {
            $this->dispatch('open-modal', 'confirm-overwrite-modal');
        } else {
            $this->confirmUpload();
        }
    }

    public function confirmUpload(): void
    {
        $action = app(UpdateResultScoreAction::class);
        $this->validate([
            'academicSessionId' => ['required', 'integer', 'exists:academic_sessions,id'],
            'teacherId' => ['required', 'integer', 'exists:teachers,id'],
            'programId' => ['required', 'integer', 'exists:programs,id'],
            'courseId' => ['required', 'integer', 'exists:courses,id'],
            'level' => ['required', 'integer'],
            'previewRows' => ['required', 'array', 'min:1'],
        ]);

        if (! $this->gradesSetup) {
            $this->collegeToast(__('Cannot import grades because grade points have not been setup.'), 'error');
            return;
        }

        $hasErrors = false;
        foreach ($this->previewRows as $i => $row) {
            $index = trim((string) ($row['student_index'] ?? ''));
            
            if ($index === '') {
                $this->previewRows[$i]['status'] = 'error';
                $this->previewRows[$i]['message'] = __('Index number is required.');
                $hasErrors = true;
                continue;
            }

            // Validations
            $att = $row['attendance'] !== '' ? floatval($row['attendance']) : 0.0;
            $mid = $row['midsem'] !== '' ? floatval($row['midsem']) : 0.0;
            $proj = $row['project'] !== '' ? floatval($row['project']) : 0.0;
            $exam = $row['exam'] !== '' ? floatval($row['exam']) : 0.0;

            if ($att < 0 || $att > 10) {
                $this->previewRows[$i]['status'] = 'error';
                $this->previewRows[$i]['message'] = __('Attendance must be 0-10.');
                $hasErrors = true;
                continue;
            }
            if ($mid < 0 || $mid > 20) {
                $this->previewRows[$i]['status'] = 'error';
                $this->previewRows[$i]['message'] = __('Midsem must be 0-20.');
                $hasErrors = true;
                continue;
            }
            if ($proj < 0 || $proj > 10) {
                $this->previewRows[$i]['status'] = 'error';
                $this->previewRows[$i]['message'] = __('Project must be 0-10.');
                $hasErrors = true;
                continue;
            }
            if ($exam < 0 || $exam > 60) {
                $this->previewRows[$i]['status'] = 'error';
                $this->previewRows[$i]['message'] = __('Exam must be 0-60.');
                $hasErrors = true;
                continue;
            }

            $student = Student::query()
                ->where('program_id', $this->programId)
                ->where('current_year', (string) $this->level)
                ->where(function($q) use ($index) {
                    $q->where('index_number', $index)
                      ->orWhere('admission_index', $index);
                })
                ->first();

            if ($student === null) {
                $this->previewRows[$i]['status'] = 'error';
                $this->previewRows[$i]['message'] = __('Student not found in this cohort.');
                $hasErrors = true;
                continue;
            }

            $this->previewRows[$i]['student_name'] = trim(($student->lastname ?? '').' '.($student->firstname ?? ''));
            $this->previewRows[$i]['status'] = 'ready';
            $this->previewRows[$i]['message'] = '';
        }

        if ($hasErrors) {
            $this->collegeToast(__('Fix errors in highlighted rows before importing.'), 'danger');
            return;
        }

        // Get course details for semester value
        $course = Course::query()->find($this->courseId);
        $semesterVal = 1;
        if ($course) {
            $semesterVal = str_contains((string) $course->course_semester, '2') ? 2 : 1;
        }

        DB::transaction(function () use ($semesterVal) {
            // Find or create the ResultSlip
            $slip = \App\Models\ResultSlip::query()->firstOrCreate(
                [
                    'teacher_id' => $this->teacherId,
                    'program_id' => $this->programId,
                    'course_id' => $this->courseId,
                    'academic_session_id' => $this->academicSessionId,
                    'level' => (string) $this->level,
                    'semester' => $semesterVal,
                ],
                [
                    'status' => 'draft',
                ]
            );

            // Determine status of the slip
            $newStatus = $this->isTeacherMode ? 'pending' : 'approved';
            $slip->status = $newStatus;
            $slip->review_comments = null;
            if ($newStatus === 'approved') {
                $slip->approved_by = auth()->id();
                $slip->approved_at = now();
            } else {
                $slip->approved_by = null;
                $slip->approved_at = null;
            }
            $slip->save();

            foreach ($this->previewRows as $i => $row) {
                $index = trim((string) $row['student_index']);
                $student = Student::query()
                    ->where('index_number', $index)
                    ->orWhere('admission_index', $index)
                    ->first();

                $att = $row['attendance'] !== '' ? floatval($row['attendance']) : 0.0;
                $mid = $row['midsem'] !== '' ? floatval($row['midsem']) : 0.0;
                $proj = $row['project'] !== '' ? floatval($row['project']) : 0.0;
                $ex = $row['exam'] !== '' ? floatval($row['exam']) : 0.0;

                $classScore = $att + $mid + $proj;

                // Save Grade
                Grade::query()->updateOrCreate(
                    [
                        'result_slip_id' => $slip->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'teacher_id' => $this->teacherId,
                        'attendance_score' => $att,
                        'midsem_score' => $mid,
                        'project_score' => $proj,
                        'class_score' => $classScore,
                        'exam_score' => $ex,
                    ]
                );

                $this->previewRows[$i]['status'] = 'imported';
                $this->previewRows[$i]['message'] = __('Saved');
            }

            // If approved, write final results using ResultSlipApprovalService
            if ($newStatus === 'approved') {
                $approvalService = new \App\Services\ResultSlipApprovalService();
                $approvalService->approve($slip, (int) auth()->id());
            }
        });

        $this->collegeToast(__('All parsed student results imported successfully.'));
        $this->spreadsheetPond = null;
        $this->previewRows = [];
        $this->detectedRows = null;

        if ($this->isTeacherMode) {
            $this->reset(['programId', 'courseId', 'level']);
        } else {
            $this->reset(['teacherId', 'programId', 'courseId', 'level']);
        }

        $this->dispatch('clear-filepond');
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function hydrateStudentNames(array &$rows): void
    {
        $indexes = collect($rows)
            ->map(fn (array $row) => trim((string) ($row['student_index'] ?? '')))
            ->filter()
            ->unique()
            ->values();

        if ($indexes->isEmpty()) {
            return;
        }

        $students = Student::query()
            ->whereIn('index_number', $indexes->all())
            ->orWhereIn('admission_index', $indexes->all())
            ->get();

        $lookup = [];
        foreach ($students as $student) {
            if ($student->index_number !== null && $student->index_number !== '') {
                $lookup[(string) $student->index_number] = $student;
            }
            if ($student->admission_index !== null && $student->admission_index !== '') {
                $lookup[(string) $student->admission_index] = $student;
            }
        }

        foreach ($rows as $i => $row) {
            $key = trim((string) ($row['student_index'] ?? ''));
            $student = $lookup[$key] ?? null;
            if ($student === null) {
                $rows[$i]['student_name'] = '';
                $rows[$i]['status'] = 'warning';
                $rows[$i]['message'] = __('Student not registered.');
                continue;
            }

            $rows[$i]['student_name'] = trim(($student->lastname ?? '').' '.($student->firstname ?? ''));
            $rows[$i]['status'] = 'ready';
            $rows[$i]['message'] = '';
        }
    }

    public function render(): View
    {
        $teachers = [];
        $programs = [];
        $courses = [];
        $levels = [];

        // Dynamic selections
        if (! $this->isTeacherMode) {
            $teachers = Teacher::query()
                ->with('user')
                ->get()
                ->sortBy(fn($t) => trim(($t->lastname ?? '').' '.($t->othernames ?? '')));
        }

        if ($this->teacherId) {
            $programs = TeacherAssignment::query()
                ->where('teacher_id', $this->teacherId)
                ->where('session_id', $this->academicSessionId)
                ->with('program')
                ->get()
                ->pluck('program')
                ->filter()
                ->unique('id');
        }

        if ($this->teacherId && $this->programId) {
            $courses = TeacherAssignment::query()
                ->where('teacher_id', $this->teacherId)
                ->where('program_id', $this->programId)
                ->where('session_id', $this->academicSessionId)
                ->with('course')
                ->get()
                ->pluck('course')
                ->filter()
                ->unique('id');
        }

        if ($this->teacherId && $this->programId && $this->courseId) {
            $levels = TeacherAssignment::query()
                ->where('teacher_id', $this->teacherId)
                ->where('program_id', $this->programId)
                ->where('course_id', $this->courseId)
                ->where('session_id', $this->academicSessionId)
                ->pluck('level')
                ->unique()
                ->toArray();
            sort($levels);
        }

        $sessions = AcademicSession::query()->orderByDesc('id')->get(['id', 'name']);

        $layout = $this->isTeacherMode ? 'components.layouts.teacher' : 'components.layouts.admin';

        return view('livewire.admin.grading.upload-grades-page', [
            'sessions' => $sessions,
            'teachers' => $teachers,
            'programs' => $programs,
            'courses' => $courses,
            'levels' => $levels,
        ])->layout($layout, [
            'title' => __('Upload grades'),
            'headerTitle' => __('Upload Course Results'),
            'headerDescription' => __('Import student marks in bulk using standard Excel template spreadsheet.'),
        ]);
    }
}
