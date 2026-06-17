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

    public function saveDepartment(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
            'faculty_id' => ['nullable', 'integer', 'exists:faculties,id'],
        ]);

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
        $department->update([
            'name' => trim($this->name),
            'faculty_id' => $this->faculty_id === '' ? null : (int) $this->faculty_id,
        ]);

        $this->cancelEditDepartment();
        $this->collegeToast(__('Department has been updated.'));
    }

    public function confirmDeleteDepartment(int $departmentId): void
    {
        $this->deletingDepartmentId = $departmentId;
        $this->dispatch('open-modal', 'confirm-delete-department-modal');
    }

    public function deleteDepartment(): void
    {
        if ($this->deletingDepartmentId === null) {
            return;
        }
        $departmentId = $this->deletingDepartmentId;
        try {
            Department::query()->findOrFail($departmentId)->delete();
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
        $departments = Department::query()
            ->with(['faculty', 'headOfDepartment'])
            ->withCount('programs')
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.admin.academic.department-index', [
            'departments' => $departments,
            'faculties' => Faculty::query()->orderBy('name')->get(['id', 'name']),
        ])->layout('components.layouts.admin', [
            'title' => __('Departments'),
            'headerTitle' => __('Departments'),
            'headerDescription' => __('Manage academic departments and link them to faculties.'),
        ]);
    }
}
