<?php

declare(strict_types=1);

namespace App\Livewire\Teacher;

use App\Models\AcademicSession;
use App\Models\Student;
use App\Models\TeacherAssignment;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class TeacherCoursesPage extends Component
{
    public function render(): View
    {
        $teacher = auth()->user()?->teacher;

        $activeSession = AcademicSession::query()->where('is_active', true)->first();
        if ($activeSession === null) {
            $activeSession = AcademicSession::query()->orderByDesc('id')->first();
        }

        $assignments = $teacher && $activeSession
            ? TeacherAssignment::query()
                ->where('teacher_id', $teacher->id)
                ->where('session_id', $activeSession->id)
                ->with(['course.program', 'program'])
                ->get()
            : collect();

        $studentCounts = [];
        foreach ($assignments as $asg) {
            $studentCounts[$asg->id] = (int) Student::query()
                ->where('program_id', $asg->program_id)
                ->where('current_year', (string) $asg->level)
                ->where('approved', true)
                ->count();
        }

        return view('livewire.teacher.teacher-courses-page', [
            'assignments' => $assignments,
            'studentCounts' => $studentCounts,
        ])->layout('components.layouts.teacher', [
            'title' => __('My courses'),
            'headerDescription' => __('View all courses assigned to you for the active academic session.'),
        ]);
    }
}
