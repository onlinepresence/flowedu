<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Staff;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\AcademicSession;
use App\Models\EvaluationForm;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class EvaluationIndexPage extends Component
{
    use DispatchesCollegeToasts;

    public string $search = '';

    public string $filterStatus = 'all';

    public ?string $deletingFormCode = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => 'all'],
    ];

    public string $createTitle = '';
    public string $createAcademicYear = '';
    public string $createStartTime = '';
    public string $createEndTime = '';
    public string $createControlType = 'auto';

    public function openCreateModal(): void
    {
        $this->createTitle = '';
        
        $currentSession = AcademicSession::query()->where('is_current', true)->first();
        if ($currentSession !== null) {
            $this->createAcademicYear = $currentSession->name;
        } else {
            $year = (int) now()->format('Y');
            $this->createAcademicYear = $year.'/'.($year + 1);
        }
        
        $this->createStartTime = now()->format('Y-m-d\TH:i');
        $this->createEndTime = now()->addMonth()->format('Y-m-d\TH:i');
        $this->createControlType = 'auto';

        $this->resetValidation();
        
        $this->dispatch('open-modal', 'ev-create');
    }

    public function closeCreateModal(): void
    {
        $this->dispatch('close-modal', 'ev-create');
    }

    public function saveNewForm(): void
    {
        $this->validate([
            'createTitle' => ['required', 'string', 'max:255'],
            'createAcademicYear' => ['required', 'string', 'max:9'],
            'createStartTime' => ['required', 'date'],
            'createEndTime' => ['required', 'date', 'after_or_equal:createStartTime'],
            'createControlType' => ['required', 'in:auto,manual'],
        ], [
            'createTitle.required' => __('The title field is required.'),
            'createAcademicYear.required' => __('The academic year field is required.'),
            'createStartTime.required' => __('The start time field is required.'),
            'createEndTime.required' => __('The end time field is required.'),
            'createEndTime.after_or_equal' => __('The end time must be a date after or equal to start time.'),
            'createControlType.required' => __('The control type field is required.'),
        ]);

        $code = strtoupper(Str::random(8));
        while (EvaluationForm::query()->where('unique_code', $code)->exists()) {
            $code = strtoupper(Str::random(8));
        }

        EvaluationForm::query()->create([
            'title' => $this->createTitle,
            'academic_year' => $this->createAcademicYear,
            'unique_code' => $code,
            'start_time' => $this->createStartTime,
            'end_time' => $this->createEndTime,
            'control_type' => $this->createControlType,
            'is_active' => false,
            'created_by' => auth()->id(),
            'last_edited_by' => auth()->id(),
        ]);

        $this->dispatch('close-modal', 'ev-create');
        $this->collegeToast(__('Evaluation form created.'));

        $this->redirect(route('admin.evaluation', ['form_code' => $code]), navigate: true);
    }

    public function openDeleteModal(string $formCode): void
    {
        $this->deletingFormCode = $formCode;
        $this->js('window.dispatchEvent(new CustomEvent("open-modal", { detail: "ev-delete" }))');
    }

    public function closeDeleteModal(): void
    {
        $this->deletingFormCode = null;
        $this->js('window.dispatchEvent(new CustomEvent("close-modal", { detail: "ev-delete" }))');
    }

    public function confirmDeleteForm(): void
    {
        if ($this->deletingFormCode === null) {
            return;
        }

        $form = EvaluationForm::query()->where('unique_code', $this->deletingFormCode)->first();
        if ($form === null) {
            $this->closeDeleteModal();

            return;
        }

        $hasNoResponses = $form->responses()->count() === 0;
        $hasNoQuestions = $form->questions()->count() === 0;
        $isCreatedRecently = $form->created_at !== null && $form->created_at->gt(now()->subHours(12));

        if (!($hasNoResponses && ($hasNoQuestions || $isCreatedRecently))) {
            $this->collegeToast(__('Only forms with no responses and either no questions or created within the last 12 hours can be deleted.'), 'error');
            $this->closeDeleteModal();

            return;
        }

        $form->delete();

        $this->closeDeleteModal();
        $this->collegeToast(__('Evaluation form deleted.'));
    }

    public function render(): View
    {
        $query = EvaluationForm::query();

        if ($this->search !== '') {
            $query->where(function ($q): void {
                $term = '%'.$this->search.'%';
                $q->where('title', 'like', $term)
                  ->orWhere('unique_code', 'like', $term);
            });
        }

        if ($this->filterStatus === 'active') {
            $query->where('is_active', 1);
        } elseif ($this->filterStatus === 'inactive') {
            $query->where('is_active', 0);
        } elseif ($this->filterStatus === 'closed') {
            $query->where('is_active', -1);
        }

        $forms = $query->withCount('responses')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return view('livewire.admin.staff.evaluation-index-page', [
            'forms' => $forms,
        ])->layout('components.layouts.admin', [
            'title' => __('Evaluations'),
            'headerTitle' => __('Evaluations'),
            'headerDescription' => __('View, schedule and manage teaching evaluation surveys and check student feedback response counts.'),
        ]);
    }
}
