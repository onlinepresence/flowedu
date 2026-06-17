<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Models\EvaluationForm;
use App\Models\EvaluationResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class StudentEvaluationPage extends Component
{
    public string $tab = 'ongoing';

    public function mount(?string $tab = null): void
    {
        if ($tab !== null && $tab !== '') {
            $this->tab = in_array($tab, ['ongoing', 'completed'], true) ? $tab : 'ongoing';
        }
    }

    public function render(): View
    {
        $userId = Auth::id();
        $now = now();

        if ($this->tab === 'completed') {
            $formIds = EvaluationResponse::query()
                ->where('student_id', $userId)
                ->where('status', 'submitted')
                ->pluck('form_id');

            $forms = EvaluationForm::query()
                ->whereIn('id', $formIds)
                ->orderByDesc('end_time')
                ->get();
        } else {
            $forms = EvaluationForm::query()
                ->where('start_time', '<=', $now)
                ->where('end_time', '>=', $now)
                ->where('is_active', true)
                ->orderBy('end_time')
                ->get()
                ->filter(function (EvaluationForm $form) use ($userId) {
                    $r = EvaluationResponse::query()
                        ->where('form_id', $form->id)
                        ->where('student_id', $userId)
                        ->first();

                    return $r === null || $r->status !== 'submitted';
                })
                ->values();
        }

        return view('livewire.student.student-evaluation-page', [
            'forms' => $forms,
        ])->layout('components.layouts.student', [
            'title' => __('Lecturer Evaluation'),
            'headerTitle' => __('Lecturer Evaluation'),
            'headerDescription' => __('Share your feedback regarding lecturer performance and course quality.'),
        ]);
    }
}
