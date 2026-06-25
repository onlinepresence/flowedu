<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Staff;

use App\Models\LeaveRequest;
use App\Models\StaffLeaveType;
use App\Models\SystemAudit;
use App\Models\AcademicSession;
use App\Models\User;
use App\Models\Department;
use App\Support\CollegeFlash;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class StaffLeavesPage extends Component
{
    use WithPagination;

    // View state
    public string $activeTab = 'my_leaves'; // 'my_leaves', 'pending_reviews', 'all_leaves'
    
    // Create Leave form state
    public bool $showCreateModal = false;
    public ?int $staff_leave_type_id = null;
    public string $start_date = '';
    public string $end_date = '';
    public string $reason = '';
    public bool $is_emergency = false;

    // Review form state
    public bool $showReviewModal = false;
    public ?int $selected_request_id = null;
    public string $rejection_reason = '';

    protected $rules = [
        'staff_leave_type_id' => 'required|exists:staff_leave_types,id',
        'start_date' => 'required|date|after_or_equal:today',
        'end_date' => 'required|date|after_or_equal:start_date',
        'reason' => 'required|string|min:5',
        'is_emergency' => 'boolean',
    ];

    public function mount(): void
    {
        // Enforce finance/staff gating
        $user = auth()->user();
        abort_unless($user && ($user->hasAdminPermission('nav_staff_leaves') || $user->hasAdminPermission('nav_staff_home')), 403);
    }

    public function openCreateModal(): void
    {
        $this->resetErrorBag();
        $this->reset(['staff_leave_type_id', 'start_date', 'end_date', 'reason', 'is_emergency']);
        $this->showCreateModal = true;
    }

    public function submitLeaveRequest(): void
    {
        $this->validate();

        $user = auth()->user();

        // Calculate requested days
        $start = new \DateTime($this->start_date);
        $end = new \DateTime($this->end_date);
        $requestedDays = $start->diff($end)->days + 1;

        // Resolve active session
        $session = AcademicSession::query()->where('is_current', true)->first();

        // Determine starting stage and status based on settings & role
        $strategy = \App\Models\Setting::query()
            ->where('setting_key', 'leave_settings.approval_workflow')
            ->value('setting_value') ?? 'standard';

        $startingStage = 'pending_principal';
        if ($strategy === 'departmental') {
            $startingStage = 'pending_hod';
        } elseif ($strategy === 'standard') {
            $startingStage = 'pending_registrar';
        }

        $userRole = $user->adminRoleSlug();
        $status = 'pending';

        if ($userRole === 'principal') {
            $status = 'approved';
            $startingStage = 'approved';
        } elseif ($userRole === 'vice_principal' || $userRole === 'registrar') {
            if ($startingStage === 'pending_registrar') {
                $startingStage = 'pending_principal';
            }
        } elseif ($userRole === 'hod') {
            if ($startingStage === 'pending_hod') {
                $startingStage = 'pending_principal';
            }
        }

        DB::transaction(function () use ($user, $requestedDays, $session, $status, $startingStage) {
            $request = LeaveRequest::create([
                'user_id' => $user->id,
                'staff_leave_type_id' => $this->staff_leave_type_id,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'requested_days' => $requestedDays,
                'status' => $status,
                'current_stage' => $startingStage,
                'reason' => $this->reason,
                'is_emergency' => $this->is_emergency,
                'academic_session_id' => $session?->id,
            ]);

            // Log System Audit
            SystemAudit::create([
                'user_id' => $user->id,
                'action' => 'leave_created',
                'description' => 'Submitted leave request starting from ' . $request->start_date->format('Y-m-d'),
                'auditable_type' => LeaveRequest::class,
                'auditable_id' => $request->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => [
                    'leave_request_id' => $request->id,
                    'status' => $status,
                    'current_stage' => $startingStage,
                ],
            ]);
        });

        $this->showCreateModal = false;
        CollegeFlash::forNextRequestToo('status', __('Leave request submitted successfully.'));
    }

    public function openReviewModal(int $requestId): void
    {
        $this->selected_request_id = $requestId;
        $this->rejection_reason = '';
        $this->showReviewModal = true;
    }

    public function approveRequest(): void
    {
        $user = auth()->user();
        $request = LeaveRequest::findOrFail($this->selected_request_id);

        if (!$this->canUserReview($user, $request)) {
            $this->addError('rejection_reason', __('You are not authorized to review this request at this stage.'));
            return;
        }

        $nextStage = 'approved';

        if ($request->current_stage === 'pending_hod') {
            $nextStage = 'pending_principal';
        } elseif ($request->current_stage === 'pending_registrar') {
            $nextStage = 'pending_principal';
        } elseif ($request->current_stage === 'pending_principal') {
            $nextStage = 'approved';
        }

        DB::transaction(function () use ($user, $request, $nextStage) {
            if ($nextStage === 'approved') {
                $request->update([
                    'status' => 'approved',
                    'current_stage' => 'approved',
                    'reviewer_id' => $user->id,
                    'reviewed_at' => now(),
                ]);

                SystemAudit::create([
                    'user_id' => $user->id,
                    'action' => 'leave_approved',
                    'description' => 'Approved leave request #' . $request->id,
                    'auditable_type' => LeaveRequest::class,
                    'auditable_id' => $request->id,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'metadata' => [
                        'leave_request_id' => $request->id,
                        'applicant_id' => $request->user_id,
                        'approved_by' => $user->id,
                    ],
                ]);
            } else {
                $request->update([
                    'current_stage' => $nextStage,
                ]);

                SystemAudit::create([
                    'user_id' => $user->id,
                    'action' => 'leave_approved_step',
                    'description' => 'Approved step in leave request #' . $request->id . '. Next stage: ' . $nextStage,
                    'auditable_type' => LeaveRequest::class,
                    'auditable_id' => $request->id,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'metadata' => [
                        'leave_request_id' => $request->id,
                        'approved_by' => $user->id,
                        'next_stage' => $nextStage,
                    ],
                ]);
            }
        });

        $this->showReviewModal = false;
        CollegeFlash::forNextRequestToo('status', __('Leave request approved successfully.'));
    }

    public function rejectRequest(): void
    {
        $this->validate([
            'rejection_reason' => 'required|string|min:5',
        ]);

        $user = auth()->user();
        $request = LeaveRequest::findOrFail($this->selected_request_id);

        if (!$this->canUserReview($user, $request)) {
            $this->addError('rejection_reason', __('You are not authorized to review this request at this stage.'));
            return;
        }

        DB::transaction(function () use ($user, $request) {
            $request->update([
                'status' => 'rejected',
                'current_stage' => 'rejected',
                'reviewer_id' => $user->id,
                'reviewed_at' => now(),
                'rejection_reason' => $this->rejection_reason,
            ]);

            SystemAudit::create([
                'user_id' => $user->id,
                'action' => 'leave_rejected',
                'description' => 'Rejected leave request #' . $request->id . '. Reason: ' . $this->rejection_reason,
                'auditable_type' => LeaveRequest::class,
                'auditable_id' => $request->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => [
                    'leave_request_id' => $request->id,
                    'applicant_id' => $request->user_id,
                    'rejected_by' => $user->id,
                    'reason' => $this->rejection_reason,
                ],
            ]);
        });

        $this->showReviewModal = false;
        CollegeFlash::forNextRequestToo('status', __('Leave request rejected.'));
    }

    public function canUserReview(User $user, LeaveRequest $request): bool
    {
        if ($request->status !== 'pending') {
            return false;
        }

        $userRole = $user->adminRoleSlug();

        // Owner/System Admin overrides
        if ($user->isAdminOwner() || $userRole === 'system_admin') {
            return true;
        }

        switch ($request->current_stage) {
            case 'pending_hod':
                $applicant = $request->user;
                $applicantDeptId = null;
                if ($applicant->admin) $applicantDeptId = $applicant->admin->department_id;
                elseif ($applicant->teacher) $applicantDeptId = $applicant->teacher->department_id;
                elseif ($applicant->nonTeachingStaff) $applicantDeptId = $applicant->nonTeachingStaff->department_id;
                elseif ($applicant->student) $applicantDeptId = $applicant->student->department_id;

                if ($applicantDeptId) {
                    $dept = Department::find($applicantDeptId);
                    return $dept && (int)$dept->hod === (int)$user->id;
                }
                return false;

            case 'pending_registrar':
                return in_array($userRole, ['registrar', 'vice_principal', 'secretary'], true) 
                    || $user->hasAdminPermission('review_leaves');

            case 'pending_principal':
                return $userRole === 'principal';

            default:
                return false;
        }
    }

    public function render(): View
    {
        $user = auth()->user();

        // 1. My leaves query
        $myLeavesQuery = LeaveRequest::query()
            ->with(['staffLeaveType', 'reviewer'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        // 2. Pending reviews query
        $pendingReviewsQuery = LeaveRequest::query()
            ->with(['user.admin', 'user.teacher', 'user.nonTeachingStaff', 'staffLeaveType'])
            ->where('status', 'pending');

        // Filter based on who can review what
        $allPending = $pendingReviewsQuery->get();
        $pendingIds = [];
        foreach ($allPending as $req) {
            if ($this->canUserReview($user, $req)) {
                $pendingIds[] = $req->id;
            }
        }

        $pendingReviewsQuery->whereIn('id', $pendingIds)->orderBy('created_at', 'asc');

        // 3. All leaves query (for admins/owners/principals)
        $allLeavesQuery = LeaveRequest::query()
            ->with(['user', 'staffLeaveType', 'reviewer'])
            ->orderBy('created_at', 'desc');

        // Limit visibility of "All Leaves" tab to authorized personnel
        $canViewAllLeaves = $user->isAdminOwner() || in_array($user->adminRoleSlug(), ['principal', 'vice_principal', 'registrar', 'system_admin'], true);

        // Execute paginated queries based on active tab
        $leaves = collect();
        if ($this->activeTab === 'my_leaves') {
            $leaves = $myLeavesQuery->paginate(10, pageName: 'my_leaves_page');
        } elseif ($this->activeTab === 'pending_reviews') {
            $leaves = $pendingReviewsQuery->paginate(10, pageName: 'pending_reviews_page');
        } elseif ($this->activeTab === 'all_leaves' && $canViewAllLeaves) {
            $leaves = $allLeavesQuery->paginate(10, pageName: 'all_leaves_page');
        }

        return view('livewire.admin.staff.staff-leaves-page', [
            'leaves' => $leaves,
            'leaveTypes' => StaffLeaveType::all(),
            'canViewAllLeaves' => $canViewAllLeaves,
        ])->layout('components.layouts.admin', [
            'title' => __('Staff Leave Management'),
            'headerTitle' => __('Staff Leave Management'),
            'headerDescription' => __('Submit leave requests, track status, and manage multi-stage sign-offs.'),
        ]);
    }
}
