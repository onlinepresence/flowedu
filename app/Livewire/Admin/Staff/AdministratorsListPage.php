<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Staff;

use App\Actions\Staff\CreateAdminUser;
use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\Admin;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class AdministratorsListPage extends Component
{
    use DispatchesCollegeToasts;
    use WithPagination;

    public string $search = '';

    public string $filterRole = 'all';

    public string $filterDepartment = 'all';

    public string $filterFaculty = 'all';

    public string $filterStatus = 'all';

    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeactivateModal = false;

    public ?int $editingAdminId = null;

    public ?int $deactivatingAdminId = null;

    // Form fields
    public string $lastname = '';

    public string $othernames = '';

    public string $email = '';

    public string $username = ''; // Staff Number

    public string $password = '';

    public string $phone_number = '';

    public string $gender = 'male';

    public string $position_title = '';

    public ?int $department_id = null;

    public ?int $faculty_id = null;

    public ?string $date_of_appointment = null;

    public ?string $ghana_card = null;

    public ?int $type = null;

    public string $status = 'active';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterRole' => ['except' => 'all'],
        'filterDepartment' => ['except' => 'all'],
        'filterFaculty' => ['except' => 'all'],
        'filterStatus' => ['except' => 'all'],
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

    public function updatedFilterRole(): void
    {
        $this->resetPage();
    }

    public function updatedFilterDepartment(): void
    {
        $this->resetPage();
    }

    public function updatedFilterFaculty(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
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

    public function openEditModal(int $adminId): void
    {
        $row = Admin::query()->with('user')->findOrFail($adminId);
        $user = $row->user;
        if ($user === null) {
            return;
        }

        // Prevent editing the owner account unless the current user is the owner
        if ($user->isAdminOwner() && auth()->id() !== $user->id) {
            $this->collegeToast(__('You cannot edit the primary Owner account.'), 'danger');
            return;
        }

        $this->editingAdminId = $row->id;
        $this->lastname = $row->lastname;
        $this->othernames = $row->othernames;
        $this->email = $user->email;
        $this->username = $user->username;
        $this->password = '';
        $this->phone_number = (string) $row->phone_number;
        $this->gender = $row->gender ?? 'male';
        $this->position_title = (string) $row->position_title;
        $this->department_id = $row->department_id;
        $this->faculty_id = $row->faculty_id;
        $this->date_of_appointment = $row->date_of_appointment ? $row->date_of_appointment->format('Y-m-d') : null;
        $this->ghana_card = (string) $row->ghana_card;
        $this->type = $row->type;
        $this->status = $row->status;
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingAdminId = null;
        $this->resetForm();
    }

    public function openDeactivateModal(int $adminId): void
    {
        $row = Admin::query()->findOrFail($adminId);
        if ($row->user_id === auth()->id()) {
            $this->collegeToast(__('You cannot deactivate your own account.'), 'danger');
            return;
        }
        if ($row->user?->isAdminOwner()) {
            $this->collegeToast(__('You cannot deactivate the primary Owner account.'), 'danger');
            return;
        }
        $this->deactivatingAdminId = $adminId;
        $this->showDeactivateModal = true;
    }

    public function closeDeactivateModal(): void
    {
        $this->showDeactivateModal = false;
        $this->deactivatingAdminId = null;
    }

    public function saveCreate(CreateAdminUser $createAdminUser): void
    {
        // Default password if not provided
        $pw = $this->password;
        if (trim($pw) === '') {
            $pw = 'Password@1';
        }

        $rules = $this->validationRules();
        $rules['password'] = ['nullable', 'string', 'min:8'];
        $validated = $this->validate($rules);

        $createAdminUser->execute([
            'name' => trim($validated['othernames'] . ' ' . $validated['lastname']),
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => $pw,
            'lastname' => $validated['lastname'],
            'othernames' => $validated['othernames'],
            'phone_number' => $validated['phone_number'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'position_title' => $validated['position_title'] ?? null,
            'department_id' => $validated['department_id'] ? (int) $validated['department_id'] : null,
            'faculty_id' => $validated['faculty_id'] ? (int) $validated['faculty_id'] : null,
            'date_of_appointment' => $validated['date_of_appointment'] ?? null,
            'ghana_card' => $validated['ghana_card'] ?? null,
            'type' => $validated['type'] ? (int) $validated['type'] : null,
            'active' => true,
        ]);

        $this->closeCreateModal();
        $this->resetPage();
        $this->collegeToast(__('Administrator account created.'));
    }

    public function saveEdit(): void
    {
        if ($this->editingAdminId === null) {
            return;
        }

        $row = Admin::query()->with('user')->findOrFail($this->editingAdminId);
        $user = $row->user;
        if ($user === null) {
            return;
        }

        $rules = $this->validationRules($user->id);
        if ($this->password !== '') {
            $rules['password'] = ['required', 'string', 'min:8'];
        } else {
            $rules['password'] = ['nullable'];
        }
        $validated = $this->validate($rules);

        $user->name = trim($validated['othernames'] . ' ' . $validated['lastname']);
        $user->username = $validated['username'];
        $user->email = $validated['email'];
        if ($this->password !== '') {
            $user->password = Hash::make($this->password);
        }
        $user->save();

        $row->forceFill([
            'lastname' => $validated['lastname'],
            'othernames' => $validated['othernames'],
            'phone_number' => $validated['phone_number'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'position_title' => $validated['position_title'] ?? null,
            'department_id' => $validated['department_id'] ? (int) $validated['department_id'] : null,
            'faculty_id' => $validated['faculty_id'] ? (int) $validated['faculty_id'] : null,
            'date_of_appointment' => $validated['date_of_appointment'] ?? null,
            'ghana_card' => $validated['ghana_card'] ?? null,
            'type' => $validated['type'] ? (int) $validated['type'] : null,
            'status' => $validated['status'],
        ])->save();

        $this->closeEditModal();
        $this->collegeToast(__('Administrator account updated.'));
    }

    public function confirmDeactivate(): void
    {
        if ($this->deactivatingAdminId === null) {
            return;
        }

        $row = Admin::query()->with('user')->findOrFail($this->deactivatingAdminId);
        if ($row->user_id === auth()->id() || $row->user?->isAdminOwner()) {
            $this->closeDeactivateModal();
            return;
        }

        $row->forceFill(['status' => 'inactive'])->save();
        if ($row->user !== null) {
            $row->user->forceFill(['active' => false])->save();
        }

        $this->closeDeactivateModal();
        $this->collegeToast(__('Administrator account deactivated.'));
    }

    private function validationRules(?int $userId = null): array
    {
        $usernameUnique = 'unique:users,username';
        $emailUnique = 'unique:users,email';

        if ($userId !== null) {
            $usernameUnique .= ',' . $userId;
            $emailUnique .= ',' . $userId;
        }

        return [
            'lastname' => ['required', 'string', 'max:255'],
            'othernames' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', $emailUnique],
            'username' => ['required', 'string', 'max:255', $usernameUnique],
            'phone_number' => ['nullable', 'string', 'max:32'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'position_title' => ['nullable', 'string', 'max:191'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'faculty_id' => ['nullable', 'exists:faculties,id'],
            'date_of_appointment' => ['nullable', 'date'],
            'ghana_card' => ['nullable', 'string', 'max:64'],
            'type' => [
                'required',
                'exists:user_roles,id',
                Rule::notIn(UserRole::query()->where('name', 'owner')->pluck('id')->all()),
            ],
            'status' => ['required', 'in:active,inactive'],
        ];
    }

    private function resetForm(): void
    {
        $this->lastname = '';
        $this->othernames = '';
        $this->email = '';
        $this->username = '';
        $this->password = '';
        $this->phone_number = '';
        $this->gender = 'male';
        $this->position_title = '';
        $this->department_id = null;
        $this->faculty_id = null;
        $this->date_of_appointment = null;
        $this->ghana_card = '';
        $this->type = UserRole::query()->where('name', '!=', 'owner')->orderBy('display_name')->value('id');
        $this->status = 'active';
        $this->editingAdminId = null;
        $this->resetValidation();
    }

    public function render(): View
    {
        $query = Admin::query()->with(['user', 'department', 'faculty', 'role']);

        if ($this->search !== '') {
            $q = '%' . $this->search . '%';
            $query->where(function ($sub) use ($q) {
                $sub->where('lastname', 'like', $q)
                    ->orWhere('othernames', 'like', $q)
                    ->orWhere('phone_number', 'like', $q)
                    ->orWhere('position_title', 'like', $q)
                    ->orWhere('ghana_card', 'like', $q)
                    ->orWhereHas('user', function ($u) use ($q) {
                        $u->where('name', 'like', $q)
                          ->orWhere('email', 'like', $q)
                          ->orWhere('username', 'like', $q);
                    });
            });
        }

        if ($this->filterRole !== 'all') {
            $query->where('type', (int) $this->filterRole);
        }

        if ($this->filterDepartment !== 'all') {
            $query->where('department_id', (int) $this->filterDepartment);
        }

        if ($this->filterFaculty !== 'all') {
            $query->where('faculty_id', (int) $this->filterFaculty);
        }

        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        $admins = $query->paginate(15);

        // Exclude Owner from selection roles
        $roles = UserRole::query()
            ->where('name', '!=', 'owner')
            ->orderBy('display_name')
            ->get();

        $departments = Department::query()->orderBy('name')->get();
        $faculties = Faculty::query()->orderBy('name')->get();

        return view('livewire.admin.staff.administrators-list-page', [
            'admins' => $admins,
            'roles' => $roles,
            'departments' => $departments,
            'faculties' => $faculties,
        ])->layout('components.layouts.admin', [
            'title' => __('Administrators'),
            'headerTitle' => __('Administrators'),
            'headerDescription' => __('Manage system administrative staff and assign functional security roles.'),
        ]);
    }
}
