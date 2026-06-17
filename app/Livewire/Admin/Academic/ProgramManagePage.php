<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Academic;

use App\Models\Course;
use App\Models\Program;
use App\Models\Teacher;
use App\Support\CollegeFlash;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ProgramManagePage extends Component
{
    public Program $program;

    public string $form_level = '';

    public ?int $editingCourseId = null;

    public ?int $deletingCourseId = null;

    public string $course_name = '';

    public string $course_code = '';

    public string $course_semester = '1';

    public string $teacher_id = '';

    public function mount(int $program_id, string $form_level): void
    {
        $this->program = Program::query()->findOrFail($program_id);
        $this->form_level = $form_level;
    }

    public function saveCourse(): void
    {
        $this->validate([
            'course_name' => ['required', 'string', 'max:255', 'unique:courses,name'],
            'course_code' => ['nullable', 'string', 'max:255', 'unique:courses,code'],
            'course_semester' => ['required', Rule::in(['1', '2'])],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
        ]);

        $code = trim($this->course_code);
        if ($code === '') {
            $code = $this->generateCourseCode();
        }

        Course::query()->create([
            'name' => trim($this->course_name),
            'code' => $code,
            'program_id' => $this->program->id,
            'teacher_id' => $this->teacher_id === '' ? null : (int) $this->teacher_id,
            'course_semester' => $this->course_semester,
            'year_level' => $this->form_level,
        ]);

        $this->resetCourseForm();
        CollegeFlash::forNextRequestToo('status', __('Course has been added.'));
    }

    public function editCourse(int $courseId): void
    {
        $course = Course::query()->where('program_id', $this->program->id)->findOrFail($courseId);
        $this->editingCourseId = $course->id;
        $this->course_name = (string) $course->name;
        $this->course_code = (string) $course->code;
        $this->course_semester = (string) $course->course_semester;
        $this->teacher_id = $course->teacher_id !== null ? (string) $course->teacher_id : '';
        $this->resetValidation();
    }

    public function updateCourse(): void
    {
        if ($this->editingCourseId === null) {
            return;
        }

        $this->validate([
            'course_name' => ['required', 'string', 'max:255', Rule::unique('courses', 'name')->ignore($this->editingCourseId)],
            'course_code' => ['required', 'string', 'max:255', Rule::unique('courses', 'code')->ignore($this->editingCourseId)],
            'course_semester' => ['required', Rule::in(['1', '2'])],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
        ]);

        $course = Course::query()->where('program_id', $this->program->id)->findOrFail($this->editingCourseId);
        $course->update([
            'name' => trim($this->course_name),
            'code' => trim($this->course_code),
            'course_semester' => $this->course_semester,
            'teacher_id' => $this->teacher_id === '' ? null : (int) $this->teacher_id,
        ]);

        $this->resetCourseForm();
        CollegeFlash::forNextRequestToo('status', __('Course has been updated.'));
    }

    public function cancelEditCourse(): void
    {
        $this->resetCourseForm();
    }

    public function confirmDeleteCourse(int $courseId): void
    {
        $this->deletingCourseId = $courseId;
        $this->dispatch('open-modal', 'confirm-delete-course-modal');
    }

    public function deleteCourse(): void
    {
        if ($this->deletingCourseId === null) {
            return;
        }
        $courseId = $this->deletingCourseId;
        try {
            Course::query()->where('program_id', $this->program->id)->findOrFail($courseId)->delete();
            if ($this->editingCourseId === $courseId) {
                $this->resetCourseForm();
            }
            $this->deletingCourseId = null;
            CollegeFlash::forNextRequestToo('status', __('Course has been deleted.'));
        } catch (QueryException) {
            $this->deletingCourseId = null;
            CollegeFlash::forNextRequestToo('backup_error', __('Cannot delete this course because related records still exist.'));
        }
    }

    private function resetCourseForm(): void
    {
        $this->editingCourseId = null;
        $this->course_name = '';
        $this->course_code = '';
        $this->course_semester = '1';
        $this->teacher_id = '';
        $this->resetValidation();
    }

    private function generateCourseCode(): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $this->program->name) ?: 'CRS', 0, 4));
        $base = $prefix.$this->form_level.$this->course_semester;
        $next = (int) Course::query()
            ->where('program_id', $this->program->id)
            ->where('year_level', $this->form_level)
            ->where('course_semester', $this->course_semester)
            ->count() + 1;

        do {
            $candidate = $base.str_pad((string) $next, 2, '0', STR_PAD_LEFT);
            $exists = Course::query()->where('code', $candidate)->exists();
            $next++;
        } while ($exists);

        return $candidate;
    }

    public function render(): View
    {
        $courses = Course::query()
            ->where('program_id', $this->program->id)
            ->where('year_level', $this->form_level)
            ->with('teacher.user')
            ->orderBy('code')
            ->get();

        return view('livewire.admin.academic.program-manage-page', [
            'courses' => $courses,
            'teachers' => Teacher::query()->with('user')->orderBy('lastname')->orderBy('othernames')->get(),
        ])->layout('components.layouts.admin', [
            'title' => __('Manage program'),
            'headerTitle' => $this->program->name,
            'headerDescription' => __('Year level :level · Add, edit, or delete courses.', ['level' => (int)$this->form_level * 100]),
        ]);
    }
}
