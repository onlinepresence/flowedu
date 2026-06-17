<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Setup;

use App\Models\Department;
use App\Models\Program;
use App\Support\CollegeFlash;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class SetupProgramPage extends Component
{
    use WithPagination;

    public string $name = '';

    public string $department_id = '';

    public string $certificate = '';

    public string $cost = '';

    public int $program_length = 4;

    public ?int $editingProgramId = null;

    public function saveProgram(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
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
        CollegeFlash::forNextRequestToo('status', __('Program has been added.'));
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
    }

    public function cancelEditProgram(): void
    {
        $this->editingProgramId = null;
        $this->reset(['name', 'department_id', 'certificate', 'cost']);
        $this->department_id = '';
        $this->program_length = 4;
        $this->resetValidation();
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
        CollegeFlash::forNextRequestToo('status', __('Program has been updated.'));
    }

    public function deleteProgram(int $programId): void
    {
        try {
            Program::query()->findOrFail($programId)->delete();
            if ($this->editingProgramId === $programId) {
                $this->cancelEditProgram();
            }
            $this->resetPage();
            CollegeFlash::forNextRequestToo('status', __('Program has been deleted.'));
        } catch (QueryException) {
            CollegeFlash::forNextRequestToo('backup_error', __('Cannot delete program because related records still exist.'));
        }
    }

    public function render(): View
    {
        return view('livewire.admin.setup.setup-program-page', [
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'programs' => Program::query()
                ->with('department')
                ->orderBy('name')
                ->paginate(15),
        ])->layout('components.layouts.admin', ['title' => __('Setup programs')]);
    }
}
