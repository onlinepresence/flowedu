<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Staff;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\Department;
use App\Models\StaffAssignment;
use App\Models\User;
use App\Models\StaffRole;
use App\Models\UserRole;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class StaffAssignmentsListPage extends Component
{
    use DispatchesCollegeToasts;
    use WithPagination;

    public string $role = '';

    public string $role_description = '';

    public string $role_status = 'active';

    public string $search = '';

    public string $filterRole = 'all';

    public string $filterDepartment = 'all';

    public string $filterStatus = 'all';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterDepartment' => ['except' => 'all'],
        'filterRole' => ['except' => 'all'],
        'filterStatus' => ['except' => 'all'],
    ];

    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showEndModal = false;

    public bool $showRevokeRoleModal = false;

    public ?int $editingId = null;

    public ?int $endingId = null;

    public ?int $revokingAssignmentId = null;

    public ?int $staff_id = null;

    public ?int $department_id = null;

    public string $office = '';

    public string $position_title = '';

    public string $assignment_date = '';

    public string $status = 'active';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterDepartment(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterRole(): void
    {
        $this->resetPage();
    }

    public function openRevokeRoleModal(int $assignmentId): void
    {
        $this->revokingAssignmentId = $assignmentId;
        $this->showRevokeRoleModal = true;
    }

    public function closeRevokeRoleModal(): void
    {
        $this->revokingAssignmentId = null;
        $this->showRevokeRoleModal = false;
    }

    public function confirmRevokeRole(): void
    {
        if ($this->revokingAssignmentId === null) {
            return;
        }

        $row = StaffAssignment::query()->findOrFail($this->revokingAssignmentId);
        if ($row->staff_id) {
            StaffRole::query()->where('staff_id', $row->staff_id)->update(['status' => 'inactive']);
            $this->collegeToast(__('Administrative role revoked successfully.'));
        }

        $this->closeRevokeRoleModal();
    }

    public function openCreateModal(): void
    {
        $this->resetAssignmentForm();
        $this->assignment_date = now()->format('Y-m-d');
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->resetAssignmentForm();
    }

    public function openEditModal(int $id): void
    {
        $row = StaffAssignment::query()->findOrFail($id);
        $this->editingId = $row->id;
        $this->staff_id = $row->staff_id;
        $this->department_id = $row->department_id;
        $this->office = $row->office;
        $this->position_title = $row->position_title;
        $this->assignment_date = $row->assignment_date?->format('Y-m-d') ?? '';
        $this->status = (string) $row->status;

        $roleRow = StaffRole::query()->where('staff_id', $row->staff_id)->first();
        if ($roleRow) {
            $this->role = $roleRow->role;
            $this->role_description = $roleRow->description ?? '';
            $this->role_status = $roleRow->status;
        } else {
            $this->role = '';
            $this->role_description = '';
            $this->role_status = 'active';
        }

        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingId = null;
        $this->resetAssignmentForm();
    }

    public function openEndModal(int $id): void
    {
        $this->endingId = $id;
        $this->showEndModal = true;
    }

    public function closeEndModal(): void
    {
        $this->showEndModal = false;
        $this->endingId = null;
    }

    public function saveCreate(): void
    {
        $validated = $this->validate([
            'staff_id' => ['required', 'exists:users,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'office' => ['required', 'string', 'max:191'],
            'position_title' => ['required', 'string', 'max:191'],
            'assignment_date' => ['nullable', 'date'],
            'status' => ['required', 'in:active,inactive,ended'],
            'role' => ['nullable', 'string', 'max:128'],
            'role_description' => ['nullable', 'string'],
            'role_status' => ['required', 'in:active,inactive'],
        ]);

        $user = User::query()->findOrFail((int) $validated['staff_id']);
        if ($user->type !== 'staff') {
            $this->addError('staff_id', __('Select a user with the staff account type.'));

            return;
        }

        StaffAssignment::query()->create([
            'staff_id' => (int) $validated['staff_id'],
            'department_id' => (int) $validated['department_id'],
            'office' => $validated['office'],
            'position_title' => $validated['position_title'],
            'assignment_date' => $validated['assignment_date'] !== '' ? $validated['assignment_date'] : null,
            'assigned_by' => auth()->id(),
            'status' => $validated['status'],
        ]);

        if (!empty($validated['role'])) {
            StaffRole::query()->updateOrCreate(
                ['staff_id' => (int) $validated['staff_id']],
                [
                    'role' => $validated['role'],
                    'department_id' => (int) $validated['department_id'],
                    'description' => $validated['role_description'] !== '' ? $validated['role_description'] : null,
                    'status' => $validated['role_status'],
                    'assigned_by' => auth()->id(),
                    'assigned_date' => now()->format('Y-m-d'),
                ]
            );
        } else {
            StaffRole::query()->where('staff_id', (int) $validated['staff_id'])->update(['status' => 'inactive']);
        }

        $this->closeCreateModal();
        $this->resetPage();
        $this->collegeToast(__('Assignment created.'));
    }

    public function saveEdit(): void
    {
        if ($this->editingId === null) {
            return;
        }

        $validated = $this->validate([
            'staff_id' => ['required', 'exists:users,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'office' => ['required', 'string', 'max:191'],
            'position_title' => ['required', 'string', 'max:191'],
            'assignment_date' => ['nullable', 'date'],
            'status' => ['required', 'in:active,inactive,ended'],
            'role' => ['nullable', 'string', 'max:128'],
            'role_description' => ['nullable', 'string'],
            'role_status' => ['required', 'in:active,inactive'],
        ]);

        $user = User::query()->findOrFail((int) $validated['staff_id']);
        if ($user->type !== 'staff') {
            $this->addError('staff_id', __('Select a user with the staff account type.'));

            return;
        }

        $row = StaffAssignment::query()->findOrFail($this->editingId);
        $row->forceFill([
            'staff_id' => (int) $validated['staff_id'],
            'department_id' => (int) $validated['department_id'],
            'office' => $validated['office'],
            'position_title' => $validated['position_title'],
            'assignment_date' => $validated['assignment_date'] !== '' ? $validated['assignment_date'] : null,
            'status' => $validated['status'],
        ])->save();

        if (!empty($validated['role'])) {
            StaffRole::query()->updateOrCreate(
                ['staff_id' => (int) $validated['staff_id']],
                [
                    'role' => $validated['role'],
                    'department_id' => (int) $validated['department_id'],
                    'description' => $validated['role_description'] !== '' ? $validated['role_description'] : null,
                    'status' => $validated['role_status'],
                    'assigned_by' => auth()->id(),
                    'assigned_date' => now()->format('Y-m-d'),
                ]
            );
        } else {
            StaffRole::query()->where('staff_id', (int) $validated['staff_id'])->update(['status' => 'inactive']);
        }

        $this->closeEditModal();
        $this->collegeToast(__('Assignment updated.'));
    }

    public function confirmEnd(): void
    {
        if ($this->endingId === null) {
            return;
        }

        $row = StaffAssignment::query()->findOrFail($this->endingId);
        $row->forceFill(['status' => 'ended'])->save();

        $this->closeEndModal();
        $this->collegeToast(__('Assignment ended.'));
    }

    private function resetAssignmentForm(): void
    {
        $this->staff_id = null;
        $this->department_id = null;
        $this->office = '';
        $this->position_title = '';
        $this->assignment_date = '';
        $this->status = 'active';
        $this->role = '';
        $this->role_description = '';
        $this->role_status = 'active';
        $this->resetValidation();
    }

    public function render(): View
    {
        $query = StaffAssignment::query()
            ->with(['staff.staffRoles.roleModel', 'department', 'assigner']);

        if ($this->search !== '') {
            $q = '%' . $this->search . '%';
            $query->where(function ($sub) use ($q) {
                $sub->where('office', 'like', $q)
                    ->orWhere('position_title', 'like', $q)
                    ->orWhereHas('staff', function ($u) use ($q) {
                        $u->where('name', 'like', $q)
                          ->orWhere('email', 'like', $q)
                          ->orWhere('username', 'like', $q);
                    });
            });
        }

        if ($this->filterDepartment !== 'all') {
            $query->where('department_id', (int) $this->filterDepartment);
        }

        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterRole !== 'all') {
            $query->whereHas('staff.staffRoles', function ($r) {
                $r->where('role', $this->filterRole)->where('status', 'active');
            });
        }

        $rows = $query->orderByDesc('assignment_date')
            ->paginate(20);

        $staffUsers = User::query()
            ->where('type', 'staff')
            ->orderByRaw('COALESCE(username, email)')
            ->limit(500)
            ->get();

        $departments = Department::query()->orderBy('name')->get();

        $roleOptions = UserRole::query()
            ->whereNotIn('name', ['owner', 'system_admin'])
            ->orderBy('display_name')
            ->get(['name', 'display_name']);

        return view('livewire.admin.staff.staff-assignments-list-page', [
            'rows' => $rows,
            'staffUsers' => $staffUsers,
            'departments' => $departments,
            'roleOptions' => $roleOptions,
        ])->layout('components.layouts.admin', [
            'title' => __('Staff Assignments'),
            'headerTitle' => __('Staff Assignments'),
            'headerDescription' => __('Assign physical offices, operational workspaces, and titles to specific staff members.'),
        ]);
    }
}
