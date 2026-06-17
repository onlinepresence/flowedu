<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Staff;

use App\Models\EvaluationForm;
use App\Models\EvaluationQuestion;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class EvaluationPreviewPage extends Component
{
    public string $form_code = '';

    public function mount(string $form_code): void
    {
        $this->form_code = $form_code;
    }

    public function render(): View
    {
        $form = EvaluationForm::query()->where('unique_code', $this->form_code)->firstOrFail();
        $questions = EvaluationQuestion::query()
            ->where('form_id', $form->id)
            ->whereNull('deleted_at')
            ->orderBy('question_order')
            ->get();

        return view('livewire.admin.staff.evaluation-preview-page', [
            'form' => $form,
            'questions' => $questions,
        ])->layout('components.layouts.admin', ['title' => __('Evaluation preview'), 'showPageHeading' => false]);
    }
}
