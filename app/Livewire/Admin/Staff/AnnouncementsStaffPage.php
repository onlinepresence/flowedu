<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Staff;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\Course;
use App\Models\Announcement;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class AnnouncementsStaffPage extends Component
{
    use DispatchesCollegeToasts;
    use WithPagination;

    // Filters
    public string $search = '';

    public string $filterStatus = 'all';

    public ?int $filterCourse = null;

    // Review Modal States
    public bool $showReviewModal = false;

    public ?int $selectedAnnouncementId = null;

    public string $rejectionReason = '';

    // Delete Confirmation
    public bool $showDeleteModal = false;

    public ?int $deletingAnnouncementId = null;

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
        $announcement = Announcement::query()->findOrFail($id);
        $this->selectedAnnouncementId = $announcement->id;
        $this->rejectionReason = (string) ($announcement->rejection_reason ?? '');
        $this->showReviewModal = true;
    }

    public function closeReviewModal(): void
    {
        $this->showReviewModal = false;
        $this->selectedAnnouncementId = null;
        $this->rejectionReason = '';
    }

    public function approveAnnouncement(): void
    {
        if ($this->selectedAnnouncementId === null) {
            return;
        }

        $announcement = Announcement::query()->findOrFail($this->selectedAnnouncementId);
        $announcement->forceFill([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_date' => now(),
            'rejection_reason' => null,
        ])->save();

        $this->closeReviewModal();
        $this->collegeToast(__('Announcement approved successfully.'));
    }

    public function rejectAnnouncement(): void
    {
        $this->validate([
            'rejectionReason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        if ($this->selectedAnnouncementId === null) {
            return;
        }

        $announcement = Announcement::query()->findOrFail($this->selectedAnnouncementId);
        $announcement->forceFill([
            'status' => 'rejected',
            'approved_by' => null,
            'approved_date' => null,
            'rejection_reason' => $this->rejectionReason,
        ])->save();

        $this->closeReviewModal();
        $this->collegeToast(__('Announcement rejected with reason.'));
    }

    public function openDeleteModal(int $id): void
    {
        $this->deletingAnnouncementId = $id;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingAnnouncementId = null;
    }

    public function confirmDelete(): void
    {
        if ($this->deletingAnnouncementId === null) {
            return;
        }

        Announcement::query()->whereKey($this->deletingAnnouncementId)->delete();

        $this->closeDeleteModal();
        $this->resetPage();
        $this->collegeToast(__('Announcement deleted successfully.'));
    }

    public function render(): View
    {
        $query = Announcement::query();

        if ($this->search !== '') {
            $query->where(function ($q): void {
                $term = '%'.$this->search.'%';
                $q->where('title', 'like', $term)
                  ->orWhere('body', 'like', $term)
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

        $selectedAnnouncement = $this->selectedAnnouncementId
            ? Announcement::query()->with(['course', 'teacher'])->find($this->selectedAnnouncementId)
            : null;

        return view('livewire.admin.staff.announcements-staff-page', [
            'rows' => $rows,
            'courses' => $courses,
            'selectedAnnouncement' => $selectedAnnouncement,
        ])->layout('components.layouts.admin', [
            'title' => __('Teacher Announcements Review'),
            'headerTitle' => __('Teacher Announcements Review'),
            'headerDescription' => __('Audit announcements posted by teachers before they are broadcasted to class feeds.'),
        ]);
    }
}
