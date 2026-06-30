<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Staff;

use App\Actions\Staff\CreateNonTeachingStaffUser;
use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\Department;
use App\Models\NonTeachingStaff;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;

class NonTeachingListPage extends Component
{
    use DispatchesCollegeToasts;
    use WithPagination;

    public string $search = '';

    public string $filterDepartment = 'all';

    public string $filterPosition = 'all';

    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeactivateModal = false;

    public ?int $editingNonTeachingId = null;

    public ?int $deactivatingNonTeachingId = null;

    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $password = '';

    public string $position = '';

    public ?int $department_id = null;

    public string $phone_number = '';

    public string $status = 'active';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterDepartment' => ['except' => 'all'],
        'filterPosition' => ['except' => 'all'],
    ];

    public function mount(): void
    {
        if (request()->boolean('create')) {
            $this->openCreateModal();
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterDepartment(): void
    {
        $this->resetPage();
    }

    public function updatedFilterPosition(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function openEditModal(int $nonTeachingId): void
    {
        $row = NonTeachingStaff::query()->with('user')->findOrFail($nonTeachingId);
        $user = $row->user;
        if ($user === null) {
            return;
        }

        $this->editingNonTeachingId = $row->id;
        $this->name = (string) ($user->name ?? '');
        $this->username = (string) ($user->username ?? '');
        $this->email = $user->email;
        $this->password = '';
        $this->position = $row->position;
        $this->department_id = $row->department_id;
        $this->phone_number = $row->phone_number;
        $this->status = (string) $row->status;
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingNonTeachingId = null;
        $this->resetForm();
    }

    public function openDeactivateModal(int $nonTeachingId): void
    {
        $this->deactivatingNonTeachingId = $nonTeachingId;
        $this->showDeactivateModal = true;
    }

    public function closeDeactivateModal(): void
    {
        $this->showDeactivateModal = false;
        $this->deactivatingNonTeachingId = null;
    }

    public function saveCreate(CreateNonTeachingStaffUser $createNonTeachingStaffUser): void
    {
        $pw = $this->password;
        if (trim($pw) === '') {
            $pw = 'Password@1';
        }

        $rules = $this->createRules();
        $rules['password'] = ['nullable', 'string', 'min:8'];
        $validated = $this->validate($rules);

        $createNonTeachingStaffUser->execute([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => $pw,
            'position' => $validated['position'],
            'department_id' => $validated['department_id'] ? (int) $validated['department_id'] : null,
            'phone_number' => $validated['phone_number'],
            'status' => 'active',
            'active' => true,
        ]);

        $this->closeCreateModal();
        $this->resetPage();
        $this->collegeToast(__('Non-teaching staff member created.'));
    }

    public function saveEdit(): void
    {
        if ($this->editingNonTeachingId === null) {
            return;
        }

        $row = NonTeachingStaff::query()->with('user')->findOrFail($this->editingNonTeachingId);
        $user = $row->user;
        if ($user === null) {
            return;
        }

        $rules = $this->editRules($user->id);
        if ($this->password !== '') {
            $rules['password'] = ['required', 'string', 'min:8'];
        } else {
            $rules['password'] = ['nullable'];
        }
        $validated = $this->validate($rules);

        $user->name = $validated['name'];
        $user->username = $validated['username'];
        $user->email = $validated['email'];
        if ($this->password !== '') {
            $user->password = Hash::make($this->password);
        }
        $user->save();

        $row->forceFill([
            'position' => $validated['position'],
            'department_id' => $validated['department_id'] ? (int) $validated['department_id'] : null,
            'phone_number' => $validated['phone_number'],
            'status' => $validated['status'],
        ])->save();

        $this->closeEditModal();
        $this->collegeToast(__('Staff record updated.'));
    }

    public function confirmDeactivate(): void
    {
        if ($this->deactivatingNonTeachingId === null) {
            return;
        }

        $row = NonTeachingStaff::query()->with('user')->findOrFail($this->deactivatingNonTeachingId);
        $row->forceFill(['status' => 'inactive'])->save();
        if ($row->user !== null) {
            $row->user->forceFill(['active' => false])->save();
        }

        $this->closeDeactivateModal();
        $this->collegeToast(__('Staff member deactivated.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function createRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:'.User::class.',username'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class.',email'],
            'position' => ['required', 'string', 'max:191'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'phone_number' => ['required', 'string', 'max:32'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function editRules(int $userId): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:'.User::class.',username,'.$userId],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class.',email,'.$userId],
            'position' => ['required', 'string', 'max:191'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'phone_number' => ['required', 'string', 'max:32'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->username = '';
        $this->email = '';
        $this->password = '';
        $this->position = '';
        $this->department_id = null;
        $this->phone_number = '';
        $this->status = 'active';
        $this->editingNonTeachingId = null;
        $this->resetValidation();
    }

    public function render(): View
    {
        $query = NonTeachingStaff::query()->with(['user', 'department']);

        if ($this->search !== '') {
            $q = '%' . $this->search . '%';
            $query->where(function ($sub) use ($q) {
                $sub->where('position', 'like', $q)
                    ->orWhere('phone_number', 'like', $q)
                    ->orWhereHas('user', function ($u) use ($q) {
                        $u->where('name', 'like', $q)
                          ->orWhere('email', 'like', $q)
                          ->orWhere('username', 'like', $q);
                    });
            });
        }

        if ($this->filterDepartment !== 'all') {
            $query->where('department_id', (int) $this->filterDepartment);
        }

        if ($this->filterPosition !== 'all') {
            $query->where('position', $this->filterPosition);
        }

        $rows = $query->paginate(15);
        $departments = Department::query()->orderBy('name')->get();

        // Get distinct list of existing positions for filter list
        $positions = NonTeachingStaff::query()
            ->whereNotNull('position')
            ->where('position', '!=', '')
            ->distinct()
            ->orderBy('position')
            ->pluck('position')
            ->all();

        return view('livewire.admin.staff.non-teaching-list-page', [
            'rows' => $rows,
            'departments' => $departments,
            'positions' => $positions,
        ])->layout('components.layouts.admin', [
            'title' => __('Non-teaching staff'),
            'headerTitle' => __('Non-Teaching Staff'),
            'headerDescription' => __('Manage operational support personnel, facility assignments, and service roles.'),
        ]);
    }
}
