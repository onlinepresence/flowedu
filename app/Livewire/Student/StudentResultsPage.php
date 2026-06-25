<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Models\Result;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StudentResultsPage extends Component
{
    public bool $redirectEnabled = false;

    public string $externalGradingUrl = '';

    public string $selectedLevel = 'all';

    public array $availableLevels = [];

    public array $transcriptData = [];

    public function mount(): void
    {
        $settings = \App\Models\Setting::query()
            ->where('category', 'system_preferences')
            ->pluck('setting_value', 'setting_key');

        $this->redirectEnabled = ($settings['system_preferences.student_grading_redirect'] ?? '0') === '1';
        $this->externalGradingUrl = (string) ($settings['system_preferences.external_grading_url'] ?? '');

        if ($this->redirectEnabled && !empty($this->externalGradingUrl)) {
            $this->redirect($this->externalGradingUrl);
            return;
        }

        $student = auth()->user()?->student;
        $grouped = [];
        $levels = [];

        if ($student !== null) {
            $results = Result::query()
                ->where('student_id', $student->id)
                ->with(['course', 'academicSession'])
                ->get();

            $results = $results->sortBy(fn($r) => [
                (int) ($r->course->year_level ?? 1),
                (int) ($r->course->course_semester ?? 1)
            ]);

            $cumulativePts = 0.0;
            $cumulativeCnt = 0;

            foreach ($results as $res) {
                $year = $res->course->year_level ?? '1';
                $level = (int) $year * 100;
                $sem = $res->course->course_semester ?? '1';
                $academicYear = $res->academicSession?->name ?? '';

                $key = "Level {$level} - Semester {$sem}";

                if (! isset($grouped[$key])) {
                    $grouped[$key] = [
                        'level' => $level,
                        'semester' => $sem,
                        'academic_year' => $academicYear,
                        'results' => [],
                        'semester_points' => 0.0,
                        'semester_count' => 0,
                        'gpa' => '0.00',
                        'cgpa' => '0.00',
                    ];
                }

                $grouped[$key]['results'][] = [
                    'code' => $res->course->code ?? '—',
                    'name' => $res->course->name ?? '—',
                    'score' => floatval($res->score),
                    'grade' => $res->grade ?? '—',
                    'points' => floatval($res->grade_points),
                    'credit_hours' => 1,
                ];

                $pts = (float) $res->grade_points;
                $grouped[$key]['semester_points'] += $pts;
                $grouped[$key]['semester_count']++;

                $cumulativePts += $pts;
                $cumulativeCnt++;

                $grouped[$key]['gpa'] = number_format(
                    $grouped[$key]['semester_count'] > 0
                        ? $grouped[$key]['semester_points'] / $grouped[$key]['semester_count']
                        : 0.0,
                    2,
                    '.',
                    ''
                );

                $grouped[$key]['cgpa'] = number_format(
                    $cumulativeCnt > 0
                        ? $cumulativePts / $cumulativeCnt
                        : 0.0,
                    2,
                    '.',
                    ''
                );
            }

            $levels = collect($grouped)->pluck('level')->unique()->sort()->values()->all();
        }

        $this->transcriptData = $grouped;
        $this->availableLevels = $levels;
    }

    public function render(): View
    {
        $filteredData = $this->transcriptData;
        if ($this->selectedLevel !== 'all') {
            $filteredData = collect($this->transcriptData)
                ->filter(fn($sem) => $sem['level'] == $this->selectedLevel)
                ->all();
        }

        return view('livewire.student.student-results-page', [
            'filteredData' => $filteredData,
        ])->layout('components.layouts.student', [
            'title' => __('My Results'),
            'headerTitle' => __('Academic Results'),
            'headerDescription' => $this->redirectEnabled
                ? __('Access your published academic performance reviews.')
                : __('View and track your semester-by-semester academic grades and GPAs.'),
        ]);
    }
}
