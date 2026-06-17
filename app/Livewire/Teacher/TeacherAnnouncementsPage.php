<?php

declare(strict_types=1);

namespace App\Livewire\Teacher;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\Announcement;
use App\Models\AcademicSession;
use App\Models\TeacherAssignment;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;

class TeacherAnnouncementsPage extends Component
{
    use DispatchesCollegeToasts;
    use WithPagination;

    // Form fields
    public ?int $announcementId = null;
    public ?int $courseId = null;
    public string $title = '';
    public string $body = '';

    // Deletion tracking
    public ?int $deletingId = null;

    protected $queryString = [
        'page' => ['except' => 1],
    ];

    protected $rules = [
        'courseId' => ['required', 'exists:courses,id'],
        'title' => ['required', 'string', 'min:3', 'max:255'],
        'body' => ['required', 'string', 'min:10'],
    ];

    #[On('trigger-create-modal')]
    public function openCreateModalFromEvent(): void
    {
        $this->openCreateModal(null);
    }

    public function openCreateModal(?int $id = null): void
    {
        $this->resetErrorBag();

        if ($id) {
            $ann = Announcement::findOrFail($id);
            abort_unless((int) $ann->teacher_id === (int) auth()->user()?->teacher?->id, 403);

            $this->announcementId = $ann->id;
            $this->courseId = $ann->course_id;
            $this->title = $ann->title;
            $this->body = $ann->body;
        } else {
            $this->resetForm();
        }

        $this->dispatch('open-modal', 'announcement-create-modal');
    }

    public function resetForm(): void
    {
        $this->announcementId = null;
        $this->courseId = null;
        $this->title = '';
        $this->body = '';
    }

    public function saveAnnouncement(bool $asDraft = false): void
    {
        $this->validate();

        $teacher = auth()->user()?->teacher;
        if (!$teacher) {
            return;
        }

        // Get the active session for the course/teacher assignment
        $asg = TeacherAssignment::query()
            ->where('teacher_id', $teacher->id)
            ->where('course_id', $this->courseId)
            ->first();

        $sessionId = $asg ? $asg->session_id : AcademicSession::query()->where('is_active', true)->value('id');
        if (!$sessionId) {
            $sessionId = AcademicSession::query()->orderByDesc('id')->value('id');
        }

        $status = $asDraft ? 'draft' : 'pending';
        $published = !$asDraft;

        Announcement::query()->updateOrCreate(
            [
                'id' => $this->announcementId,
            ],
            [
                'teacher_id' => $teacher->id,
                'course_id' => $this->courseId,
                'academic_session_id' => $sessionId,
                'title' => $this->title,
                'body' => $this->body,
                'status' => $status,
                'published' => $published,
                'rejection_reason' => null,
            ]
        );

        $this->dispatch('close-modal', 'announcement-create-modal');
        $this->resetForm();
        $this->resetPage();
        $this->collegeToast($asDraft ? __('Draft saved successfully.') : __('Announcement submitted for review.'));
    }

    public function openDeleteModal(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', 'delete-announcement-modal');
    }

    public function deleteAnnouncement(): void
    {
        if ($this->deletingId) {
            $ann = Announcement::findOrFail($this->deletingId);
            abort_unless((int) $ann->teacher_id === (int) auth()->user()?->teacher?->id, 403);

            $ann->delete();
            $this->deletingId = null;
            $this->resetPage();
            $this->collegeToast(__('Announcement deleted successfully.'));
        }
    }

    public function render(): View
    {
        $teacher = auth()->user()?->teacher;

        $rows = $teacher
            ? Announcement::query()
                ->where('teacher_id', $teacher->id)
                ->with(['course', 'academicSession'])
                ->orderByDesc('id')
                ->paginate(10)
            : collect();

        // Get class choices from teacher assignments
        $assignedCourses = $teacher
            ? TeacherAssignment::query()
                ->where('teacher_id', $teacher->id)
                ->with('course')
                ->get()
                ->pluck('course')
                ->unique('id')
                ->values()
            : collect();

        return view('livewire.teacher.teacher-announcements-page', [
            'rows' => $rows,
            'assignedCourses' => $assignedCourses,
        ])->layout('components.layouts.teacher', [
            'title' => __('Announcements Feed'),
            'headerDescription' => __('Create and manage announcements broadcasted to your students.'),
        ]);
    }
}
