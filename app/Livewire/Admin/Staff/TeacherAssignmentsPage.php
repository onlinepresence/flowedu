<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Staff;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Program;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class TeacherAssignmentsPage extends Component
{
    use DispatchesCollegeToasts;
    use WithPagination;

    // Filters
    public string $search = '';

    public ?int $filterSessionId = null;

    // Bulk selection
    public array $selectedIds = [];

    public bool $selectAll = false;

    // Form fields
    public ?int $teacher_id = null;

    public ?int $program_id = null;

    public int $level = 1;

    public ?int $course_id = null;

    public ?int $session_id = null;

    // UI state
    public bool $isEditing = false;

    public ?int $editingId = null;

    public bool $showDeleteModal = false;

    public ?int $deletingId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterSessionId' => ['except' => null],
    ];

    public function mount(): void
    {
        $currentSession = AcademicSession::where('is_current', true)->first();
        if ($currentSession !== null) {
            $this->filterSessionId = $currentSession->id;
            $this->session_id = $currentSession->id;
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->selectedIds = [];
        $this->selectAll = false;
    }

    public function updatingFilterSessionId(): void
    {
        $this->resetPage();
        $this->selectedIds = [];
        $this->selectAll = false;
    }

    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $this->selectedIds = $this->getVisibleAssignmentIds();
        } else {
            $this->selectedIds = [];
        }
    }

    public function updatedProgramId(mixed $value): void
    {
        $this->course_id = null;
        $this->level = 1;
    }

    private function getVisibleAssignmentIds(): array
    {
        $query = TeacherAssignment::query();

        if ($this->search !== '') {
            $query->where(function ($q): void {
                $term = '%'.$this->search.'%';
                $q->whereHas('teacher', function ($tq) use ($term): void {
                    $tq->where('lastname', 'like', $term)
                       ->orWhere('othernames', 'like', $term);
                })
                ->orWhereHas('course', function ($cq) use ($term): void {
                    $cq->where('name', 'like', $term)
                       ->orWhere('code', 'like', $term);
                })
                ->orWhereHas('program', function ($pq) use ($term): void {
                    $pq->where('name', 'like', $term);
                });
            });
        }

        if ($this->filterSessionId !== null && $this->filterSessionId > 0) {
            $query->where('session_id', $this->filterSessionId);
        }

        return $query->pluck('id')->map(fn ($id) => (string) $id)->toArray();
    }

    public function moveToCurrentSession(): void
    {
        $currentSession = AcademicSession::where('is_current', true)->first();
        if ($currentSession === null) {
            $this->collegeToast(__('No current academic session found.'), 'error');
            return;
        }

        if ($this->filterSessionId !== null && (int) $this->filterSessionId === $currentSession->id) {
            $this->collegeToast(__('Cannot migrate assignments from the current academic session to itself.'), 'error');
            return;
        }

        if (empty($this->selectedIds)) {
            $this->collegeToast(__('No assignments selected.'), 'error');
            return;
        }

        $assignments = TeacherAssignment::query()
            ->whereIn('id', $this->selectedIds)
            ->get();

        $migratedCount = 0;
        foreach ($assignments as $assignment) {
            $exists = TeacherAssignment::query()
                ->where('teacher_id', $assignment->teacher_id)
                ->where('course_id', $assignment->course_id)
                ->where('session_id', $currentSession->id)
                ->exists();

            if (!$exists) {
                TeacherAssignment::create([
                    'teacher_id' => $assignment->teacher_id,
                    'program_id' => $assignment->program_id,
                    'level' => $assignment->level,
                    'course_id' => $assignment->course_id,
                    'session_id' => $currentSession->id,
                    'assigned_by' => auth()->id(),
                    'assigned_date' => now(),
                ]);
                $migratedCount++;
            }
        }

        $this->selectedIds = [];
        $this->selectAll = false;
        $this->collegeToast(__(':count course assignment(s) migrated to session :session.', [
            'count' => $migratedCount,
            'session' => $currentSession->name
        ]));
    }

    public function startEdit(int $id): void
    {
        $row = TeacherAssignment::query()->findOrFail($id);
        $this->editingId = $row->id;
        $this->teacher_id = $row->teacher_id;
        $this->program_id = $row->program_id;
        $this->level = (int) $row->level;
        $this->course_id = $row->course_id;
        $this->session_id = $row->session_id;
        $this->isEditing = true;
    }

    public function cancelEdit(): void
    {
        $this->resetAssignmentForm();
    }

    public function openDeleteModal(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function confirmDelete(): void
    {
        if ($this->deletingId === null) {
            return;
        }

        TeacherAssignment::query()->whereKey($this->deletingId)->delete();

        $this->closeDeleteModal();
        $this->resetPage();
        $this->collegeToast(__('Assignment removed.'));
    }

    public function save(): void
    {
        $validated = $this->validate($this->rulesForSave());

        if ($this->courseProgramMismatch((int) $validated['course_id'], (int) $validated['program_id'])) {
            $this->addError('course_id', __('Selected course does not belong to the program.'));

            return;
        }

        if ($this->isDuplicateAssignment(
            (int) $validated['teacher_id'],
            (int) $validated['course_id'],
            (int) $validated['session_id'],
            $this->editingId,
        )) {
            $this->addError('course_id', __('This teacher is already assigned to this course in this session.'));

            return;
        }

        if ($this->isEditing && $this->editingId !== null) {
            $row = TeacherAssignment::query()->findOrFail($this->editingId);
            $row->forceFill([
                'teacher_id' => (int) $validated['teacher_id'],
                'program_id' => (int) $validated['program_id'],
                'level' => (int) $validated['level'],
                'course_id' => (int) $validated['course_id'],
                'session_id' => (int) $validated['session_id'],
            ])->save();

            $this->collegeToast(__('Assignment updated.'));
        } else {
            TeacherAssignment::query()->create([
                'teacher_id' => (int) $validated['teacher_id'],
                'program_id' => (int) $validated['program_id'],
                'level' => (int) $validated['level'],
                'course_id' => (int) $validated['course_id'],
                'session_id' => (int) $validated['session_id'],
                'assigned_by' => auth()->id(),
                'assigned_date' => now(),
            ]);

            $this->collegeToast(__('Assignment created.'));
        }

        $this->resetAssignmentForm();
    }

    /**
     * @return array<string, mixed>
     */
    private function rulesForSave(): array
    {
        $program = Program::find($this->program_id);
        $maxLevel = $program?->program_length ?? 4;

        return [
            'teacher_id' => ['required', 'exists:teachers,id'],
            'program_id' => ['required', 'exists:programs,id'],
            'level' => ['required', 'integer', 'min:1', 'max:'.$maxLevel],
            'course_id' => ['required', 'exists:courses,id'],
            'session_id' => ['required', 'exists:academic_sessions,id'],
        ];
    }

    private function courseProgramMismatch(int $courseId, int $programId): bool
    {
        return ! Course::query()->whereKey($courseId)->where('program_id', $programId)->exists();
    }

    private function isDuplicateAssignment(int $teacherId, int $courseId, int $sessionId, ?int $ignoreId): bool
    {
        $q = TeacherAssignment::query()
            ->where('teacher_id', $teacherId)
            ->where('course_id', $courseId)
            ->where('session_id', $sessionId);

        if ($ignoreId !== null) {
            $q->where('id', '!=', $ignoreId);
        }

        return $q->exists();
    }

    private function resetAssignmentForm(): void
    {
        $this->teacher_id = null;
        $this->program_id = null;
        $this->level = 1;
        $this->course_id = null;
        $currentSession = AcademicSession::where('is_current', true)->first();
        $this->session_id = $currentSession?->id;
        $this->isEditing = false;
        $this->editingId = null;
        $this->resetValidation();
    }

    public function render(): View
    {
        $query = TeacherAssignment::query();

        if ($this->search !== '') {
            $query->where(function ($q): void {
                $term = '%'.$this->search.'%';
                $q->whereHas('teacher', function ($tq) use ($term): void {
                    $tq->where('lastname', 'like', $term)
                       ->orWhere('othernames', 'like', $term);
                })
                ->orWhereHas('course', function ($cq) use ($term): void {
                    $cq->where('name', 'like', $term)
                       ->orWhere('code', 'like', $term);
                })
                ->orWhereHas('program', function ($pq) use ($term): void {
                    $pq->where('name', 'like', $term);
                });
            });
        }

        if ($this->filterSessionId !== null && $this->filterSessionId > 0) {
            $query->where('session_id', $this->filterSessionId);
        }

        $rows = $query->with(['teacher', 'program', 'course', 'session'])
            ->orderByDesc('assigned_date')
            ->paginate(20);

        $teachers = Teacher::query()->with('user')->orderBy('lastname')->limit(500)->get();
        $programs = Program::query()->orderBy('name')->get();
        
        $currentSession = AcademicSession::where('is_current', true)->first();
        
        // Filter session options to only include current and future sessions (or the session of the assignment being edited)
        $formSessions = AcademicSession::query()
            ->where('is_current', true)
            ->orWhere('start_date', '>=', $currentSession?->start_date ?? now())
            ->when($this->isEditing && $this->session_id, function ($q): void {
                $q->orWhere('id', $this->session_id);
            })
            ->orderByDesc('id')
            ->get();

        $allSessions = AcademicSession::query()->orderByDesc('id')->limit(100)->get();

        $courses = collect();
        $maxLevels = 4;
        if ($this->program_id !== null && $this->program_id > 0) {
            $selectedProg = Program::find($this->program_id);
            $maxLevels = $selectedProg?->program_length ?? 4;
            $courses = Course::query()
                ->where('program_id', $this->program_id)
                ->orderBy('code')
                ->get();
        }

        return view('livewire.admin.staff.teacher-assignments-page', [
            'rows' => $rows,
            'teachers' => $teachers,
            'programs' => $programs,
            'formSessions' => $formSessions,
            'allSessions' => $allSessions,
            'courses' => $courses,
            'maxLevels' => $maxLevels,
            'currentSession' => $currentSession,
        ])->layout('components.layouts.admin', [
            'title' => __('Teacher Course Assignments'),
            'headerTitle' => __('Teacher Course Assignments'),
            'headerDescription' => __('Assign lecturers to courses, academic sessions, and specific program levels.'),
        ]);
    }
}
