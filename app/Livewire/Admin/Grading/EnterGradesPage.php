<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Grading;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Grade;
use App\Models\GradePoint;
use App\Models\Program;
use App\Models\Result;
use App\Models\ResultSlip;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Services\ResultSlipApprovalService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class EnterGradesPage extends Component
{
    use DispatchesCollegeToasts;

    public bool $isTeacherMode = false;

    public ?int $teacherId = null;

    public ?int $programId = null;

    public string $semester = '';

    public ?int $courseId = null;

    public ?int $level = null;

    public ?int $academicSessionId = null;

    public array $scores = [];

    public array $gradeScale = [];

    public bool $gradesSetup = false;

    public string $cohortStatus = 'new';

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

        // Load optional deep-linked parameters
        if (request()->query('programId')) {
            $this->programId = (int) request()->query('programId');
        }
        if (request()->query('semester')) {
            $this->semester = (string) request()->query('semester');
        }
        if (request()->query('courseId')) {
            $this->courseId = (int) request()->query('courseId');
        }
        if (request()->query('level')) {
            $this->level = (int) request()->query('level');
        }
        if (request()->query('academicSessionId')) {
            $this->academicSessionId = (int) request()->query('academicSessionId');
        }

        if ($this->teacherId && $this->programId && $this->semester && $this->courseId && $this->level && $this->academicSessionId) {
            $this->loadStudentsAndScores();
        }
    }

    public function updatedTeacherId(): void
    {
        $this->resetFilters(['programId', 'semester', 'courseId', 'level']);
    }

    public function updatedProgramId(): void
    {
        $this->resetFilters(['semester', 'courseId', 'level']);
    }

    public function updatedSemester(): void
    {
        $this->resetFilters(['courseId', 'level']);
    }

    public function updatedCourseId(): void
    {
        $this->resetFilters(['level']);
    }

    public function updatedLevel(): void
    {
        $this->loadStudentsAndScores();
    }

    public function updatedAcademicSessionId(): void
    {
        $this->loadStudentsAndScores();
    }

    private function resetFilters(array $fields): void
    {
        $this->reset($fields);
        $this->scores = [];
        $this->cohortStatus = 'new';
    }

    public function enableEdit(int $studentId): void
    {
        if (isset($this->scores[$studentId])) {
            $this->scores[$studentId]['is_editing'] = true;
        }
    }

    public function convertToDraft(): void
    {
        if (! $this->teacherId || ! $this->programId || ! $this->semester || ! $this->courseId || ! $this->level || ! $this->academicSessionId) {
            return;
        }

        $course = Course::query()->find($this->courseId);
        $semesterVal = 1;
        if ($course) {
            $semesterVal = str_contains((string) $course->course_semester, '2') ? 2 : 1;
        }

        $slip = ResultSlip::query()
            ->where('teacher_id', $this->teacherId)
            ->where('program_id', $this->programId)
            ->where('course_id', $this->courseId)
            ->where('academic_session_id', $this->academicSessionId)
            ->where('level', (string) $this->level)
            ->where('semester', $semesterVal)
            ->first();

        if ($slip && $slip->status === 'rejected') {
            $slip->update(['status' => 'draft']);
            $this->loadStudentsAndScores();
            $this->collegeToast(__('Result slip converted to draft for editing.'));
        }
    }

    public function loadStudentsAndScores(): void
    {
        $this->scores = [];
        $this->cohortStatus = 'new';

        if (! $this->teacherId || ! $this->programId || ! $this->semester || ! $this->courseId || ! $this->level || ! $this->academicSessionId) {
            return;
        }

        // Get course details for semester value
        $course = Course::query()->find($this->courseId);
        $semesterVal = 1;
        if ($course) {
            $semesterVal = str_contains((string) $course->course_semester, '2') ? 2 : 1;
        }

        // Find or create ResultSlip for this cohort
        $slip = ResultSlip::query()
            ->where('teacher_id', $this->teacherId)
            ->where('program_id', $this->programId)
            ->where('course_id', $this->courseId)
            ->where('academic_session_id', $this->academicSessionId)
            ->where('level', (string) $this->level)
            ->where('semester', $semesterVal)
            ->first();

        $slipStatus = $slip ? $slip->status : 'new';
        $this->cohortStatus = $slipStatus;
        $reviewComments = $slip ? $slip->review_comments : null;
        $isCohortEditingByDefault = ($slipStatus === 'new' || $slipStatus === 'draft');

        $students = Student::query()
            ->where('program_id', $this->programId)
            ->where('current_year', (string) $this->level)
            ->orderBy('index_number')
            ->get();

        foreach ($students as $student) {
            $grade = $slip 
                ? Grade::query()->where('result_slip_id', $slip->id)->where('student_id', $student->id)->first() 
                : null;

            $isEditing = $isCohortEditingByDefault;

            $this->scores[$student->id] = [
                'selected' => true,
                'attendance' => $grade && $grade->attendance_score !== null ? (string) floatval($grade->attendance_score) : '',
                'midsem' => $grade && $grade->midsem_score !== null ? (string) floatval($grade->midsem_score) : '',
                'project' => $grade && $grade->project_score !== null ? (string) floatval($grade->project_score) : '',
                'exam' => $grade && $grade->exam_score !== null ? (string) floatval($grade->exam_score) : '',
                'index_number' => $student->index_number,
                'name' => trim(($student->lastname ?? '').' '.($student->firstname ?? '').' '.($student->othernames ?? '')),
                'status' => $slipStatus,
                'review_comments' => $reviewComments,
                'is_editing' => $isEditing,
            ];
        }
    }

    public function saveScores(bool $isDraft = false): void
    {
        if (! $this->gradesSetup) {
            $this->collegeToast(__('Cannot save results because grade points have not been setup.'), 'error');
            return;
        }

        if (empty($this->scores)) {
            return;
        }

        // Filter out students that are checked AND are in an editable state
        $selectedScores = array_filter($this->scores, function ($s) {
            if (empty($s['selected'])) {
                return false;
            }
            $status = $s['status'] ?? 'new';
            $isEditing = $s['is_editing'] ?? false;
            return $status === 'new' || $status === 'draft' || $status === 'rejected' || $isEditing;
        });

        if (empty($selectedScores)) {
            $this->collegeToast(__('Please select at least one editable student to save.'), 'warning');
            return;
        }

        // Validate
        $rules = [];
        $messages = [];
        foreach ($selectedScores as $studentId => $data) {
            $rules["scores.{$studentId}.attendance"] = ['nullable', 'numeric', 'min:0', 'max:10'];
            $rules["scores.{$studentId}.midsem"] = ['nullable', 'numeric', 'min:0', 'max:20'];
            $rules["scores.{$studentId}.project"] = ['nullable', 'numeric', 'min:0', 'max:10'];
            $rules["scores.{$studentId}.exam"] = ['nullable', 'numeric', 'min:0', 'max:60'];

            $name = $data['name'];
            $messages["scores.{$studentId}.attendance.max"] = __(':name: Attendance score cannot exceed 10.', ['name' => $name]);
            $messages["scores.{$studentId}.midsem.max"] = __(':name: Mid-semester score cannot exceed 20.', ['name' => $name]);
            $messages["scores.{$studentId}.project.max"] = __(':name: Project score cannot exceed 10.', ['name' => $name]);
            $messages["scores.{$studentId}.exam.max"] = __(':name: Exam score cannot exceed 60.', ['name' => $name]);
        }

        $this->validate($rules, $messages);

        // Get course details for semester value
        $course = Course::query()->find($this->courseId);
        $semesterVal = 1;
        if ($course) {
            $semesterVal = str_contains((string) $course->course_semester, '2') ? 2 : 1;
        }

        DB::transaction(function () use ($selectedScores, $isDraft, $semesterVal) {
            // Find or create the ResultSlip
            $slip = ResultSlip::query()->firstOrCreate(
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
            if ($isDraft) {
                $newStatus = 'draft';
            } else {
                $newStatus = $this->isTeacherMode ? 'pending' : 'approved';
            }

            // Update slip status and clear review comments when saving/submitting
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

            // Save Grades
            foreach ($selectedScores as $studentId => $data) {
                $att = $data['attendance'] !== '' ? floatval($data['attendance']) : 0.0;
                $mid = $data['midsem'] !== '' ? floatval($data['midsem']) : 0.0;
                $proj = $data['project'] !== '' ? floatval($data['project']) : 0.0;
                $ex = $data['exam'] !== '' ? floatval($data['exam']) : 0.0;

                $classScore = $att + $mid + $proj;

                Grade::query()->updateOrCreate(
                    [
                        'result_slip_id' => $slip->id,
                        'student_id' => $studentId,
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
            }

            // If approved, write final results using ResultSlipApprovalService
            if ($newStatus === 'approved') {
                $approvalService = new ResultSlipApprovalService();
                $approvalService->approve($slip, (int) auth()->id());
            }
        });

        $this->collegeToast($isDraft ? __('Results saved as draft.') : __('Student results submitted successfully.'));
        $this->loadStudentsAndScores();
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

        if ($this->teacherId && $this->programId && $this->semester) {
            $courses = TeacherAssignment::query()
                ->where('teacher_id', $this->teacherId)
                ->where('program_id', $this->programId)
                ->where('session_id', $this->academicSessionId)
                ->whereHas('course', function ($q) {
                    $q->where('course_semester', $this->semester);
                })
                ->with('course')
                ->get()
                ->pluck('course')
                ->filter()
                ->unique('id');
        }

        if ($this->teacherId && $this->programId && $this->semester && $this->courseId) {
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

        return view('livewire.admin.grading.enter-grades-page', [
            'sessions' => $sessions,
            'teachers' => $teachers,
            'programs' => $programs,
            'courses' => $courses,
            'levels' => $levels,
        ])->layout($layout, [
            'title' => __('Enter results'),
            'headerTitle' => __('Enter Student Results'),
            'headerDescription' => __('Record continuous assessment scores and exam marks directly into the system.'),
        ]);
    }
}
