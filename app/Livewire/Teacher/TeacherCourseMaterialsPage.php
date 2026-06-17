<?php

declare(strict_types=1);

namespace App\Livewire\Teacher;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\TimetableClass;
use App\Support\FilepondPendingFile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;

class TeacherCourseMaterialsPage extends Component
{
    use DispatchesCollegeToasts;

    /** @var list<int>|null */
    private ?array $assignableCourseIdsMemo = null;

    #[Url(except: '')]
    public ?string $course = null;

    public bool $showUploadModal = false;

    public string $uploadTitle = '';

    public string $uploadDescription = '';

    public ?int $uploadCourseId = null;

    public ?string $materialFilePond = null;

    public function mount(): void
    {
        $this->normalizeCourseQueryParam();
    }

    public function updatedCourse(?string $value): void
    {
        if ($value === '') {
            $this->course = null;
        }
    }

    public function openUploadModal(): void
    {
        if (count($this->assignableCourseIds()) === 0) {
            return;
        }

        $this->resetValidation();
        $this->uploadTitle = '';
        $this->uploadDescription = '';
        $this->materialFilePond = null;
        $ids = $this->assignableCourseIds();
        $this->uploadCourseId = count($ids) === 1 ? (int) $ids[0] : null;
        $this->showUploadModal = true;
    }

    public function closeUploadModal(): void
    {
        $this->showUploadModal = false;
        $this->materialFilePond = null;
        $this->resetValidation();
    }

    public function saveMaterial(): void
    {
        $teacher = auth()->user()?->teacher;
        if ($teacher === null) {
            return;
        }

        $assignable = $this->assignableCourseIds();
        if ($assignable === []) {
            return;
        }

        $this->validate([
            'uploadTitle' => ['required', 'string', 'max:255'],
            'uploadDescription' => ['nullable', 'string', 'max:2000'],
            'uploadCourseId' => ['required', 'integer', Rule::in($assignable)],
            'materialFilePond' => ['required', 'string', 'max:500'],
        ]);

        $userId = Auth::id();
        if ($userId === null || ! FilepondPendingFile::assertOwnedPendingPath($this->materialFilePond, $userId)) {
            $this->addError('materialFilePond', __('Uploaded file is invalid.'));

            return;
        }

        $fullPath = Storage::disk('local')->path($this->materialFilePond);
        $file = new UploadedFile(
            $fullPath,
            basename($fullPath),
            mime_content_type($fullPath) ?: null,
            null,
            true
        );

        $relative = $file->store('teachers/course-materials', 'college_uploads');
        Storage::disk('local')->delete($this->materialFilePond);

        $extension = pathinfo($relative, PATHINFO_EXTENSION) ?: 'bin';

        CourseMaterial::query()->create([
            'course_id' => $this->uploadCourseId,
            'teacher_id' => $teacher->id,
            'title' => $this->uploadTitle,
            'description' => $this->uploadDescription !== '' ? $this->uploadDescription : null,
            'file_path' => $relative,
            'file_type' => strtoupper($extension),
            'status' => 'pending',
            'published' => false,
        ]);

        $this->materialFilePond = null;
        $this->showUploadModal = false;
        $this->uploadTitle = '';
        $this->uploadDescription = '';
        $this->uploadCourseId = null;

        $this->collegeToast(__('Material uploaded successfully. It will appear to students after approval.'));
    }

    public function render(): View
    {
        $teacher = auth()->user()?->teacher;

        $assignableIds = $this->assignableCourseIds();
        $assignableCourses = $assignableIds === []
            ? collect()
            : Course::query()->whereIn('id', $assignableIds)->with('program')->orderBy('code')->get();

        $filterCourses = collect();
        if ($teacher !== null) {
            $materialCourseIds = CourseMaterial::query()
                ->where('teacher_id', $teacher->id)
                ->distinct()
                ->pluck('course_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $filterIds = array_values(array_unique(array_merge($assignableIds, $materialCourseIds)));
            if ($filterIds !== []) {
                $filterCourses = Course::query()
                    ->whereIn('id', $filterIds)
                    ->orderBy('code')
                    ->get(['id', 'code', 'name']);
            }
        }

        $rows = collect();
        $fileSizes = [];

        if ($teacher !== null) {
            $query = CourseMaterial::query()
                ->where('teacher_id', $teacher->id)
                ->with('course.program')
                ->orderByDesc('id');

            if ($this->course !== null && $this->course !== '') {
                $query->whereHas('course', fn ($q) => $q->where('code', $this->course));
            }

            $rows = $query->get();
            $disk = Storage::disk('college_uploads');
            foreach ($rows as $row) {
                $p = $row->file_path;
                if ($p !== null && $p !== '' && ! str_contains($p, '..') && $disk->exists($p)) {
                    $fileSizes[$row->id] = $disk->size($p);
                }
            }
        }

        $fileSizeLabels = [];
        foreach ($fileSizes as $id => $bytes) {
            $fileSizeLabels[$id] = $this->formatBytes((int) $bytes);
        }

        return view('livewire.teacher.teacher-course-materials-page', [
            'assignableCourses' => $assignableCourses,
            'filterCourses' => $filterCourses,
            'canUpload' => $assignableCourses->isNotEmpty(),
            'rows' => $rows,
            'fileSizeLabels' => $fileSizeLabels,
        ])->layout('components.layouts.teacher', [
            'title' => __('Course materials'),
            'headerDescription' => __('Manage and share course materials with your students.'),
        ]);
    }

    private function normalizeCourseQueryParam(): void
    {
        if ($this->course === null || $this->course === '') {
            $this->course = null;

            return;
        }

        $teacher = auth()->user()?->teacher;
        if ($teacher === null) {
            $this->course = null;

            return;
        }

        $code = $this->course;
        $allowedByTimetable = Course::query()
            ->whereIn('id', $this->assignableCourseIds())
            ->where('code', $code)
            ->exists();

        $allowedByMaterials = CourseMaterial::query()
            ->where('teacher_id', $teacher->id)
            ->whereHas('course', fn ($q) => $q->where('code', $code))
            ->exists();

        if (! $allowedByTimetable && ! $allowedByMaterials) {
            $this->course = null;
        }
    }

    /**
     * @return list<int>
     */
    private function assignableCourseIds(): array
    {
        if ($this->assignableCourseIdsMemo !== null) {
            return $this->assignableCourseIdsMemo;
        }

        $teacher = auth()->user()?->teacher;
        if ($teacher === null) {
            $this->assignableCourseIdsMemo = [];

            return [];
        }

        $ids = TimetableClass::query()
            ->where('teacher_id', $teacher->id)
            ->whereNotNull('course_id')
            ->distinct()
            ->pluck('course_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $this->assignableCourseIdsMemo = $ids;

        return $ids;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $v = (float) $bytes;
        while ($v >= 1024 && $i < count($units) - 1) {
            $v /= 1024;
            $i++;
        }

        return round($v, $i === 0 ? 0 : 1).' '.$units[$i];
    }
}
