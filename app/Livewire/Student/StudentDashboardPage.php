<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Models\AcademicSession;
use App\Models\FeePayment;
use App\Models\TimetableClass;
use App\Services\SchoolLicenceService;
use App\Services\StudentGpaService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StudentDashboardPage extends Component
{
    public function render(SchoolLicenceService $licenceService, StudentGpaService $gpaService): View
    {
        $user = auth()->user();
        if ($user === null) {
            abort(403);
        }

        $student = $user->student;
        if ($student === null) {
            abort(403);
        }

        $student->loadMissing('program');
        $gpa = $gpaService->statsForStudent($student);
        $outstanding = (float) FeePayment::query()
            ->where('student_id', $student->id)
            ->sum('balance');

        $clearanceEligible = null;
        $clearanceFinalYear = null;
        if ($licenceService->can('student_welfare')) {
            $clearanceFinalYear = 400;
            $clearanceEligible = $clearanceFinalYear === (int) $student->current_year;
        }

        $yearDisplay = $student->current_year !== null && $student->current_year !== ''
            ? (string) $student->current_year
            : '—';
        $levelLabel = $student->graduated
            ? __('Graduated')
            : __('Level :level', ['level' => $yearDisplay]);

        $welcomeName = $student->lastname ?: ($user->username ?? $user->email ?? '');
        $othernames = $student->othernames;

        $todayDay = now()->format('l');
        $currentSessionId = AcademicSession::query()->where('is_current', true)->value('id');

        $todaySlots = collect();
        if ($student->program_id !== null && $student->current_year !== null) {
            $todaySlots = TimetableClass::query()
                ->where('program_id', $student->program_id)
                ->where('day', $todayDay)
                ->whereHas('timetable', function ($query) use ($student, $currentSessionId) {
                    $query->where('level', $student->current_year);
                    if ($currentSessionId !== null) {
                        $query->where('session_id', $currentSessionId);
                    }
                })
                ->with(['course', 'teacher.user'])
                ->orderBy('start_time')
                ->get();
        }

        return view('livewire.student.student-dashboard-page', [
            'student' => $student,
            'welcomeName' => $welcomeName,
            'othernames' => $othernames,
            'gpa' => $gpa,
            'outstanding' => $outstanding,
            'levelLabel' => $levelLabel,
            'clearanceEligible' => $clearanceEligible,
            'clearanceFinalYear' => $clearanceFinalYear,
            'canFinance' => $licenceService->can('finance'),
            'canClearance' => $licenceService->can('student_welfare'),
            'todaySlots' => $todaySlots,
        ])->layout('components.layouts.student', [
            'title' => __('Dashboard'),
            'headerTitle' => __('Welcome, :name', ['name' => $welcomeName]),
            'headerDescription' => $othernames ?: null,
        ]);
    }
}
