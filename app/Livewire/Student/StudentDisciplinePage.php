<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Models\DisciplinaryRecord;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StudentDisciplinePage extends Component
{
    public function render(): View
    {
        $student = auth()->user()?->student;
        $rows = $student
            ? DisciplinaryRecord::query()
                ->where('index_number', $student->index_number)
                ->with('program')
                ->orderByDesc('date_of_action')
                ->get()
            : collect();

        return view('livewire.student.student-discipline-page', [
            'rows' => $rows,
        ])->layout('components.layouts.student', ['title' => __('Discipline'), 'hideHeader' => true]);
    }
}
