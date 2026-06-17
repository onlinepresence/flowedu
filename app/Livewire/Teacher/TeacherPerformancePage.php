<?php

declare(strict_types=1);

namespace App\Livewire\Teacher;

use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Result;
use App\Models\ResultSlip;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class TeacherPerformancePage extends Component
{
    public string|int $selectedSessionId = 'all';
    
    public string $selectedSemester = 'all';

    public function mount(): void
    {
        $session = AcademicSession::query()->where('is_active', true)->first();
        if ($session !== null) {
            $this->selectedSessionId = (string) $session->id;
        }
    }

    public function render(): View
    {
        $teacher = auth()->user()?->teacher;
        
        $sessions = AcademicSession::query()->orderBy('name', 'desc')->get();
        
        $resultCount = 0;
        $avgScore = null;
        $passRate = 0.0;
        $topCourse = null;
        $topCourseAvg = null;
        
        $gradeDistribution = [
            'A' => ['count' => 0, 'percentage' => 0.0],
            'B+' => ['count' => 0, 'percentage' => 0.0],
            'B' => ['count' => 0, 'percentage' => 0.0],
            'C' => ['count' => 0, 'percentage' => 0.0],
            'D' => ['count' => 0, 'percentage' => 0.0],
            'F' => ['count' => 0, 'percentage' => 0.0],
        ];

        $slipCounts = [
            'draft' => 0,
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
        ];

        $courseAnalytics = [];

        if ($teacher !== null) {
            // 1. Base results query
            $resultsQuery = Result::query()->where('teacher_id', $teacher->id);

            if ($this->selectedSessionId !== 'all') {
                $resultsQuery->where('academic_session_id', $this->selectedSessionId);
            }

            if ($this->selectedSemester !== 'all') {
                $resultsQuery->whereHas('course', function ($q) {
                    $q->where('course_semester', $this->selectedSemester);
                });
            }

            // Get total count & average score
            $resultCount = $resultsQuery->count();
            if ($resultCount > 0) {
                $avgScore = (float) $resultsQuery->whereNotNull('score')->avg('score');
                
                $passCount = (clone $resultsQuery)->where('score', '>=', 50.0)->count();
                $passRate = ($passCount / $resultCount) * 100;
            }

            // Top performing course
            $topCourseData = (clone $resultsQuery)
                ->select('course_id', DB::raw('AVG(score) as avg_score'))
                ->groupBy('course_id')
                ->orderByDesc('avg_score')
                ->first();

            if ($topCourseData !== null) {
                $topCourse = Course::find($topCourseData->course_id);
                $topCourseAvg = (float) $topCourseData->avg_score;
            }

            // Grade distributions count
            $rawGrades = (clone $resultsQuery)
                ->select('grade', DB::raw('COUNT(*) as count'))
                ->groupBy('grade')
                ->pluck('count', 'grade')
                ->toArray();

            foreach ($gradeDistribution as $gKey => $val) {
                $cnt = $rawGrades[$gKey] ?? 0;
                $pct = $resultCount > 0 ? ($cnt / $resultCount) * 100 : 0.0;
                $gradeDistribution[$gKey] = [
                    'count' => $cnt,
                    'percentage' => $pct,
                ];
            }

            // Slips stats
            $slipsQuery = ResultSlip::query()->where('teacher_id', $teacher->id);
            if ($this->selectedSessionId !== 'all') {
                $slipsQuery->where('academic_session_id', (int) $this->selectedSessionId);
            }
            if ($this->selectedSemester !== 'all') {
                $slipsQuery->where('semester', (int) $this->selectedSemester);
            }

            $rawSlips = $slipsQuery
                ->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            foreach ($slipCounts as $sKey => $val) {
                $slipCounts[$sKey] = $rawSlips[$sKey] ?? 0;
            }

            // Course breakdowns
            $courseStats = (clone $resultsQuery)
                ->select('course_id', 'academic_session_id',
                    DB::raw('COUNT(*) as total_students'),
                    DB::raw('AVG(score) as average_score'),
                    DB::raw('MAX(score) as max_score'),
                    DB::raw('MIN(score) as min_score'),
                    DB::raw('SUM(CASE WHEN score >= 50.0 THEN 1 ELSE 0 END) as pass_students')
                )
                ->groupBy('course_id', 'academic_session_id')
                ->get();

            foreach ($courseStats as $stat) {
                $course = Course::with('program')->find($stat->course_id);
                $sessionModel = AcademicSession::find($stat->academic_session_id);
                if ($course !== null && $sessionModel !== null) {
                    $total = (int) $stat->total_students;
                    $passCount = (int) $stat->pass_students;
                    $cPassRate = $total > 0 ? ($passCount / $total) * 100 : 0.0;

                    $courseAnalytics[] = [
                        'course' => $course,
                        'session' => $sessionModel,
                        'total_students' => $total,
                        'average_score' => (float) $stat->average_score,
                        'max_score' => (float) $stat->max_score,
                        'min_score' => (float) $stat->min_score,
                        'pass_rate' => $cPassRate,
                    ];
                }
            }

            usort($courseAnalytics, function($a, $b) {
                $cmp = strcmp($a['course']->code, $b['course']->code);
                if ($cmp === 0) {
                    return strcmp($b['session']->name, $a['session']->name);
                }
                return $cmp;
            });
        }

        return view('livewire.teacher.teacher-performance-page', [
            'sessions' => $sessions,
            'resultCount' => $resultCount,
            'avgScore' => $avgScore,
            'passRate' => $passRate,
            'topCourse' => $topCourse,
            'topCourseAvg' => $topCourseAvg,
            'gradeDistribution' => $gradeDistribution,
            'slipCounts' => $slipCounts,
            'courseAnalytics' => $courseAnalytics,
        ])->layout('components.layouts.teacher', [
            'title' => __('Performance'),
            'headerDescription' => __('View analytical charts and class scoring summaries for your courses.'),
        ]);
    }
}
