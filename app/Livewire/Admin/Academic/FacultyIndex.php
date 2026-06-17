<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Academic;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\Faculty;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class FacultyIndex extends Component
{
    use DispatchesCollegeToasts;
    use WithPagination;

    public string $newName = '';

    public ?int $editingFacultyId = null;

    public ?int $deletingFacultyId = null;

    public function saveFaculty(): void
    {
        $this->validate([
            'newName' => ['required', 'string', 'max:255', 'unique:faculties,name'],
        ]);

        Faculty::query()->create([
            'name' => trim($this->newName),
        ]);

        $this->newName = '';
        $this->resetPage();
        $this->collegeToast(__('Faculty has been added.'));
    }

    public function editFaculty(int $facultyId): void
    {
        $faculty = Faculty::query()->findOrFail($facultyId);
        $this->editingFacultyId = $faculty->id;
        $this->newName = (string) $faculty->name;
        $this->resetValidation();
    }

    public function cancelEditFaculty(): void
    {
        $this->editingFacultyId = null;
        $this->newName = '';
        $this->resetValidation();
    }

    public function updateFaculty(): void
    {
        if ($this->editingFacultyId === null) {
            return;
        }

        $this->validate([
            'newName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('faculties', 'name')->ignore($this->editingFacultyId),
            ],
        ]);

        $faculty = Faculty::query()->findOrFail($this->editingFacultyId);
        $faculty->update([
            'name' => trim($this->newName),
        ]);

        $this->cancelEditFaculty();
        $this->collegeToast(__('Faculty has been updated.'));
    }

    public function confirmDeleteFaculty(int $facultyId): void
    {
        $this->deletingFacultyId = $facultyId;
        $this->dispatch('open-modal', 'confirm-delete-faculty-modal');
    }

    public function deleteFaculty(): void
    {
        if ($this->deletingFacultyId === null) {
            return;
        }
        $facultyId = $this->deletingFacultyId;
        try {
            Faculty::query()->findOrFail($facultyId)->delete();
            if ($this->editingFacultyId === $facultyId) {
                $this->cancelEditFaculty();
            }
            $this->deletingFacultyId = null;
            $this->resetPage();
            $this->collegeToast(__('Faculty has been deleted.'));
        } catch (QueryException) {
            $this->deletingFacultyId = null;
            $this->collegeToast(__('Cannot delete faculty because related records still exist.'), 'error');
        }
    }

    public function render(): View
    {
        $title = request()->routeIs('admin.setup.faculties')
            ? __('Setup faculties')
            : __('Faculties');

        return view('livewire.admin.academic.faculty-index', [
            'faculties' => Faculty::query()->with('dean')->orderBy('name')->paginate(15),
        ])->layout('components.layouts.admin', [
            'title' => $title,
            'headerTitle' => $title,
            'headerDescription' => __('Manage academic faculties within the institution.'),
        ]);
    }
}
