<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Models\AdminType;
use App\Models\UserRole;
use App\Support\CollegeFlash;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SettingsUserRolesPage extends Component
{
    private const PROTECTED_ROLE_NAMES = ['owner', 'system_admin'];

    public function mount(): void
    {
        AdminType::ensureDefaults();
        UserRole::ensureSystemRoles();
    }

    public bool $showRoleModal = false;

    public bool $showDeleteModal = false;

    public bool $isEditing = false;

    public ?int $editingId = null;

    public string $roleFilter = 'all';

    public string $display_name = '';

    public string $role_name = '';

    public string $name = '';

    /** @var list<string> */
    public array $selectedPermissions = [];

    public ?int $deleteRoleId = null;

    public function openCreate(): void
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->editingId = null;
        $this->showRoleModal = true;
    }

    public function openEdit(int $roleId): void
    {
        $role = UserRole::query()->findOrFail($roleId);
        $this->isEditing = true;
        $this->editingId = $role->id;
        $this->display_name = $role->display_name;
        $this->role_name = $role->role_name;
        $this->name = $role->name;
        $perms = $role->permissions;
        $this->selectedPermissions = is_array($perms)
            ? array_values(array_filter($perms, fn ($p) => is_string($p)))
            : [];
        $this->showRoleModal = true;
    }

    public function closeRoleModal(): void
    {
        $this->showRoleModal = false;
        $this->resetForm();
    }

    public function saveRole(): void
    {
        $permissionKeys = array_keys(config('college.admin_permissions', []));
        $adminTypeNames = AdminType::query()->orderBy('name')->pluck('name')->all();

        if ($this->isEditing && $this->editingId !== null) {
            $role = UserRole::query()->findOrFail($this->editingId);
            $this->validate([
                'display_name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('user_roles', 'display_name')->ignore($role->id),
                ],
                'selectedPermissions' => ['nullable', 'array'],
                'selectedPermissions.*' => ['string', Rule::in($permissionKeys)],
            ]);
            $role->update([
                'display_name' => $this->display_name,
                'permissions' => $this->selectedPermissions,
            ]);

            $this->closeRoleModal();
            CollegeFlash::forNextRequestToo('status', __('Role updated.'));
            $this->redirectAfterMutation();

            return;
        }

        $this->validate([
            'display_name' => ['required', 'string', 'max:255', 'unique:user_roles,display_name'],
            'role_name' => ['required', 'string', Rule::in($adminTypeNames)],
            'name' => ['nullable', 'string', 'max:255'],
            'selectedPermissions' => ['nullable', 'array'],
            'selectedPermissions.*' => ['string', Rule::in($permissionKeys)],
        ]);

        $slug = $this->normalizedSystemName();

        UserRole::query()->create([
            'role_name' => $this->role_name,
            'name' => $slug,
            'display_name' => $this->display_name,
            'permissions' => $this->selectedPermissions,
        ]);

        $this->closeRoleModal();
        CollegeFlash::forNextRequestToo('status', __('Role created.'));
        $this->redirectAfterMutation();
    }

    public function confirmDelete(int $roleId): void
    {
        $role = UserRole::query()->findOrFail($roleId);
        if ($this->isProtectedName($role->name)) {
            $this->addError('delete', __('This system role cannot be deleted.'));

            return;
        }
        if ($role->admins()->exists()) {
            $this->addError('delete', __('Cannot delete a role that is assigned to one or more admins.'));

            return;
        }
        $this->deleteRoleId = $roleId;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deleteRoleId = null;
    }

    public function deleteRole(): void
    {
        if ($this->deleteRoleId === null) {
            return;
        }
        $role = UserRole::query()->findOrFail($this->deleteRoleId);
        if ($this->isProtectedName($role->name) || $role->admins()->exists()) {
            $this->closeDeleteModal();

            return;
        }
        $role->delete();
        $this->closeDeleteModal();
        CollegeFlash::forNextRequestToo('status', __('Role deleted.'));
        $this->redirectAfterMutation();
    }

    public function syncRoles(): void
    {
        abort_unless(auth()->user()?->isAdminOwner(), 403);

        \Illuminate\Support\Facades\Artisan::call('college:sync-roles');

        CollegeFlash::forNextRequestToo('status', __('Roles & permissions synchronized.'));
        $this->redirectAfterMutation();
    }

    private function redirectAfterMutation(): void
    {
        $this->redirect(route('admin.settings.roles'), navigate: true);
    }

    public function render(): View
    {
        $query = UserRole::query()->orderBy('display_name')->orderBy('name');
        if ($this->roleFilter !== 'all') {
            $query->where('role_name', $this->roleFilter);
        }

        $roles = $query->get();
        $adminTypes = AdminType::query()->orderBy('display_name')->get();
        $permissionLabels = config('college.admin_permissions', []);

        $roleTypeLabels = AdminType::query()->pluck('display_name', 'name')->all();

        return view('livewire.admin.settings.settings-user-roles-page', [
            'roles' => $roles,
            'adminTypes' => $adminTypes,
            'permissionLabels' => $permissionLabels,
            'roleTypeLabels' => $roleTypeLabels,
        ])->layout('components.layouts.admin', [
            'title' => __('Roles'),
            'headerTitle' => __('Roles & Permissions'),
            'headerDescription' => __('Configure custom user roles and assign specific administrative action permissions.'),
        ]);
    }

    private function resetForm(): void
    {
        $this->resetValidation();
        $this->display_name = '';
        $this->role_name = AdminType::query()->orderBy('name')->value('name') ?? '';
        $this->name = '';
        $this->selectedPermissions = [];
    }

    private function isProtectedName(string $name): bool
    {
        return in_array($name, self::PROTECTED_ROLE_NAMES, true);
    }

    private function normalizedSystemName(): string
    {
        $base = $this->name !== '' && trim($this->name) !== ''
            ? Str::slug(trim($this->name))
            : Str::slug($this->display_name);
        if ($base === '') {
            $base = 'role';
        }

        $candidate = $base;
        $i = 2;
        while (UserRole::query()->where('name', $candidate)->exists()) {
            $candidate = $base.'-'.$i;
            $i++;
        }

        return $candidate;
    }
}
