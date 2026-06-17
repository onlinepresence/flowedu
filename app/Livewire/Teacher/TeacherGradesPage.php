<?php

declare(strict_types=1);

namespace App\Livewire\Teacher;

use App\Models\Grade;
use App\Models\TeacherAssignment;
use App\Models\GradePoint;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class TeacherGradesPage extends Component
{
    use WithPagination;

    public ?int $selectedSessionId = null;
    public ?int $selectedCourseId = null;
    public ?int $selectedLevel = null;
    public string $search = '';

    protected $queryString = [
        'selectedSessionId' => ['except' => null],
        'selectedCourseId' => ['except' => null],
        'selectedLevel' => ['except' => null],
        'search' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedSessionId(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedCourseId(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedLevel(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->selectedSessionId = null;
        $this->selectedCourseId = null;
        $this->selectedLevel = null;
        $this->search = '';
        $this->resetPage();
    }

    public function render(): View
    {
        $teacher = auth()->user()?->teacher;
        $sessions = collect();
        $courses = collect();
        $levels = collect();

        if ($teacher) {
            $assignments = TeacherAssignment::query()
                ->where('teacher_id', $teacher->id)
                ->with(['course', 'session'])
                ->get();

            $sessions = $assignments->pluck('session')->unique('id')->sortByDesc('id')->values();
            $courses = $assignments->pluck('course')->unique('id')->sortBy('code')->values();
            $levels = $assignments->pluck('level')->unique()->sort()->values();
        }

        $query = Grade::query();

        if ($teacher) {
            $query->where('grades.teacher_id', $teacher->id);

            if ($this->selectedSessionId || $this->selectedCourseId || $this->selectedLevel) {
                $query->whereHas('resultSlip', function ($q) {
                    if ($this->selectedSessionId) {
                        $q->where('academic_session_id', $this->selectedSessionId);
                    }
                    if ($this->selectedCourseId) {
                        $q->where('course_id', $this->selectedCourseId);
                    }
                    if ($this->selectedLevel) {
                        $q->where('level', (string) $this->selectedLevel);
                    }
                });
            }

            if (trim($this->search) !== '') {
                $like = '%' . trim($this->search) . '%';
                $query->whereHas('student', function ($q) use ($like) {
                    $q->where('lastname', 'like', $like)
                      ->orWhere('firstname', 'like', $like)
                      ->orWhere('othernames', 'like', $like)
                      ->orWhere('index_number', 'like', $like);
                });
            }
        } else {
            $query->whereRaw('1 = 0');
        }

        $rows = $query->with(['student', 'resultSlip.course', 'resultSlip.academicSession', 'resultSlip.program'])
            ->orderByDesc('id')
            ->paginate(15);

        // Fetch Grade scale points once
        $gradeScale = GradePoint::query()->orderByDesc('min_score')->get();

        // Calculate dynamic total scores & map grade letters
        $rows->each(function ($row) use ($gradeScale) {
            $total = floatval($row->class_score ?? 0) + floatval($row->exam_score ?? 0);
            $row->total_score = $total;

            $letter = 'F';
            foreach ($gradeScale as $gp) {
                if ($total >= $gp->min_score && $total <= $gp->max_score) {
                    $letter = $gp->grade;
                    break;
                }
            }
            $row->letter_grade = $letter;
        });

        return view('livewire.teacher.teacher-grades-page', [
            'rows' => $rows,
            'sessions' => $sessions,
            'courses' => $courses,
            'levels' => $levels,
        ])->layout('components.layouts.teacher', [
            'title' => __('Grades Log'),
            'headerDescription' => __('View score sheets and grading lines for students in your assigned cohorts.'),
        ]);
    }
}
