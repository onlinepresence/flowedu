<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Grading;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Grade;
use App\Models\GradePoint;
use App\Models\Program;
use App\Models\Result;
use App\Models\ResultSlip;
use App\Services\ResultSlipApprovalService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class ApproveGradesPage extends Component
{
    use DispatchesCollegeToasts;
    use WithPagination;

    // Filters
    public ?int $academicSessionId = null;

    public ?int $facultyId = null;

    public ?int $departmentId = null;

    public ?int $programId = null;

    public ?int $courseId = null;

    public ?string $level = null;

    public string $searchLecturer = '';

    public string $filterStatus = 'pending';

    // Selected Cohort for modal
    public ?int $selectedTeacherId = null;

    public ?int $selectedCourseId = null;

    public ?int $selectedSessionId = null;

    public ?int $selectedProgramId = null;

    public ?string $selectedLevel = null;

    public Collection $cohortGrades;

    public string $cohortCourseLabel = '';

    private static ?Collection $gradeScaleCache = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasAdminPermission('nav_grading_approve'), 403);

        $this->academicSessionId = AcademicSession::query()
            ->where('is_current', true)
            ->value('id');

        if ($this->academicSessionId === null) {
            $this->academicSessionId = AcademicSession::query()->orderByDesc('id')->value('id');
        }

        $this->cohortGrades = new Collection();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFacultyId(): void
    {
        $this->resetPage();
        $this->reset(['departmentId', 'programId', 'courseId']);
    }

    public function updatedDepartmentId(): void
    {
        $this->resetPage();
        $this->reset(['programId', 'courseId']);
    }

    public function updatedProgramId(): void
    {
        $this->resetPage();
        $this->reset(['courseId']);
    }

    public function updatedCourseId(): void
    {
        $this->resetPage();
    }

    public function updatedLevel(): void
    {
        $this->resetPage();
    }

    public function updatedSearchLecturer(): void
    {
        $this->resetPage();
    }

    public function viewCohort(int $teacherId, int $courseId, int $sessionId, int $programId, string $level): void
    {
        $program = Program::findOrFail($programId);
        $admin = auth()->user()?->admin;
        if ($admin) {
            abort_unless($admin->canAccessProgram($program), 403);
        }

        $this->selectedTeacherId = $teacherId;
        $this->selectedCourseId = $courseId;
        $this->selectedSessionId = $sessionId;
        $this->selectedProgramId = $programId;
        $this->selectedLevel = $level;

        $this->refreshCohortGrades();
        $this->dispatch('open-modal', 'cohort-details-modal');
    }

    /**
     * Total for a grade row, computed from persisted fields so the modal
     * renders identically after Livewire dehydration roundtrips.
     */
    public function cohortTotal(Grade $grade): float
    {
        return floatval($grade->class_score) + floatval($grade->exam_score);
    }

    /**
     * Grade letter for a total. Grade scale is cached per request; never
     * stored on the models (synthetic props do not survive dehydration).
     */
    public function cohortGradeLetter(float $total): string
    {
        self::$gradeScaleCache ??= GradePoint::query()->orderByDesc('min_score')->get();

        foreach (self::$gradeScaleCache as $gp) {
            if ($total >= $gp->min_score && $total <= $gp->max_score) {
                return $gp->grade;
            }
        }

        return 'F';
    }

    private function refreshCohortGrades(): void
    {
        if ($this->selectedTeacherId === null) {
            $this->cohortGrades = new Collection();
            $this->cohortCourseLabel = '';
            return;
        }

        $slip = ResultSlip::query()
            ->where('teacher_id', $this->selectedTeacherId)
            ->where('program_id', $this->selectedProgramId)
            ->where('course_id', $this->selectedCourseId)
            ->where('academic_session_id', $this->selectedSessionId)
            ->where('level', $this->selectedLevel)
            ->with(['course'])
            ->first();

        if (! $slip) {
            $this->cohortGrades = new Collection();
            $this->cohortCourseLabel = '';
            return;
        }

        $this->cohortCourseLabel = $slip->course
            ? $slip->course->code.' - '.$slip->course->name
            : __('Unknown course');

        $this->cohortGrades = Grade::query()
            ->where('result_slip_id', $slip->id)
            ->with(['student'])
            ->get();

        $approvedStudentIds = Result::query()
            ->where('result_slip_id', $slip->id)
            ->pluck('student_id')
            ->toArray();

        foreach ($this->cohortGrades as $grade) {
            $isApproved = in_array($grade->student_id, $approvedStudentIds);
            $grade->status = $isApproved ? 'approved' : $slip->status;
        }

        if ($this->cohortGrades->isEmpty()) {
            $this->dispatch('close-modal', 'cohort-details-modal');
        }
    }

    public function approveIndividual(int $gradeId): void
    {
        $grade = Grade::query()->findOrFail($gradeId);
        $slip = $grade->resultSlip;

        $admin = auth()->user()?->admin;
        if ($admin) {
            abort_unless($admin->canAccessDepartment($slip->program->department_id), 403);
        }

        $totalScore = floatval($grade->class_score) + floatval($grade->exam_score);
        $gradeScale = GradePoint::query()->orderByDesc('min_score')->get();
        $gradeLetter = 'F';
        $gradePts = 0.0;
        foreach ($gradeScale as $gp) {
            if ($totalScore >= $gp->min_score && $totalScore <= $gp->max_score) {
                $gradeLetter = $gp->grade;
                $gradePts = (float) $gp->points;
                break;
            }
        }

        Result::query()->updateOrCreate(
            [
                'student_id' => $grade->student_id,
                'course_id' => $slip->course_id,
                'academic_session_id' => $slip->academic_session_id,
            ],
            [
                'score' => $totalScore,
                'grade' => $gradeLetter,
                'grade_points' => $gradePts,
                'entered_by' => auth()->id(),
                'entered_date' => now(),
                'teacher_id' => $slip->teacher_id,
                'result_token' => 'RES-' . Str::random(12),
                'result_slip_id' => $slip->id,
                'admin_amended' => false,
            ]
        );

        $totalGradesCount = Grade::query()->where('result_slip_id', $slip->id)->count();
        $approvedGradesCount = Result::query()->where('result_slip_id', $slip->id)->count();
        if ($approvedGradesCount >= $totalGradesCount) {
            $slip->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        }

        $this->collegeToast(__('Result approved.'));
        $this->refreshCohortGrades();
    }

    public function rejectIndividual(int $gradeId): void
    {
        $grade = Grade::query()->findOrFail($gradeId);
        $slip = $grade->resultSlip;

        $admin = auth()->user()?->admin;
        if ($admin) {
            abort_unless($admin->canAccessDepartment($slip->program->department_id), 403);
        }

        Result::query()
            ->where('result_slip_id', $slip->id)
            ->where('student_id', $grade->student_id)
            ->delete();

        $slip->update([
            'status' => 'rejected',
            'approved_by' => null,
            'approved_at' => null,
        ]);

        $this->collegeToast(__('Result marked as rejected.'));
        $this->refreshCohortGrades();
    }

    public function approveCohort(int $teacherId, int $courseId, int $sessionId, int $programId, string $level): void
    {
        $program = Program::findOrFail($programId);
        $admin = auth()->user()?->admin;
        if ($admin) {
            abort_unless($admin->canAccessProgram($program), 403);
        }

        $slip = ResultSlip::query()
            ->where('teacher_id', $teacherId)
            ->where('course_id', $courseId)
            ->where('academic_session_id', $sessionId)
            ->where('program_id', $programId)
            ->where('level', $level)
            ->first();

        if ($slip) {
            $approvalService = new ResultSlipApprovalService();
            $approvalService->approve($slip, (int) auth()->id());
        }

        $this->dispatch('close-modal', 'cohort-details-modal');
        $this->collegeToast(__('Cohort approved.'));
        $this->resetPage();
    }

    public function rejectCohort(int $teacherId, int $courseId, int $sessionId, int $programId, string $level): void
    {
        $program = Program::findOrFail($programId);
        $admin = auth()->user()?->admin;
        if ($admin) {
            abort_unless($admin->canAccessProgram($program), 403);
        }

        $slip = ResultSlip::query()
            ->where('teacher_id', $teacherId)
            ->where('course_id', $courseId)
            ->where('academic_session_id', $sessionId)
            ->where('program_id', $programId)
            ->where('level', $level)
            ->first();

        if ($slip) {
            $approvalService = new ResultSlipApprovalService();
            $approvalService->reject($slip, 'Rejected by Administrator.');
        }

        $this->dispatch('close-modal', 'cohort-details-modal');
        $this->collegeToast(__('Cohort rejected.'));
        $this->resetPage();
    }

    public function render(): View
    {
        $admin = auth()->user()?->admin;

        // Cascading selects values
        $faculties = Faculty::query()
            ->when($admin?->faculty_id, fn ($q) => $q->where('id', $admin->faculty_id))
            ->when($admin?->department_id, fn ($q) => $q->whereHas('departments', fn ($d) => $d->where('id', $admin->department_id)))
            ->orderBy('name')
            ->get(['id', 'name']);
        
        $departmentsQuery = Department::query()
            ->when($this->facultyId, fn ($q) => $q->where('faculty_id', $this->facultyId))
            ->when($admin?->department_id, fn ($q) => $q->where('id', $admin->department_id))
            ->when($admin?->faculty_id, fn ($q) => $q->where('faculty_id', $admin->faculty_id));
        $departments = ($this->facultyId || $admin?->department_id || $admin?->faculty_id)
            ? $departmentsQuery->orderBy('name')->get(['id', 'name'])
            : new Collection();

        $programsQuery = Program::query()
            ->when($this->departmentId, fn ($q) => $q->where('department_id', $this->departmentId))
            ->when($admin?->department_id, fn ($q) => $q->where('department_id', $admin->department_id))
            ->when($admin?->faculty_id, fn ($q) => $q->whereHas('department', fn ($d) => $d->where('faculty_id', $admin->faculty_id)));
        $programs = ($this->departmentId || $admin?->department_id || $admin?->faculty_id)
            ? $programsQuery->orderBy('name')->get(['id', 'name'])
            : new Collection();

        $coursesQuery = Course::query()
            ->when($this->programId, fn ($q) => $q->where('program_id', $this->programId))
            ->when($admin?->department_id, fn ($q) => $q->whereHas('program', fn ($p) => $p->where('department_id', $admin->department_id)))
            ->when($admin?->faculty_id, fn ($q) => $q->whereHas('program.department', fn ($d) => $d->where('faculty_id', $admin->faculty_id)));
        $courses = ($this->programId || $admin?->department_id || $admin?->faculty_id)
            ? $coursesQuery->orderBy('code')->get(['id', 'code', 'name'])
            : new Collection();

        // Cohorts Query on result_slips
        $query = ResultSlip::query()
            ->join('courses', 'result_slips.course_id', '=', 'courses.id')
            ->join('programs', 'result_slips.program_id', '=', 'programs.id')
            ->join('teachers', 'result_slips.teacher_id', '=', 'teachers.id')
            ->join('users', 'teachers.user_id', '=', 'users.id')
            ->leftJoin('grades', 'grades.result_slip_id', '=', 'result_slips.id')
            ->where('result_slips.status', $this->filterStatus);

        if ($this->academicSessionId) {
            $query->where('result_slips.academic_session_id', $this->academicSessionId);
        }

        if ($this->facultyId) {
            $query->join('departments', 'programs.department_id', '=', 'departments.id')
                  ->where('departments.faculty_id', $this->facultyId);
        }

        if ($this->departmentId) {
            if (! $this->facultyId) {
                $query->join('departments', 'programs.department_id', '=', 'departments.id');
            }
            $query->where('programs.department_id', $this->departmentId);
        }

        if ($this->programId) {
            $query->where('result_slips.program_id', $this->programId);
        }

        if ($this->courseId) {
            $query->where('result_slips.course_id', $this->courseId);
        }

        if ($this->level) {
            $query->where('result_slips.level', $this->level);
        }

        if ($this->searchLecturer !== '') {
            $query->where(function($q) {
                $q->where('users.name', 'like', '%'.$this->searchLecturer.'%')
                  ->orWhere('teachers.lastname', 'like', '%'.$this->searchLecturer.'%')
                  ->orWhere('teachers.othernames', 'like', '%'.$this->searchLecturer.'%');
            });
        }

        // Apply admin jurisdiction scoping to query
        $query->when($admin?->department_id, fn ($q) => $q->whereIn('result_slips.program_id', function ($sub) use ($admin) {
            $sub->select('id')->from('programs')->where('department_id', $admin->department_id);
        }))
        ->when($admin?->faculty_id, fn ($q) => $q->whereIn('result_slips.program_id', function ($sub) use ($admin) {
            $sub->select('programs.id')->from('programs')
                ->join('departments', 'programs.department_id', '=', 'departments.id')
                ->where('departments.faculty_id', $admin->faculty_id);
        }));

        $cohorts = $query->select([
                'result_slips.teacher_id',
                'result_slips.course_id',
                'result_slips.academic_session_id',
                'result_slips.program_id',
                'result_slips.level',
                'courses.code as course_code',
                'courses.name as course_name',
                'programs.name as program_name',
                DB::raw("TRIM(CONCAT(COALESCE(teachers.lastname, ''), ' ', COALESCE(teachers.othernames, ''))) as teacher_name"),
                DB::raw('COUNT(grades.id) as pending_count')
            ])
            ->groupBy([
                'result_slips.teacher_id',
                'result_slips.course_id',
                'result_slips.academic_session_id',
                'result_slips.program_id',
                'result_slips.level',
                'courses.code',
                'courses.name',
                'programs.name',
                'teachers.lastname',
                'teachers.othernames'
            ])
            ->paginate(15);

        $sessions = AcademicSession::query()->orderByDesc('id')->get(['id', 'name']);

        return view('livewire.admin.grading.approve-grades-page', [
            'cohorts' => $cohorts,
            'sessions' => $sessions,
            'faculties' => $faculties,
            'departments' => $departments,
            'programs' => $programs,
            'courses' => $courses,
        ])->layout('components.layouts.admin', [
            'title' => __('Approve grades'),
            'headerTitle' => __('Results Approval'),
            'headerDescription' => __('Review and approve pending student marks submitted by teachers.'),
        ]);
    }
}
