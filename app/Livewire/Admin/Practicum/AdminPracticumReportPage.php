<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Practicum;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\AcademicSession;
use App\Models\TeachingPracticeSupervision;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class AdminPracticumReportPage extends Component
{
    use DispatchesCollegeToasts, WithPagination;

    public ?int $academicSessionId = null;
    public string $search = '';
    public string $statusFilter = 'all'; // all, assigned, evaluated

    protected $updatesQueryString = ['search', 'statusFilter'];

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasAdminPermission('nav_practicum_report'), 403);

        $session = AcademicSession::where('is_current', true)->first() ?? AcademicSession::first();
        if ($session) {
            $this->academicSessionId = (int)$session->id;
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function exportReport()
    {
        if (!$this->academicSessionId) {
            $this->collegeToast(__('No academic session active.'), 'danger');
            return null;
        }

        $session = AcademicSession::findOrFail($this->academicSessionId);
        $query = TeachingPracticeSupervision::with(['student.user', 'teacher.user'])
            ->where('academic_session_id', $this->academicSessionId);

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $supervisions = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
        ];

        $filename = 'practicum_report_' . Str::slug($session->name) . '.csv';

        $callback = function() use ($supervisions) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($file, [
                'Student Name',
                'Student Index',
                'Student Email',
                'Supervisor Name',
                'Supervisor Email',
                'Partnership School',
                'Status',
                'Score (%)',
                'Evaluation Notes',
                'Evaluated At'
            ]);

            foreach ($supervisions as $s) {
                fputcsv($file, [
                    $s->student->user->name,
                    $s->student->index_number,
                    $s->student->user->email,
                    $s->teacher->user->name,
                    $s->teacher->user->email,
                    $s->partnership_school,
                    $s->status,
                    $s->score !== null ? number_format((float)$s->score, 2) : 'N/A',
                    $s->evaluation_notes ?? '',
                    $s->evaluated_at ? $s->evaluated_at->format('Y-m-d H:i') : ''
                ]);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    public function render(): View
    {
        $sessions = AcademicSession::orderBy('name', 'desc')->get();

        // 1. Calculate Metrics
        $totalTrainees = TeachingPracticeSupervision::where('academic_session_id', $this->academicSessionId)->count();
        $evaluatedCount = TeachingPracticeSupervision::where('academic_session_id', $this->academicSessionId)
            ->where('status', 'evaluated')
            ->count();
        $pendingCount = $totalTrainees - $evaluatedCount;
        $averageScore = TeachingPracticeSupervision::where('academic_session_id', $this->academicSessionId)
            ->where('status', 'evaluated')
            ->avg('score');

        // 2. Fetch Supervisions
        $query = TeachingPracticeSupervision::with(['student.user', 'teacher.user'])
            ->where('academic_session_id', $this->academicSessionId);

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->whereHas('student.user', function ($sq) {
                    $sq->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('teacher.user', function ($tq) {
                    $tq->where('name', 'like', '%' . $this->search . '%');
                })
                ->orWhere('partnership_school', 'like', '%' . $this->search . '%');
            });
        }

        $supervisions = $query->paginate(15);

        return view('livewire.admin.practicum.admin-practicum-report-page', [
            'sessions' => $sessions,
            'supervisions' => $supervisions,
            'totalTrainees' => $totalTrainees,
            'evaluatedCount' => $evaluatedCount,
            'pendingCount' => $pendingCount,
            'averageScore' => $averageScore,
        ])->layout('components.layouts.admin', [
            'title' => __('TP Performance Reports'),
            'headerTitle' => __('Supervision Performance & Reports'),
            'headerDescription' => __('View evaluation aggregates, search supervisor scorecards, and export student progress reviews.'),
        ]);
    }
}
