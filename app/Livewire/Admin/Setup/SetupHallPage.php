<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Setup;

use App\Models\Hall;
use App\Support\CollegeFlash;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class SetupHallPage extends Component
{
    use WithPagination;

    public string $name = '';

    public string $master = '';

    public string $cost = '';

    public string $period = 'per_year';

    public ?int $editingHallId = null;

    public function saveHall(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255', 'unique:halls,name'],
            'master' => ['nullable', 'string', 'max:255'],
            'cost' => ['required', 'numeric', 'min:0'],
            'period' => ['required', 'string', Rule::in(['per_semester', 'per_year'])],
        ]);

        Hall::query()->create([
            'name' => trim($this->name),
            'master' => $this->master === '' ? null : trim($this->master),
            'cost' => (float) $this->cost,
            'period' => $this->period,
        ]);

        $this->reset(['name', 'master', 'cost']);
        $this->period = 'per_year';
        $this->resetPage();
        CollegeFlash::forNextRequestToo('status', __('Hall has been added.'));
    }

    public function editHall(int $hallId): void
    {
        $hall = Hall::query()->findOrFail($hallId);
        $this->editingHallId = $hall->id;
        $this->name = (string) $hall->name;
        $this->master = (string) ($hall->master ?? '');
        $this->cost = (string) $hall->cost;
        $this->period = (string) $hall->period;
        $this->resetValidation();
    }

    public function cancelEditHall(): void
    {
        $this->editingHallId = null;
        $this->reset(['name', 'master', 'cost']);
        $this->period = 'per_year';
        $this->resetValidation();
    }

    public function updateHall(): void
    {
        if ($this->editingHallId === null) {
            return;
        }

        $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('halls', 'name')->ignore($this->editingHallId)],
            'master' => ['nullable', 'string', 'max:255'],
            'cost' => ['required', 'numeric', 'min:0'],
            'period' => ['required', 'string', Rule::in(['per_semester', 'per_year'])],
        ]);

        $hall = Hall::query()->findOrFail($this->editingHallId);
        $hall->update([
            'name' => trim($this->name),
            'master' => $this->master === '' ? null : trim($this->master),
            'cost' => (float) $this->cost,
            'period' => $this->period,
        ]);

        $this->cancelEditHall();
        CollegeFlash::forNextRequestToo('status', __('Hall has been updated.'));
    }

    public function deleteHall(int $hallId): void
    {
        try {
            Hall::query()->findOrFail($hallId)->delete();
            if ($this->editingHallId === $hallId) {
                $this->cancelEditHall();
            }
            $this->resetPage();
            CollegeFlash::forNextRequestToo('status', __('Hall has been deleted.'));
        } catch (QueryException) {
            CollegeFlash::forNextRequestToo('backup_error', __('Cannot delete hall because related records still exist.'));
        }
    }

    public function render(): View
    {
        return view('livewire.admin.setup.setup-hall-page', [
            'halls' => Hall::query()->orderBy('name')->paginate(15),
        ])->layout('components.layouts.admin', ['title' => __('Setup halls')]);
    }
}
