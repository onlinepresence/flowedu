<?php

declare(strict_types=1);

namespace App\Livewire\Teacher;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\SharedLessonPlan;
use App\Support\FilepondPendingFile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class TeacherLessonPlansPage extends Component
{
    use DispatchesCollegeToasts, WithFileUploads, WithPagination;

    public string $search = '';
    public string $title = '';
    public string $description = '';
    public ?string $planFilePond = null;

    public bool $showUploadModal = false;

    public function mount(): void
    {
        $teacher = auth()->user()?->teacher;
        abort_unless($teacher !== null, 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openUploadModal(): void
    {
        $this->resetValidation();
        $this->title = '';
        $this->description = '';
        $this->planFilePond = null;
        $this->showUploadModal = true;
        $this->dispatch('open-modal', 'upload-plan-modal');
    }

    public function closeUploadModal(): void
    {
        $this->showUploadModal = false;
        $this->dispatch('close-modal', 'upload-plan-modal');
    }

    public function savePlan(): void
    {
        $teacher = auth()->user()?->teacher;
        if (!$teacher || !$teacher->department_id) {
            $this->collegeToast(__('You must belong to a department to upload lesson plans.'), 'danger');
            return;
        }

        $this->validate([
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'planFilePond' => ['required', 'string', 'max:500'],
        ]);

        $userId = auth()->id();
        if ($userId === null || !FilepondPendingFile::assertOwnedPendingPath($this->planFilePond, $userId)) {
            $this->addError('planFilePond', __('Uploaded file is invalid.'));
            return;
        }

        $fullPath = Storage::disk('local')->path($this->planFilePond);
        $file = new UploadedFile(
            $fullPath,
            basename($fullPath),
            mime_content_type($fullPath) ?: null,
            null,
            true
        );

        $path = $file->store('lesson_plans', 'local');
        Storage::disk('local')->delete($this->planFilePond);

        SharedLessonPlan::create([
            'teacher_id' => $teacher->id,
            'department_id' => $teacher->department_id,
            'title' => $this->title,
            'description' => $this->description,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
        ]);

        $this->showUploadModal = false;
        $this->dispatch('close-modal', 'upload-plan-modal');
        $this->title = '';
        $this->description = '';
        $this->planFilePond = null;

        $this->collegeToast(__('Lesson plan shared successfully.'));
    }

    public function downloadPlan(int $id)
    {
        $plan = SharedLessonPlan::findOrFail($id);
        $teacher = auth()->user()?->teacher;

        if (!$teacher || (int)$teacher->department_id !== (int)$plan->department_id) {
            abort(403);
        }

        if (Storage::disk('local')->exists($plan->file_path)) {
            return Storage::disk('local')->download($plan->file_path, $plan->file_name);
        }

        // Check if demo file
        if ($plan->file_path === 'demo/lesson_plan_template.pdf') {
            // Return dummy download
            return response()->streamDownload(function () {
                echo "Demo Lesson Plan File Content";
            }, $plan->file_name);
        }

        $this->collegeToast(__('File not found on storage.'), 'danger');
        return null;
    }

    public function deletePlan(int $id): void
    {
        $plan = SharedLessonPlan::findOrFail($id);
        $teacher = auth()->user()?->teacher;

        if (!$teacher || (int)$plan->teacher_id !== (int)$teacher->id) {
            abort(403);
        }

        if (Storage::disk('local')->exists($plan->file_path)) {
            Storage::disk('local')->delete($plan->file_path);
        }

        $plan->delete();
        $this->collegeToast(__('Lesson plan deleted successfully.'), 'danger');
    }

    public function render(): View
    {
        $teacher = auth()->user()?->teacher;
        $department = $teacher?->department;

        $query = SharedLessonPlan::with('teacher.user')
            ->where('department_id', $teacher?->department_id ?? 0);

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%')
                    ->orWhereHas('teacher.user', function ($uq) {
                        $uq->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        $plans = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('livewire.teacher.teacher-lesson-plans-page', [
            'plans' => $plans,
            'departmentName' => $department?->name ?? __('Unknown Department'),
        ])->layout('components.layouts.teacher', [
            'title' => __('Departmental Lesson Plans') . ' — ' . ($department?->name ?? __('Unknown Department')),
            'headerDescription' => __('Share lesson templates and pedagogy resources with colleagues within your department.'),
        ]);
    }
}
