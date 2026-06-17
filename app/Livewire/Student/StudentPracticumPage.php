<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Models\TeachingPracticeSupervision;
use App\Services\SchoolLicenceService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StudentPracticumPage extends Component
{
    public function mount(SchoolLicenceService $licenceService): void
    {
        abort_unless($licenceService->can('practicum'), 403);
    }

    public function render(): View
    {
        $student = auth()->user()?->student;

        $supervision = $student
            ? TeachingPracticeSupervision::with(['teacher.user', 'academicSession'])
                ->where('student_id', $student->id)
                ->orderBy('created_at', 'desc')
                ->first()
            : null;

        return view('livewire.student.student-practicum-page', [
            'supervision' => $supervision,
        ])->layout('components.layouts.student', [
            'title' => __('Teaching Practice'),
            'headerTitle' => __('Teaching Practice (Practicum)'),
            'headerDescription' => __('View details about your assigned supervisor, placement school, and evaluations.'),
        ]);
    }
}
