<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Staff;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\Program;
use App\Models\Teacher;
use App\Models\TeacherRole;
use App\Models\TeacherPortalRole;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class TeacherRoleListPage extends Component
{
    use DispatchesCollegeToasts;
    use WithPagination;

    // Tabs
    public string $activeTab = 'assignments';

    // Assignments Modals & States
    public bool $showAssignModal = false;

    public bool $showEditModal = false;

    public bool $showRevokeModal = false;

    public ?int $editingId = null;

    public ?int $revokingId = null;

    // Assignments Fields
    public ?int $teacher_id = null;

    public string $role = '';

    public ?int $program_id = null;

    public string $description = '';

    public string $assigned_date = '';

    public string $status = 'active';

    // Role Definition Form Fields
    public string $defName = '';

    public string $defDisplayName = '';

    /** @var array<string> */
    public array $defPermissions = [];

    public string $defDescription = '';

    public ?int $editingDefId = null;

    public bool $isEditingDef = false;

    public function mount(): void
    {
        $this->assigned_date = now()->format('Y-m-d');
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    // --- ASSIGNMENT WORKFLOWS ---
    public function openAssignModal(): void
    {
        $this->resetRoleForm();
        $this->assigned_date = now()->format('Y-m-d');
        $this->showAssignModal = true;
    }

    public function closeAssignModal(): void
    {
        $this->showAssignModal = false;
        $this->resetRoleForm();
    }

    public function openEditModal(int $id): void
    {
        $row = TeacherRole::query()->findOrFail($id);
        $this->editingId = $row->id;
        $this->teacher_id = $row->teacher_id;
        $this->role = $row->role;
        $this->program_id = $row->program_id;
        $this->description = (string) ($row->description ?? '');
        $this->assigned_date = $row->assigned_date?->format('Y-m-d') ?? '';
        $this->status = (string) $row->status;
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingId = null;
        $this->resetRoleForm();
    }

    public function openRevokeModal(int $id): void
    {
        $this->revokingId = $id;
        $this->showRevokeModal = true;
    }

    public function closeRevokeModal(): void
    {
        $this->showRevokeModal = false;
        $this->revokingId = null;
    }

    public function saveAssign(): void
    {
        $validated = $this->validate([
            'teacher_id' => ['required', 'exists:teachers,id'],
            'role' => ['required', 'string', 'max:128'],
            'program_id' => ['nullable', 'exists:programs,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'assigned_date' => ['nullable', 'date'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        TeacherRole::query()->create([
            'teacher_id' => (int) $validated['teacher_id'],
            'role' => $validated['role'],
            'program_id' => $validated['program_id'] ?: null,
            'description' => $validated['description'] !== '' ? $validated['description'] : null,
            'assigned_date' => $validated['assigned_date'] !== '' ? $validated['assigned_date'] : null,
            'assigned_by' => auth()->id(),
            'status' => $validated['status'],
        ]);

        $this->closeAssignModal();
        $this->resetPage();
        $this->collegeToast(__('Role assigned.'));
    }

    public function saveEdit(): void
    {
        if ($this->editingId === null) {
            return;
        }

        $validated = $this->validate([
            'teacher_id' => ['required', 'exists:teachers,id'],
            'role' => ['required', 'string', 'max:128'],
            'program_id' => ['nullable', 'exists:programs,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'assigned_date' => ['nullable', 'date'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $row = TeacherRole::query()->findOrFail($this->editingId);
        $row->forceFill([
            'teacher_id' => (int) $validated['teacher_id'],
            'role' => $validated['role'],
            'program_id' => $validated['program_id'] ?: null,
            'description' => $validated['description'] !== '' ? $validated['description'] : null,
            'assigned_date' => $validated['assigned_date'] !== '' ? $validated['assigned_date'] : null,
            'status' => $validated['status'],
        ])->save();

        $this->closeEditModal();
        $this->collegeToast(__('Role updated.'));
    }

    public function confirmRevoke(): void
    {
        if ($this->revokingId === null) {
            return;
        }

        $row = TeacherRole::query()->findOrFail($this->revokingId);
        $row->forceFill(['status' => 'inactive'])->save();

        $this->closeRevokeModal();
        $this->collegeToast(__('Role revoked.'));
    }

    private function resetRoleForm(): void
    {
        $this->teacher_id = null;
        $this->role = '';
        $this->program_id = null;
        $this->description = '';
        $this->assigned_date = now()->format('Y-m-d');
        $this->status = 'active';
        $this->resetValidation();
    }

    // --- ROLE DEFINITIONS WORKFLOWS ---
    public function saveDefinition(): void
    {
        $rules = [
            'defDisplayName' => ['required', 'string', 'max:255'],
            'defPermissions' => ['required', 'array', 'min:1'],
            'defPermissions.*' => ['string', 'in:courses,students,assessments,communication'],
            'defDescription' => ['nullable', 'string', 'max:1000'],
        ];

        $validated = $this->validate($rules);

        if ($this->isEditingDef && $this->editingDefId !== null) {
            $roleDef = TeacherPortalRole::query()->findOrFail($this->editingDefId);
            $roleDef->forceFill([
                'display_name' => $validated['defDisplayName'],
                'permissions' => $validated['defPermissions'],
                'description' => $validated['defDescription'] !== '' ? $validated['defDescription'] : null,
            ])->save();

            $this->collegeToast(__('Role definition updated.'));
        } else {
            $slugName = strtolower(\Illuminate\Support\Str::slug($validated['defDisplayName'], '_'));

            if (\App\Models\TeacherPortalRole::where('name', $slugName)->exists()) {
                $this->addError('defDisplayName', __('A role with a similar name already exists.'));

                return;
            }

            TeacherPortalRole::query()->create([
                'name' => $slugName,
                'display_name' => $validated['defDisplayName'],
                'permissions' => $validated['defPermissions'],
                'description' => $validated['defDescription'] !== '' ? $validated['defDescription'] : null,
            ]);

            $this->collegeToast(__('Role definition created.'));
        }

        $this->resetDefinitionForm();
    }

    public function startEditDefinition(int $id): void
    {
        $roleDef = TeacherPortalRole::query()->findOrFail($id);
        $this->editingDefId = $roleDef->id;
        $this->defName = $roleDef->name;
        $this->defDisplayName = $roleDef->display_name;
        $this->defPermissions = (array) $roleDef->permissions;
        $this->defDescription = (string) ($roleDef->description ?? '');
        $this->isEditingDef = true;
    }

    public function deleteDefinition(int $id): void
    {
        $roleDef = TeacherPortalRole::query()->findOrFail($id);
        
        // Prevent deleting core lecturer role
        if ($roleDef->name === 'lecturer') {
            $this->collegeToast(__('Cannot delete the core lecturer role.'), 'danger');
            return;
        }

        $roleDef->delete();
        $this->collegeToast(__('Role definition deleted.'));
    }

    public function resetDefinitionForm(): void
    {
        $this->defName = '';
        $this->defDisplayName = '';
        $this->defPermissions = [];
        $this->defDescription = '';
        $this->isEditingDef = false;
        $this->editingDefId = null;
        $this->resetValidation();
    }

    public function render(): View
    {
        $portalRoles = TeacherPortalRole::query()->orderBy('display_name')->get();

        if ($this->activeTab === 'assignments') {
            $rows = TeacherRole::query()
                ->with(['teacher', 'program'])
                ->orderByDesc('assigned_date')
                ->paginate(20);

            $teachers = Teacher::query()->with('user')->orderBy('lastname')->limit(500)->get();
            $programs = Program::query()->orderBy('name')->get();

            return view('livewire.admin.staff.teacher-role-list-page', [
                'rows' => $rows,
                'teachers' => $teachers,
                'programs' => $programs,
                'portalRoles' => $portalRoles,
            ])->layout('components.layouts.admin', [
                'title' => __('Teacher Roles'),
                'headerTitle' => __('Teacher Roles'),
                'headerDescription' => __('Assign functional roles to lecturers and customize workspace permission profiles.'),
            ]);
        }

        // Definitions tab
        return view('livewire.admin.staff.teacher-role-list-page', [
            'portalRoles' => $portalRoles,
        ])->layout('components.layouts.admin', [
            'title' => __('Teacher Roles'),
            'headerTitle' => __('Teacher Roles'),
            'headerDescription' => __('Assign functional roles to lecturers and customize workspace permission profiles.'),
        ]);
    }
}
