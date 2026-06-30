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
    public string $activeTab = 'my_leaves'; // 'my_leaves', 'pending_reviews', 'all_leaves', 'leave_configurations', 'staff_assignments'
    
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

    // Configuration & Management state
    public bool $canManageConfigs = false;
    public string $submission_start_date = '';
    public string $submission_end_date = '';
    public bool $emergency_leave_enabled = false;

    // Leave Type CRUD state
    public bool $showTypeModal = false;
    public ?int $editing_type_id = null;
    public string $type_name = '';
    public int $type_max_days = 10;

    // Staff assignment search/pagination state
    public string $searchStaff = '';
    public string $filterStaffType = 'all';
    public string $filterStaffDepartment = 'all';
    public bool $canManageStaffAssignments = false;

    public function mount(): void
    {
        // Enforce finance/staff gating
        $user = auth()->user();
        abort_unless($user && ($user->hasAdminPermission('nav_staff_leaves') || $user->hasAdminPermission('nav_staff_home')), 403);

        $userRole = $user->adminRoleSlug();
        $this->canManageConfigs = $user->isAdminOwner() || in_array($userRole, ['registrar', 'system_admin'], true);
        $this->canManageStaffAssignments = $user->isAdminOwner() || in_array($userRole, ['registrar', 'system_admin', 'principal', 'hod'], true);

        // Load current configurations
        $this->submission_start_date = (string) (\App\Models\Setting::query()->where('setting_key', 'leave_settings.submission_start_date')->value('setting_value') ?? '');
        $this->submission_end_date = (string) (\App\Models\Setting::query()->where('setting_key', 'leave_settings.submission_end_date')->value('setting_value') ?? '');
        $this->emergency_leave_enabled = \App\Models\Setting::query()->where('setting_key', 'leave_settings.emergency_leave_enabled')->value('setting_value') === '1';
    }

    public function getEntitlements(): array
    {
        $user = auth()->user();
        $total = $user->staffLeaveType->max_leave_days ?? 0;
        
        $session = AcademicSession::query()->where('is_current', true)->first();

        $approved = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->when($session, fn($q) => $q->where('academic_session_id', $session->id))
            ->sum('requested_days');

        $pending = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->when($session, fn($q) => $q->where('academic_session_id', $session->id))
            ->sum('requested_days');

        $remaining = max(0, $total - $approved);

        return [
            'total' => $total,
            'approved' => $approved,
            'pending' => $pending,
            'remaining' => $remaining,
        ];
    }

    public function openCreateModal(): void
    {
        $this->resetErrorBag();
        $this->reset(['staff_leave_type_id', 'start_date', 'end_date', 'reason', 'is_emergency']);
        $this->showCreateModal = true;
    }

    public function submitLeaveRequest(): void
    {
        $rules = [
            'staff_leave_type_id' => 'required|exists:staff_leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|min:5',
            'is_emergency' => 'boolean',
        ];

        if (!$this->is_emergency) {
            $rules['start_date'] .= '|after_or_equal:today';
        }

        $this->validate($rules);

        // Submission window constraints
        $bypassWindow = $this->is_emergency && $this->emergency_leave_enabled;

        if (!$bypassWindow) {
            if ($this->submission_start_date && $this->start_date < $this->submission_start_date) {
                $this->addError('start_date', __('Leave requests can only be submitted within the active submission window starting :date.', ['date' => $this->submission_start_date]));
                return;
            }
            if ($this->submission_end_date && $this->end_date > $this->submission_end_date) {
                $this->addError('end_date', __('Leave requests can only be submitted within the active submission window ending :date.', ['date' => $this->submission_end_date]));
                return;
            }
        }

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

    public function getApplicantStats(): array
    {
        $request = $this->selected_request_id ? LeaveRequest::find($this->selected_request_id) : null;
        if (!$request) {
            return [];
        }

        $userId = $request->user_id;
        $user = User::with('staffLeaveType')->find($userId);
        if (!$user) {
            return [];
        }

        $session = AcademicSession::query()->where('is_current', true)->first();

        // Total approved days in the current academic session
        $approvedDays = LeaveRequest::query()
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->when($session, fn($q) => $q->where('academic_session_id', $session->id))
            ->sum('requested_days');

        // Total pending days in the current academic session (excluding current under review)
        $pendingDays = LeaveRequest::query()
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->where('id', '!=', $request->id)
            ->when($session, fn($q) => $q->where('academic_session_id', $session->id))
            ->sum('requested_days');

        $maxDays = $user->staffLeaveType->max_leave_days ?? 0;
        $remainingDays = max(0, $maxDays - $approvedDays);

        // History of leaves for this user in the current session
        $history = LeaveRequest::query()
            ->with('staffLeaveType')
            ->where('user_id', $userId)
            ->where('id', '!=', $request->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'request' => $request,
            'user' => $user,
            'max_days' => $maxDays,
            'approved_days' => $approvedDays,
            'pending_days' => $pendingDays,
            'remaining_days' => $remainingDays,
            'history' => $history,
        ];
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

    public function saveConfigurations(): void
    {
        abort_unless($this->canManageConfigs, 403);

        $this->validate([
            'submission_start_date' => 'nullable|date',
            'submission_end_date' => 'nullable|date|after_or_equal:submission_start_date',
            'emergency_leave_enabled' => 'boolean',
        ]);

        \App\Models\Setting::updateOrCreate(
            ['setting_key' => 'leave_settings.submission_start_date'],
            ['setting_value' => $this->submission_start_date ?: '', 'category' => 'leave_settings', 'data_type' => 'string']
        );

        \App\Models\Setting::updateOrCreate(
            ['setting_key' => 'leave_settings.submission_end_date'],
            ['setting_value' => $this->submission_end_date ?: '', 'category' => 'leave_settings', 'data_type' => 'string']
        );

        \App\Models\Setting::updateOrCreate(
            ['setting_key' => 'leave_settings.emergency_leave_enabled'],
            ['setting_value' => $this->emergency_leave_enabled ? '1' : '0', 'category' => 'leave_settings', 'data_type' => 'boolean']
        );

        CollegeFlash::forNextRequestToo('status', __('Leave configurations updated successfully.'));
    }

    public function openTypeModal(?int $id = null): void
    {
        abort_unless($this->canManageConfigs, 403);

        $this->resetErrorBag();
        $this->editing_type_id = $id;

        if ($id) {
            $type = StaffLeaveType::findOrFail($id);
            $this->type_name = $type->name;
            $this->type_max_days = $type->max_leave_days;
        } else {
            $this->type_name = '';
            $this->type_max_days = 10;
        }

        $this->showTypeModal = true;
    }

    public function saveLeaveType(): void
    {
        abort_unless($this->canManageConfigs, 403);

        $this->validate([
            'type_name' => 'required|string|max:255|unique:staff_leave_types,name,' . ($this->editing_type_id ?: 'NULL'),
            'type_max_days' => 'required|integer|min:1',
        ]);

        StaffLeaveType::updateOrCreate(
            ['id' => $this->editing_type_id],
            [
                'name' => $this->type_name,
                'max_leave_days' => $this->type_max_days,
            ]
        );

        $this->showTypeModal = false;
        CollegeFlash::forNextRequestToo('status', __('Staff Leave Type saved successfully.'));
    }

    public function deleteLeaveType(int $id): void
    {
        abort_unless($this->canManageConfigs, 403);

        $type = StaffLeaveType::findOrFail($id);
        if ($type->leaveRequests()->exists()) {
            $this->addError('type_name', __('Cannot delete this leave type because it has active requests.'));
            return;
        }

        $type->delete();
        CollegeFlash::forNextRequestToo('status', __('Staff Leave Type deleted.'));
    }

    public function assignLeaveType(int $userId, ?int $leaveTypeId): void
    {
        abort_unless($this->canManageStaffAssignments, 403);

        $user = User::findOrFail($userId);
        $user->update(['staff_leave_type_id' => $leaveTypeId ?: null]);

        $this->dispatch('toast', message: __('Staff leave type updated successfully.'), type: 'success');
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
        $staffMembers = collect();

        if ($this->activeTab === 'my_leaves') {
            $leaves = $myLeavesQuery->paginate(10, pageName: 'my_leaves_page');
        } elseif ($this->activeTab === 'pending_reviews') {
            $leaves = $pendingReviewsQuery->paginate(10, pageName: 'pending_reviews_page');
        } elseif ($this->activeTab === 'all_leaves' && $canViewAllLeaves) {
            $leaves = $allLeavesQuery->paginate(10, pageName: 'all_leaves_page');
        } elseif ($this->activeTab === 'staff_assignments' && $this->canManageStaffAssignments) {
            $userRole = $user->adminRoleSlug();
            $isHod = $userRole === 'hod';
            $hodDeptId = $user->admin?->department_id;

            $staffQuery = User::query()
                ->whereIn('type', ['admin', 'teacher', 'staff'])
                ->with(['admin.department', 'teacher.department', 'nonTeachingStaff.department', 'staffLeaveType']);

            // 1. Role-based scoping (HOD Mode vs HR Mode)
            if ($isHod) {
                if ($hodDeptId) {
                    $staffQuery->where(function ($q) use ($hodDeptId) {
                        $q->whereHas('admin', fn($sub) => $sub->where('department_id', $hodDeptId))
                          ->orWhereHas('teacher', fn($sub) => $sub->where('department_id', $hodDeptId))
                          ->orWhereHas('nonTeachingStaff', fn($sub) => $sub->where('department_id', $hodDeptId));
                    });
                } else {
                    $staffQuery->whereRaw('1 = 0');
                }
            } else {
                // HR Mode (or owner/admin/principal) - filter by department if chosen
                if ($this->filterStaffDepartment === 'none') {
                    $staffQuery->where(function ($q) {
                        $q->where(fn($sub) => $sub->whereHas('admin', fn($inner) => $inner->whereNull('department_id'))
                                                  ->orWhereDoesntHave('admin'))
                          ->where(fn($sub) => $sub->whereHas('teacher', fn($inner) => $inner->whereNull('department_id'))
                                                  ->orWhereDoesntHave('teacher'))
                          ->where(fn($sub) => $sub->whereHas('nonTeachingStaff', fn($inner) => $inner->whereNull('department_id'))
                                                  ->orWhereDoesntHave('nonTeachingStaff'));
                    });
                } elseif ($this->filterStaffDepartment !== 'all') {
                    $deptId = (int) $this->filterStaffDepartment;
                    $staffQuery->where(function ($q) use ($deptId) {
                        $q->whereHas('admin', fn($sub) => $sub->where('department_id', $deptId))
                          ->orWhereHas('teacher', fn($sub) => $sub->where('department_id', $deptId))
                          ->orWhereHas('nonTeachingStaff', fn($sub) => $sub->where('department_id', $deptId));
                    });
                }
            }

            // 2. Filter by staff type (Teaching vs Non-Teaching)
            if ($this->filterStaffType === 'teaching') {
                $staffQuery->where('type', 'teacher');
            } elseif ($this->filterStaffType === 'non_teaching') {
                $staffQuery->whereIn('type', ['staff', 'admin']);
            }

            // 3. Search filter
            if ($this->searchStaff !== '') {
                $q = '%' . $this->searchStaff . '%';
                $staffQuery->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', $q)
                        ->orWhere('email', 'like', $q)
                        ->orWhere('username', 'like', $q);
                });
            }

            $staffMembers = $staffQuery->orderBy('name')
                ->paginate(15, pageName: 'staff_assignments_page');
        }

        return view('livewire.admin.staff.staff-leaves-page', [
            'leaves' => $leaves,
            'leaveTypes' => StaffLeaveType::all(),
            'canViewAllLeaves' => $canViewAllLeaves,
            'staffMembers' => $staffMembers,
            'departments' => Department::query()->orderBy('name')->get(),
            'canManageStaffAssignments' => $this->canManageStaffAssignments,
        ])->layout('components.layouts.admin', [
            'title' => __('Staff Leave Management'),
            'headerTitle' => __('Staff Leave Management'),
            'headerDescription' => __('Submit leave requests, track status, and manage multi-stage sign-offs.'),
        ]);
    }
}
