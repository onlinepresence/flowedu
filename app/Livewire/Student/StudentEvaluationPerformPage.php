<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\EvaluationForm;
use App\Models\EvaluationQuestion;
use App\Models\EvaluationResponse;
use App\Models\ResponseDetail;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class StudentEvaluationPerformPage extends Component
{
    use DispatchesCollegeToasts;

    public string $code = '';

    public ?EvaluationForm $form = null;

    public ?EvaluationResponse $response = null;

    public ?int $selected_teacher_id = null;

    public array $answers = [];

    public function mount(string $code): void
    {
        $this->code = $code;

        $this->form = EvaluationForm::query()->where('unique_code', $code)->firstOrFail();

        $now = now();
        if ($this->form->start_time > $now || $this->form->end_time < $now || ! $this->form->is_active) {
            abort(403, __('This evaluation is not open.'));
        }

        $user = Auth::user();
        $student = $user->student;
        if ($student === null) {
            abort(403);
        }

        $this->response = EvaluationResponse::query()->firstOrCreate(
            [
                'form_id' => $this->form->id,
                'student_id' => $user->id,
            ],
            [
                'student_department_id' => $student->department_id,
                'response_code' => (string) Str::uuid(),
                'status' => 'draft',
            ]
        );

        if ($this->response->status === 'submitted') {
            session()->flash('status', __('You have already submitted this evaluation.'));

            $this->redirect(route('student.evaluation'), navigate: true);

            return;
        }

        if ($this->response->teacher_id) {
            $this->selected_teacher_id = $this->response->teacher_id;
        } else {
            $yearLevelChar = substr($student->current_year ?? '100', 0, 1);
            $assignedTeacher = \App\Models\Teacher::query()
                ->whereHas('courses', function ($q) use ($student, $yearLevelChar) {
                    $q->where('program_id', $student->program_id)
                      ->where('year_level', $yearLevelChar);
                })
                ->first();

            if ($assignedTeacher) {
                $teacher = $assignedTeacher;
            } else {
                $teacher = \App\Models\Teacher::query()
                    ->where('department_id', $student->department_id)
                    ->first() ?? \App\Models\Teacher::query()->first();
            }

            if ($teacher) {
                $this->selected_teacher_id = $teacher->user_id;
                $this->response->teacher_id = $this->selected_teacher_id;
                $this->response->save();
            }
        }

        $details = ResponseDetail::query()
            ->where('response_id', $this->response->id)
            ->get()
            ->keyBy('question_id');

        $questions = EvaluationQuestion::query()
            ->where('form_id', $this->form->id)
            ->whereNull('deleted_at')
            ->orderBy('question_order')
            ->get();

        foreach ($questions as $q) {
            $d = $details->get($q->id);
            if ($d === null) {
                $this->answers[$q->id] = $this->defaultAnswer($q);

                continue;
            }

            if ($q->rating_type === 'select_multiple' && $d->answer_text !== null && $d->answer_text !== '') {
                $decoded = json_decode($d->answer_text, true);
                $this->answers[$q->id] = is_array($decoded) ? $decoded : [];
            } elseif (in_array($q->rating_type, ['scale_5', 'scale_10', 'boolean'], true)) {
                $this->answers[$q->id] = $d->answer_value === null ? '' : (string) $d->answer_value;
            } else {
                $this->answers[$q->id] = (string) ($d->answer_text ?? '');
            }
        }
    }

    private function defaultAnswer(EvaluationQuestion $q): mixed
    {
        return match ($q->rating_type) {
            'boolean' => null,
            'select_multiple' => [],
            default => '',
        };
    }

    public function saveDraft(): void
    {
        $this->persistDetails();
        $this->collegeToast(__('Draft saved.'));
    }

    public function submit(): void
    {
        if ($this->form === null || $this->response === null) {
            return;
        }

        $questions = EvaluationQuestion::query()
            ->where('form_id', $this->form->id)
            ->whereNull('deleted_at')
            ->orderBy('question_order')
            ->get();

        foreach ($questions as $q) {
            if (! $q->is_required) {
                continue;
            }
            $v = $this->answers[$q->id] ?? null;
            if ($this->isEmptyAnswer($q, $v)) {
                $this->addError('answers.'.$q->id, __('This question is required.'));

                return;
            }
        }

        $this->persistDetails();

        $this->response->forceFill([
            'status' => 'submitted',
            'submitted_at' => now(),
        ])->save();

        session()->flash('status', __('Evaluation submitted. Thank you.'));
        $this->redirect(route('student.evaluation', ['tab' => 'completed']), navigate: true);
    }

    private function isEmptyAnswer(EvaluationQuestion $q, mixed $v): bool
    {
        if ($q->rating_type === 'select_multiple') {
            return ! is_array($v) || $v === [];
        }

        if (in_array($q->rating_type, ['scale_5', 'scale_10', 'boolean'], true)) {
            return $v === null || $v === '';
        }

        return $v === null || trim((string) $v) === '';
    }

    private function persistDetails(): void
    {
        if ($this->form === null || $this->response === null) {
            return;
        }

        $questions = EvaluationQuestion::query()
            ->where('form_id', $this->form->id)
            ->whereNull('deleted_at')
            ->orderBy('question_order')
            ->get();

        DB::transaction(function () use ($questions) {
            foreach ($questions as $q) {
                $raw = $this->answers[$q->id] ?? null;
                $answerValue = null;
                $answerText = null;

                if (in_array($q->rating_type, ['scale_5', 'scale_10'], true)) {
                    $answerValue = $raw === null || $raw === '' ? null : (int) $raw;
                } elseif ($q->rating_type === 'boolean') {
                    $answerValue = match (true) {
                        $raw === null, $raw === '' => null,
                        (string) $raw === '1' => 1,
                        (string) $raw === '0' => 0,
                        default => null,
                    };
                } elseif ($q->rating_type === 'select_multiple') {
                    $answerText = is_array($raw) ? json_encode(array_values($raw)) : null;
                } else {
                    $answerText = $raw === null ? null : (string) $raw;
                }

                ResponseDetail::query()->updateOrCreate(
                    [
                        'response_id' => $this->response->id,
                        'question_id' => $q->id,
                    ],
                    [
                        'question_text_snapshot' => $q->question_text,
                        'answer_value' => $answerValue,
                        'answer_text' => $answerText,
                    ]
                );
            }
        });
    }

    public function render(): View
    {
        $questions = collect();
        if ($this->form !== null) {
            $questions = EvaluationQuestion::query()
                ->where('form_id', $this->form->id)
                ->whereNull('deleted_at')
                ->orderBy('question_order')
                ->get();
        }

        $teacherModel = null;
        if ($this->selected_teacher_id) {
            $teacherModel = \App\Models\Teacher::query()
                ->where('user_id', $this->selected_teacher_id)
                ->with(['user', 'department'])
                ->first();
        }

        $formTitle = $this->form?->title ?? __('Perform Evaluation');
        return view('livewire.student.student-evaluation-perform-page', [
            'questions' => $questions,
            'teacher' => $teacherModel,
        ])->layout('components.layouts.student', [
            'title' => $formTitle,
            'headerTitle' => $formTitle,
            'headerDescription' => __('Fill out the evaluation form below to rate your instructor\'s teaching performance.'),
        ]);
    }
}
