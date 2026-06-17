<?php

declare(strict_types=1);

namespace App\Livewire\Teacher;

use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\ParentGuardian;
use App\Models\Result;
use App\Models\Student;
use App\Models\TeacherAssignment;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

class TeacherStudentsPage extends Component
{
    #[Url(as: 'course', except: '')]
    public ?string $filterCourseCode = null;

    #[Url(as: 'semester', except: 'all')]
    public string $selectedSemester = 'all';

    #[Url(as: 'search', except: '')]
    public string $search = '';

    public ?int $selectedStudentId = null;
    public string $modalType = '';

    public function resetFilters(): void
    {
        $this->filterCourseCode = null;
        $this->selectedSemester = 'all';
        $this->search = '';
    }

    public function showStudentModal(int $studentId, string $type): void
    {
        $this->selectedStudentId = $studentId;
        $this->modalType = $type;
    }

    public function closeStudentModal(): void
    {
        $this->selectedStudentId = null;
        $this->modalType = '';
    }

    public function render(): View
    {
        $teacher = auth()->user()?->teacher;
        $rows = collect();
        $courseOptions = collect();

        // Modal context variables
        $selectedStudent = null;
        $studentResults = collect();
        $cgpa = null;
        $parentGuardian = null;

        if ($teacher !== null) {
            $activeSession = AcademicSession::query()->where('is_active', true)->first();
            if ($activeSession === null) {
                $activeSession = AcademicSession::query()->orderByDesc('id')->first();
            }

            // Dropdown options
            $courseOptions = Course::query()
                ->where('teacher_id', $teacher->id)
                ->orderBy('code')
                ->get(['id', 'code', 'name']);

            // Get active assignments of this teacher
            $assignments = TeacherAssignment::query()
                ->where('teacher_id', $teacher->id)
                ->where('session_id', $activeSession->id)
                ->get();

            if (!$assignments->isEmpty()) {
                $q = Student::query()
                    ->select([
                        'students.*',
                        'courses.code as enrolled_course_code',
                        'courses.name as enrolled_course_name',
                        'courses.year_level as course_year_level',
                        'courses.course_semester as course_semester'
                    ])
                    ->join('courses', 'courses.program_id', '=', 'students.program_id')
                    ->where('students.approved', true)
                    ->where('courses.teacher_id', $teacher->id)
                    ->where(function (Builder $sub) use ($assignments): void {
                        foreach ($assignments as $asg) {
                            $sub->orWhere(function (Builder $sub2) use ($asg): void {
                                $sub2->where('students.program_id', $asg->program_id)
                                     ->where('students.current_year', (string) $asg->level)
                                     ->where('courses.id', $asg->course_id);
                            });
                        }
                    });

                // Apply semester filter
                if ($this->selectedSemester !== 'all') {
                    $q->where('courses.course_semester', $this->selectedSemester);
                }

                // Apply course filter
                if ($this->filterCourseCode !== null && $this->filterCourseCode !== '') {
                    $q->where('courses.code', $this->filterCourseCode);
                }

                // Apply search
                $search = trim($this->search);
                if ($search !== '') {
                    $like = '%'.$search.'%';
                    $q->where(function (Builder $sub) use ($like): void {
                        $sub->where('students.lastname', 'like', $like)
                            ->orWhere('students.firstname', 'like', $like)
                            ->orWhere('students.othernames', 'like', $like)
                            ->orWhere('students.index_number', 'like', $like);
                    });
                }

                $rows = $q
                    ->orderBy('students.lastname')
                    ->orderBy('students.firstname')
                    ->orderBy('enrolled_course_code')
                    ->limit(500)
                    ->get();
            }

            // Modal detail loaders
            if ($this->selectedStudentId !== null) {
                $selectedStudent = Student::with(['program', 'user'])->find($this->selectedStudentId);
                if ($selectedStudent !== null) {
                    if ($this->modalType === 'profile') {
                        $parentGuardian = ParentGuardian::where('student_id', $this->selectedStudentId)->first();
                    } elseif ($this->modalType === 'performance') {
                        $studentResults = Result::query()
                            ->where('student_id', $this->selectedStudentId)
                            ->with(['course', 'academicSession'])
                            ->orderBy('academic_session_id', 'desc')
                            ->get();

                        $cgpaVal = Result::query()
                            ->where('student_id', $this->selectedStudentId)
                            ->whereNotNull('grade_points')
                            ->avg('grade_points');
                        $cgpa = $cgpaVal !== null ? (float) $cgpaVal : null;
                    }
                }
            }
        }

        return view('livewire.teacher.teacher-students-page', [
            'rows' => $rows,
            'courseOptions' => $courseOptions,
            'selectedStudent' => $selectedStudent,
            'studentResults' => $studentResults,
            'cgpa' => $cgpa,
            'parentGuardian' => $parentGuardian,
        ])->layout('components.layouts.teacher', [
            'title' => __('My students'),
            'headerDescription' => __('View and manage students enrolled in your active courses.'),
        ]);
    }
}
