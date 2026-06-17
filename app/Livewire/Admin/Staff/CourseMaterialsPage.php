<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Staff;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\Course;
use App\Models\CourseMaterial;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class CourseMaterialsPage extends Component
{
    use DispatchesCollegeToasts;
    use WithPagination;

    // Filters
    public string $search = '';

    public string $filterStatus = 'all';

    public ?int $filterCourse = null;

    // Review Modal States
    public bool $showReviewModal = false;

    public ?int $selectedMaterialId = null;

    public string $rejectionReason = '';

    // Delete Confirmation
    public bool $showDeleteModal = false;

    public ?int $deletingMaterialId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => 'all'],
        'filterCourse' => ['except' => null],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCourse(): void
    {
        $this->resetPage();
    }

    public function openReviewModal(int $id): void
    {
        $material = CourseMaterial::query()->findOrFail($id);
        $this->selectedMaterialId = $material->id;
        $this->rejectionReason = (string) ($material->rejection_reason ?? '');
        $this->showReviewModal = true;
    }

    public function closeReviewModal(): void
    {
        $this->showReviewModal = false;
        $this->selectedMaterialId = null;
        $this->rejectionReason = '';
    }

    public function approveMaterial(): void
    {
        if ($this->selectedMaterialId === null) {
            return;
        }

        $material = CourseMaterial::query()->findOrFail($this->selectedMaterialId);
        $material->forceFill([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_date' => now(),
            'rejection_reason' => null,
        ])->save();

        $this->closeReviewModal();
        $this->collegeToast(__('Material approved successfully.'));
    }

    public function rejectMaterial(): void
    {
        $this->validate([
            'rejectionReason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        if ($this->selectedMaterialId === null) {
            return;
        }

        $material = CourseMaterial::query()->findOrFail($this->selectedMaterialId);
        $material->forceFill([
            'status' => 'rejected',
            'approved_by' => null,
            'approved_date' => null,
            'rejection_reason' => $this->rejectionReason,
        ])->save();

        $this->closeReviewModal();
        $this->collegeToast(__('Material rejected with reason.'));
    }

    public function openDeleteModal(int $id): void
    {
        $this->deletingMaterialId = $id;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingMaterialId = null;
    }

    public function confirmDelete(): void
    {
        if ($this->deletingMaterialId === null) {
            return;
        }

        CourseMaterial::query()->whereKey($this->deletingMaterialId)->delete();

        $this->closeDeleteModal();
        $this->resetPage();
        $this->collegeToast(__('Material deleted successfully.'));
    }

    public function render(): View
    {
        $query = CourseMaterial::query();

        if ($this->search !== '') {
            $query->where(function ($q): void {
                $term = '%'.$this->search.'%';
                $q->where('title', 'like', $term)
                  ->orWhere('description', 'like', $term)
                  ->orWhereHas('course', function ($cq) use ($term): void {
                      $cq->where('code', 'like', $term)
                        ->orWhere('name', 'like', $term);
                  })
                  ->orWhereHas('teacher', function ($tq) use ($term): void {
                      $tq->where('lastname', 'like', $term)
                        ->orWhere('othernames', 'like', $term);
                  });
            });
        }

        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterCourse !== null && $this->filterCourse > 0) {
            $query->where('course_id', $this->filterCourse);
        }

        $rows = $query->with(['course', 'teacher', 'approvedBy'])
            ->orderByDesc('id')
            ->paginate(20);

        $courses = Course::query()->orderBy('code')->get();

        $selectedMaterial = $this->selectedMaterialId
            ? CourseMaterial::query()->with(['course', 'teacher'])->find($this->selectedMaterialId)
            : null;

        return view('livewire.admin.staff.course-materials-page', [
            'rows' => $rows,
            'courses' => $courses,
            'selectedMaterial' => $selectedMaterial,
        ])->layout('components.layouts.admin', [
            'title' => __('Course Materials Review'),
            'headerTitle' => __('Course Materials Review'),
            'headerDescription' => __('Review course syllabus notes, files and slides uploaded by the lecturing staff.'),
        ]);
    }
}
