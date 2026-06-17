<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Academic;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\Department;
use App\Models\Program;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ProgramIndex extends Component
{
    use DispatchesCollegeToasts;
    use WithPagination;

    public string $name = '';

    public string $department_id = '';

    public string $certificate = '';

    public string $cost = '';

    public int $program_length = 4;

    public ?int $editingProgramId = null;

    public ?int $deletingProgramId = null;

    #[On('open-add-program')]
    public function openAddModal(): void
    {
        $this->cancelEditProgram(false);
        $this->dispatch('open-modal', 'program-modal');
    }

    public function saveProgram(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255', 'unique:programs,name'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'certificate' => ['required', 'string', 'max:255'],
            'cost' => ['required', 'numeric', 'min:0'],
            'program_length' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        Program::query()->create([
            'name' => trim($this->name),
            'department_id' => (int) $this->department_id,
            'certificate' => trim($this->certificate),
            'cost' => (float) $this->cost,
            'program_length' => $this->program_length,
        ]);

        $this->reset(['name', 'department_id', 'certificate', 'cost']);
        $this->department_id = '';
        $this->program_length = 4;
        $this->resetPage();
        $this->dispatch('close-modal', 'program-modal');
        $this->collegeToast(__('Program has been added.'));
    }

    public function editProgram(int $programId): void
    {
        $program = Program::query()->findOrFail($programId);
        $this->editingProgramId = $program->id;
        $this->name = (string) $program->name;
        $this->department_id = (string) $program->department_id;
        $this->certificate = (string) $program->certificate;
        $this->cost = (string) $program->cost;
        $this->program_length = (int) $program->program_length;
        $this->resetValidation();
        $this->dispatch('open-modal', 'program-modal');
    }

    public function cancelEditProgram(bool $shouldClose = true): void
    {
        $this->editingProgramId = null;
        $this->reset(['name', 'department_id', 'certificate', 'cost']);
        $this->department_id = '';
        $this->program_length = 4;
        $this->resetValidation();
        if ($shouldClose) {
            $this->dispatch('close-modal', 'program-modal');
        }
    }

    public function updateProgram(): void
    {
        if ($this->editingProgramId === null) {
            return;
        }

        $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('programs', 'name')->ignore($this->editingProgramId),
            ],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'certificate' => ['required', 'string', 'max:255'],
            'cost' => ['required', 'numeric', 'min:0'],
            'program_length' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $program = Program::query()->findOrFail($this->editingProgramId);
        $program->update([
            'name' => trim($this->name),
            'department_id' => (int) $this->department_id,
            'certificate' => trim($this->certificate),
            'cost' => (float) $this->cost,
            'program_length' => $this->program_length,
        ]);

        $this->cancelEditProgram();
        $this->collegeToast(__('Program has been updated.'));
    }

    public function confirmDeleteProgram(int $programId): void
    {
        $this->deletingProgramId = $programId;
        $this->dispatch('open-modal', 'confirm-delete-program-modal');
    }

    public function deleteProgram(): void
    {
        if ($this->deletingProgramId === null) {
            return;
        }
        $programId = $this->deletingProgramId;
        try {
            Program::query()->findOrFail($programId)->delete();
            if ($this->editingProgramId === $programId) {
                $this->cancelEditProgram();
            }
            $this->deletingProgramId = null;
            $this->resetPage();
            $this->collegeToast(__('Program has been deleted.'));
        } catch (QueryException) {
            $this->deletingProgramId = null;
            $this->collegeToast(__('Cannot delete program because related records still exist.'), 'error');
        }
    }

    public function render(): View
    {
        $programs = Program::query()
            ->with('department')
            ->withCount('courses')
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.admin.academic.program-index', [
            'programs' => $programs,
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
        ])->layout('components.layouts.admin', [
            'title' => __('Programs'),
            'headerTitle' => __('Programs'),
            'headerDescription' => __('Manage academic programs, departments, costs, and certificate types.'),
        ]);
    }
}
