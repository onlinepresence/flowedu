<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Setup;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\User;
use App\Support\CollegeFlash;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class SetupDepartmentPage extends Component
{
    use WithPagination;

    public string $name = '';

    public string $faculty_id = '';

    public string $hod = '';

    public ?int $editingDepartmentId = null;

    public function saveDepartment(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
            'faculty_id' => ['nullable', 'integer', 'exists:faculties,id'],
            'hod' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $facultyId = $this->faculty_id === '' ? null : (int) $this->faculty_id;
        $hodId = $this->hod === '' ? null : (int) $this->hod;

        Department::query()->create([
            'name' => trim($this->name),
            'faculty_id' => $facultyId,
            'hod' => $hodId,
        ]);

        $this->reset(['name', 'faculty_id', 'hod']);
        $this->resetPage();
        CollegeFlash::forNextRequestToo('status', __('Department has been added.'));
    }

    public function editDepartment(int $departmentId): void
    {
        $department = Department::query()->findOrFail($departmentId);
        $this->editingDepartmentId = $department->id;
        $this->name = (string) $department->name;
        $this->faculty_id = $department->faculty_id !== null ? (string) $department->faculty_id : '';
        $this->hod = $department->hod !== null ? (string) $department->hod : '';
        $this->resetValidation();
    }

    public function cancelEditDepartment(): void
    {
        $this->editingDepartmentId = null;
        $this->reset(['name', 'faculty_id', 'hod']);
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
            'hod' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $department = Department::query()->findOrFail($this->editingDepartmentId);
        $department->update([
            'name' => trim($this->name),
            'faculty_id' => $this->faculty_id === '' ? null : (int) $this->faculty_id,
            'hod' => $this->hod === '' ? null : (int) $this->hod,
        ]);

        $this->cancelEditDepartment();
        CollegeFlash::forNextRequestToo('status', __('Department has been updated.'));
    }

    public function deleteDepartment(int $departmentId): void
    {
        try {
            Department::query()->findOrFail($departmentId)->delete();
            if ($this->editingDepartmentId === $departmentId) {
                $this->cancelEditDepartment();
            }
            $this->resetPage();
            CollegeFlash::forNextRequestToo('status', __('Department has been deleted.'));
        } catch (QueryException) {
            CollegeFlash::forNextRequestToo('backup_error', __('Cannot delete department because related records still exist.'));
        }
    }

    public function render(): View
    {
        $faculties = Faculty::query()->orderBy('name')->get(['id', 'name']);
        $hodUsers = User::query()
            ->where('type', 'admin')
            ->whereNotNull('username')
            ->where('username', '!=', '')
            ->orderBy('username')
            ->get(['id', 'username', 'email']);

        return view('livewire.admin.setup.setup-department-page', [
            'faculties' => $faculties,
            'hodUsers' => $hodUsers,
            'departments' => Department::query()
                ->with(['faculty', 'headOfDepartment'])
                ->orderBy('name')
                ->paginate(15),
        ])->layout('components.layouts.admin', ['title' => __('Setup departments')]);
    }
}
