<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Models\AcademicInformation;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StudentAttendancePage extends Component
{
    public function render(): View
    {
        $student = auth()->user()?->student;
        $rows = $student
            ? AcademicInformation::query()
                ->where('student_id', $student->id)
                ->with('program')
                ->orderByDesc('id')
                ->get()
            : collect();

        // Fetch preferences
        $settings = \App\Models\Setting::query()
            ->where('category', 'system_preferences')
            ->pluck('setting_value', 'setting_key');

        $showPolicy = (bool) ($settings['system_preferences.show_attendance_policy'] ?? true);
        $minThreshold = (int) ($settings['system_preferences.min_attendance_threshold'] ?? 75);

        // Calculate statistics based on a 120 days per academic session/semester benchmark
        $totalDaysAttended = 0;
        $cumulativeRate = 0.0;
        $standing = 'Good';
        $totalMaxDays = $rows->count() * 120;

        if ($rows->isNotEmpty()) {
            $totalDaysAttended = (int) $rows->sum('attendance_record');
            $cumulativeRate = min(100.0, ($totalDaysAttended / max(1, $totalMaxDays)) * 100);

            // Eligibility standing is determined by the current (latest) semester's attendance rate
            $currentSemester = $rows->first();
            $currentRate = min(100.0, ($currentSemester->attendance_record / 120) * 100);

            if ($currentRate >= ($minThreshold + 10)) {
                $standing = 'Good';
            } elseif ($currentRate >= $minThreshold) {
                $standing = 'Warning';
            } else {
                $standing = 'Critical';
            }
        }

        return view('livewire.student.student-attendance-page', [
            'rows' => $rows,
            'student' => $student,
            'totalDaysAttended' => $totalDaysAttended,
            'cumulativeRate' => $cumulativeRate,
            'standing' => $standing,
            'totalMaxDays' => $totalMaxDays,
            'showPolicy' => $showPolicy,
            'minThreshold' => $minThreshold,
        ])->layout('components.layouts.student', [
            'title' => __('Attendance'),
            'hideHeader' => true,
        ]);
    }
}
