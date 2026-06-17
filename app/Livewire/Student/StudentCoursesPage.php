<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Models\Course;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StudentCoursesPage extends Component
{
    public function render(): View
    {
        $student = auth()->user()?->student;
        $courses = collect();
        $semesterName = __('Active Semester');

        if ($student !== null && $student->program_id !== null) {
            $activeSemester = \App\Models\Semester::query()
                ->where('is_active', true)
                ->first() ?? \App\Models\Semester::query()->orderByDesc('id')->first();

            $activeSemesterCode = '1';
            if ($activeSemester !== null) {
                $semesterName = $activeSemester->name;
                if (str_contains(strtolower($semesterName), 'second') || str_contains($semesterName, '2')) {
                    $activeSemesterCode = '2';
                }
            }

            $courses = Course::query()
                ->where('program_id', $student->program_id)
                ->where('course_semester', $activeSemesterCode)
                ->with('teacher')
                ->orderBy('year_level')
                ->orderBy('code')
                ->get();
        }

        return view('livewire.student.student-courses-page', [
            'courses' => $courses,
        ])->layout('components.layouts.student', [
            'title' => __('My Courses'),
            'headerTitle' => __('Registered Courses'),
            'headerDescription' => __('View all courses registered under your program for ') . $semesterName . '.',
        ]);
    }
}
