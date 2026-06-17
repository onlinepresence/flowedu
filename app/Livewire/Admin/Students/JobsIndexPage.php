<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Students;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\JobAlert;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class JobsIndexPage extends Component
{
    use DispatchesCollegeToasts;
    use WithPagination;

    // Filters
    public string $search = '';
    public string $typeFilter = '';
    public string $statusFilter = 'active'; // active, expired, archived

    // Create/Edit Modals
    public bool $showFormModal = false;
    public ?int $editingId = null;

    // Form inputs
    public string $title = '';
    public string $type = 'job';
    public string $company_or_organizer = '';
    public string $description = '';
    public string $requirements = '';
    public string $expiry_date = '';

    // Reactivate Modal properties
    public bool $showReactivateModal = false;
    public ?int $reactivateId = null;
    public string $newExpiryDate = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'typeFilter' => ['except' => ''],
        'statusFilter' => ['except' => 'active'],
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
        // Dispatch browser event to reset/init Quill
        $this->dispatch('init-editor', content: '');
    }

    public function openEditModal(int $id): void
    {
        $this->resetForm();
        $row = JobAlert::findOrFail($id);
        $this->editingId = $id;
        $this->title = $row->title;
        $this->type = $row->type;
        $this->company_or_organizer = $row->company_or_organizer ?? '';
        $this->description = $row->description;
        $this->requirements = $row->requirements ?? '';
        $this->expiry_date = $row->expiry_date->format('Y-m-d');

        $this->showFormModal = true;
        $this->dispatch('init-editor', content: $this->description);
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:job,activity'],
            'company_or_organizer' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'requirements' => ['nullable', 'string'],
            'expiry_date' => ['required', 'date'],
        ]);

        $payload = [
            'title' => $this->title,
            'type' => $this->type,
            'company_or_organizer' => $this->company_or_organizer ?: null,
            'description' => $this->description,
            'requirements' => $this->requirements ?: null,
            'expiry_date' => $this->expiry_date,
        ];

        if ($this->editingId) {
            $row = JobAlert::findOrFail($this->editingId);
            $row->update($payload);
            $this->collegeToast(__('Job/Activity updated successfully.'));
        } else {
            JobAlert::create($payload);
            $this->collegeToast(__('Job/Activity created successfully.'));
        }

        $this->showFormModal = false;
        $this->resetForm();
    }

    public function openReactivateModal(int $id): void
    {
        $row = JobAlert::findOrFail($id);
        $this->reactivateId = $id;
        $this->newExpiryDate = now()->addMonth()->format('Y-m-d');
        $this->showReactivateModal = true;
    }

    public function reactivate(): void
    {
        $this->validate([
            'newExpiryDate' => ['required', 'date', 'after:today'],
        ]);

        if ($this->reactivateId) {
            $row = JobAlert::findOrFail($this->reactivateId);
            $row->update([
                'expiry_date' => $this->newExpiryDate,
            ]);
            $this->collegeToast(__('Job/Activity reactivated successfully.'));
        }

        $this->showReactivateModal = false;
        $this->reactivateId = null;
        $this->newExpiryDate = '';
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function closeReactivateModal(): void
    {
        $this->showReactivateModal = false;
        $this->reactivateId = null;
        $this->newExpiryDate = '';
    }

    public function delete(int $id): void
    {
        JobAlert::destroy($id);
        $this->collegeToast(__('Job/Activity deleted successfully.'));
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->type = 'job';
        $this->company_or_organizer = '';
        $this->description = '';
        $this->requirements = '';
        $this->expiry_date = now()->addMonth()->format('Y-m-d');
    }

    public function render(): View
    {
        $today = now()->toDateString();
        $threeMonthsAgo = now()->subMonths(3)->toDateString();

        $query = JobAlert::query()
            ->when($this->search !== '', function ($q) {
                $q->where(function ($sub) {
                    $sub->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('company_or_organizer', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->typeFilter !== '', fn ($q) => $q->where('type', $this->typeFilter));

        if ($this->statusFilter === 'active') {
            $query->where('expiry_date', '>=', $today);
        } elseif ($this->statusFilter === 'expired') {
            $query->where('expiry_date', '<', $today)
                  ->where('expiry_date', '>=', $threeMonthsAgo);
        } elseif ($this->statusFilter === 'archived') {
            $query->where('expiry_date', '<', $threeMonthsAgo);
        }

        $rows = $query->orderByDesc('id')->paginate(15);

        return view('livewire.admin.students.jobs-index-page', [
            'rows' => $rows,
        ])->layout('components.layouts.admin', [
            'title' => __('Jobs & Activities Management'),
            'hideHeader' => true,
        ]);
    }
}
