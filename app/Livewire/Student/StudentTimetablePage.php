<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Models\TimetableClass;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StudentTimetablePage extends Component
{
    public bool $showTodayOnly = false;

    public function getSlotStatus($slot): string
    {
        $today = strtolower(now()->format('l'));
        if (strtolower($slot->day ?? '') !== $today) {
            return 'other-day';
        }

        $nowStr = now()->format('H:i:s');
        $start = $slot->start_time;
        $end = $slot->end_time;

        if (!$start || !$end) {
            return 'today';
        }

        if ($nowStr >= $start && $nowStr <= $end) {
            return 'in-progress';
        }

        if ($nowStr < $start) {
            return 'upcoming';
        }

        return 'past';
    }

    public function render(): View
    {
        $student = auth()->user()?->student;
        $slots = collect();
        $sessionName = __('Active Session');

        if ($student !== null && $student->program_id !== null) {
            $sessionId = \App\Models\AcademicSession::activeSessionId();
            $activeSession = \App\Models\AcademicSession::query()->find($sessionId);
            if ($activeSession !== null) {
                $sessionName = $activeSession->name;
            }

            $levelInt = (int) $student->current_year;

            $slots = TimetableClass::query()
                ->where('program_id', $student->program_id)
                ->whereHas('timetable', function ($q) use ($sessionId, $levelInt) {
                    if ($sessionId) {
                        $q->where('session_id', $sessionId);
                    }
                    $q->where('level', $levelInt);
                })
                ->with(['course', 'teacher'])
                ->get();

            $dayOrder = [
                'monday' => 1,
                'tuesday' => 2,
                'wednesday' => 3,
                'thursday' => 4,
                'friday' => 5,
                'saturday' => 6,
                'sunday' => 7,
            ];

            $slots = $slots->sortBy(function ($slot) use ($dayOrder) {
                $dayKey = strtolower($slot->day ?? '');
                return [
                    $dayOrder[$dayKey] ?? 99,
                    $slot->start_time
                ];
            })->values();

            if ($this->showTodayOnly) {
                $todayName = strtolower(now()->format('l'));
                $slots = $slots->filter(fn($slot) => strtolower($slot->day ?? '') === $todayName)->values();
            }
        }

        return view('livewire.student.student-timetable-page', [
            'slots' => $slots,
        ])->layout('components.layouts.student', [
            'title' => __('My Timetable'),
            'headerTitle' => __('Class Timetable'),
            'headerDescription' => __('View your weekly class schedule for the ') . $sessionName . ' ' . __('academic year.'),
        ]);
    }
}
