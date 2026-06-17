<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Models\AcademicSession;
use App\Models\ScholarshipRecipient;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StudentFeesAllowancePage extends Component
{
    public string $sessionFilter = '';

    protected $queryString = [
        'sessionFilter' => ['except' => ''],
    ];

    public function render(): View
    {
        $student = auth()->user()?->student;
        $studentId = $student?->id;

        $query = ScholarshipRecipient::query()
            ->where('student_id', $studentId)
            ->whereHas('scholarship', function ($q) {
                $q->whereIn('name', ['Student Allowance', 'Monthly Student Allowance Scheme']);
            })
            ->with(['scholarship', 'academicSession']);

        if ($this->sessionFilter !== '') {
            $query->where('academic_session_id', (int) $this->sessionFilter);
        }

        $rows = $query->orderByDesc('id')->get();

        $sessions = $student ? get_student_academic_sessions($student) : collect();

        return view('livewire.student.student-fees-allowance-page', [
            'rows' => $rows,
            'sessions' => $sessions,
        ])->layout('components.layouts.student', [
            'title' => __('My Allowances'),
            'hideHeader' => true,
        ]);
    }
}
