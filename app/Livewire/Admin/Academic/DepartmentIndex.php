<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Academic;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\Department;
use App\Models\Faculty;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class DepartmentIndex extends Component
{
    use DispatchesCollegeToasts;
    use WithPagination;

    public string $name = '';

    public string $faculty_id = '';

    public ?int $editingDepartmentId = null;

    public ?int $deletingDepartmentId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasAdminPermission('nav_academic_department'), 403);
    }

    public function saveDepartment(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
            'faculty_id' => ['nullable', 'integer', 'exists:faculties,id'],
        ]);

        $admin = auth()->user()?->admin;
        if ($admin) {
            abort_unless($admin->canAccessFaculty($this->faculty_id ? (int) $this->faculty_id : null), 403);
        }

        Department::query()->create([
            'name' => trim($this->name),
            'faculty_id' => $this->faculty_id === '' ? null : (int) $this->faculty_id,
        ]);

        $this->reset(['name', 'faculty_id']);
        $this->resetPage();
        $this->collegeToast(__('Department has been added.'));
    }

    public function editDepartment(int $departmentId): void
    {
        $department = Department::query()->findOrFail($departmentId);
        $admin = auth()->user()?->admin;
        if ($admin) {
            abort_unless($admin->canAccessDepartment($department->id), 403);
        }

        $this->editingDepartmentId = $department->id;
        $this->name = (string) $department->name;
        $this->faculty_id = $department->faculty_id !== null ? (string) $department->faculty_id : '';
        $this->resetValidation();
    }

    public function cancelEditDepartment(): void
    {
        $this->editingDepartmentId = null;
        $this->reset(['name', 'faculty_id']);
        $this->resetValidation();
    }

    public function updateDepartment(): void
    {
        if ($this->editingDepartmentId === null) {
            return;
        }

        $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')->ignore($this->editingDepartmentId),
            ],
            'faculty_id' => ['nullable', 'integer', 'exists:faculties,id'],
        ]);

        $department = Department::query()->findOrFail($this->editingDepartmentId);
        $admin = auth()->user()?->admin;
        if ($admin) {
            abort_unless($admin->canAccessDepartment($department->id), 403);
            abort_unless($admin->canAccessFaculty($this->faculty_id ? (int) $this->faculty_id : null), 403);
        }

        $department->update([
            'name' => trim($this->name),
            'faculty_id' => $this->faculty_id === '' ? null : (int) $this->faculty_id,
        ]);

        $this->cancelEditDepartment();
        $this->collegeToast(__('Department has been updated.'));
    }

    public function confirmDeleteDepartment(int $departmentId): void
    {
        $department = Department::query()->findOrFail($departmentId);
        $admin = auth()->user()?->admin;
        if ($admin) {
            abort_unless($admin->canAccessDepartment($department->id), 403);
        }

        $this->deletingDepartmentId = $departmentId;
        $this->dispatch('open-modal', 'confirm-delete-department-modal');
    }

    public function deleteDepartment(): void
    {
        if ($this->deletingDepartmentId === null) {
            return;
        }
        $departmentId = $this->deletingDepartmentId;
        $department = Department::query()->findOrFail($departmentId);
        $admin = auth()->user()?->admin;
        if ($admin) {
            abort_unless($admin->canAccessDepartment($department->id), 403);
        }

        try {
            $department->delete();
            if ($this->editingDepartmentId === $departmentId) {
                $this->cancelEditDepartment();
            }
            $this->deletingDepartmentId = null;
            $this->resetPage();
            $this->collegeToast(__('Department has been deleted.'));
        } catch (QueryException) {
            $this->deletingDepartmentId = null;
            $this->collegeToast(__('Cannot delete department because related records still exist.'), 'error');
        }
    }

    public function render(): View
    {
        $admin = auth()->user()?->admin;

        $departments = Department::query()
            ->with(['faculty', 'headOfDepartment'])
            ->withCount('programs')
            ->when($admin?->department_id, fn ($q) => $q->where('id', $admin->department_id))
            ->when($admin?->faculty_id, fn ($q) => $q->where('faculty_id', $admin->faculty_id))
            ->orderBy('name')
            ->paginate(20);

        $faculties = Faculty::query()
            ->when($admin?->faculty_id, fn ($q) => $q->where('id', $admin->faculty_id))
            ->when($admin?->department_id, fn ($q) => $q->whereHas('departments', fn ($d) => $d->where('id', $admin->department_id)))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.admin.academic.department-index', [
            'departments' => $departments,
            'faculties' => $faculties,
        ])->layout('components.layouts.admin', [
            'title' => __('Departments'),
            'headerTitle' => __('Departments'),
            'headerDescription' => __('Manage academic departments and link them to faculties.'),
        ]);
    }
}
