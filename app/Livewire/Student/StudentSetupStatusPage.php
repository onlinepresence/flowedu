<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Actions\Students\ActivateStudentDashboardAction;
use App\Models\Student;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class StudentSetupStatusPage extends Component
{
    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $student = $user->student;

        if ($student === null) {
            $this->redirect(route('student.setup.personal'));

            return;
        }

        if ($student->approved && ! $student->is_new) {
            $this->redirect(route('student.dashboard'));
        }
    }

    public function activate(ActivateStudentDashboardAction $action): void
    {
        /** @var Student|null $student */
        $student = auth()->user()->student;
        if ($student === null) {
            return;
        }

        try {
            $action->execute($student);
        } catch (ValidationException $e) {
            $this->setErrorBag($e->errors());

            return;
        }

        session()->flash('status', __('Admission process successfully completed'));
        $this->redirect(route('student.dashboard'), navigate: true);
    }

    public function render(): View
    {
        /** @var Student|null $student */
        $student = auth()->user()->student;

        return view('livewire.student.student-setup-status-page', [
            'student' => $student,
        ])->layout('components.layouts.student', ['title' => __('Admission status')]);
    }
}
