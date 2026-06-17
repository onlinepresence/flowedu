<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Grading;

use App\Models\Program;
use App\Models\Student;
use App\Models\Result;
use App\Models\TranscriptRequest;
use App\Services\StudentGpaService;
use App\Livewire\Concerns\DispatchesCollegeToasts;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class TranscriptIndexPage extends Component
{
    use WithPagination, DispatchesCollegeToasts;

    public string $activeTab = 'history'; // history, generate

    // Generate Tab field
    public string $student_index = '';

    // Search filters
    public string $search = '';

    public string $filterProgram = '';

    public string $filterStatus = ''; // '', pending, processed, rejected

    // Selected Student for modal
    public ?Student $selectedStudent = null;

    public ?int $activeRequestId = null;

    public array $transcriptData = [];

    public array $selectedStudentStats = [];

    public bool $showInlinePreview = false;

    // Reject Modal state
    public ?int $rejectingRequestId = null;
    public string $rejectionRemarks = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterProgram(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->reset([
            'student_index',
            'search',
            'filterProgram',
            'filterStatus',
            'selectedStudent',
            'activeRequestId',
            'transcriptData',
            'selectedStudentStats',
            'showInlinePreview',
            'rejectingRequestId',
            'rejectionRemarks'
        ]);
        $this->resetPage();
    }

    public function generateTranscript(): void
    {
        $this->validate([
            'student_index' => ['required', 'string', 'max:100', 'exists:students,index_number'],
        ]);

        $student = Student::query()
            ->where('index_number', trim($this->student_index))
            ->first();

        if ($student === null) {
            $this->addError('student_index', __('No matching student found.'));
            return;
        }

        $this->showTranscriptModal($student->id, true);
    }

    public function showTranscriptModal(int $studentId, bool $isInline = false, ?int $requestId = null): void
    {
        $this->activeRequestId = $requestId;
        $this->selectedStudent = Student::query()
            ->with(['program.department.faculty'])
            ->findOrFail($studentId);

        // Get all results for student
        $results = Result::query()
            ->where('student_id', $studentId)
            ->with('course')
            ->get();

        // Group results by Year Level and Semester
        $results = $results->sortBy(fn($r) => [
            $r->course->year_level ?? 1,
            $r->course->course_semester ?? 'Semester 1'
        ]);

        $grouped = [];
        $cumulativePts = 0.0;
        $cumulativeCnt = 0;

        foreach ($results as $res) {
            $year = $res->course->year_level ?? 1;
            $sem = $res->course->course_semester ?? 'Semester 1';
            $key = "Year {$year} - {$sem}";

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'year' => $year,
                    'sem' => $sem,
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
            ];

            $pts = (float) $res->grade_points;
            $grouped[$key]['semester_points'] += $pts;
            $grouped[$key]['semester_count']++;

            $cumulativePts += $pts;
            $cumulativeCnt++;

            // Calculate semester GPA
            $grouped[$key]['gpa'] = number_format(
                $grouped[$key]['semester_count'] > 0 
                    ? $grouped[$key]['semester_points'] / $grouped[$key]['semester_count'] 
                    : 0.0,
                2
            );

            // Calculate cumulative GPA (CGPA) up to this semester
            $grouped[$key]['cgpa'] = number_format(
                $cumulativeCnt > 0 
                    ? $cumulativePts / $cumulativeCnt 
                    : 0.0,
                2
            );
        }

        $this->transcriptData = $grouped;
        $this->selectedStudentStats = [
            'cgpa' => $cumulativeCnt > 0 ? number_format($cumulativePts / $cumulativeCnt, 2) : '0.00',
            'credit_hours' => $cumulativeCnt,
        ];

        $this->showInlinePreview = $isInline;

        if (! $isInline) {
            $this->dispatch('open-modal', 'view-transcript-modal');
        }
    }

    public function markAsProcessed(int $requestId): void
    {
        $request = TranscriptRequest::query()->findOrFail($requestId);
        
        $request->update([
            'status' => 'processed',
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        if ($this->activeRequestId === $requestId) {
            $this->dispatch('close-modal', 'view-transcript-modal');
            $this->reset(['selectedStudent', 'activeRequestId', 'transcriptData', 'selectedStudentStats']);
        }

        $this->collegeToast(__('Transcript request marked as processed successfully.'));
    }

    public function openRejectModal(int $requestId): void
    {
        $this->rejectingRequestId = $requestId;
        $this->rejectionRemarks = '';
        $this->dispatch('open-modal', 'reject-request-modal');
    }

    public function submitRejection(): void
    {
        $this->validate([
            'rejectionRemarks' => ['required', 'string', 'max:500'],
        ]);

        if ($this->rejectingRequestId === null) {
            return;
        }

        $request = TranscriptRequest::query()->findOrFail($this->rejectingRequestId);
        $request->update([
            'status' => 'rejected',
            'remarks' => trim($this->rejectionRemarks),
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        $this->dispatch('close-modal', 'reject-request-modal');
        $this->collegeToast(__('Transcript request rejected successfully.'));
        $this->reset(['rejectingRequestId', 'rejectionRemarks']);
    }

    public function render(StudentGpaService $gpa): View
    {
        $requests = TranscriptRequest::query()
            ->with(['student.program', 'student.results'])
            ->when($this->search !== '', function ($query): void {
                $term = '%'.trim($this->search).'%';
                $query->whereHas('student', function ($q) use ($term): void {
                    $q->where('index_number', 'like', $term)
                        ->orWhere('lastname', 'like', $term)
                        ->orWhere('firstname', 'like', $term)
                        ->orWhere('othernames', 'like', $term);
                });
            })
            ->when($this->filterProgram !== '', function ($query): void {
                $query->whereHas('student', function ($q): void {
                    $q->where('program_id', (int) $this->filterProgram);
                });
            })
            ->when($this->filterStatus !== '', fn ($query) => $query->where('status', $this->filterStatus))
            ->orderByDesc('created_at')
            ->paginate(15);

        // Enhance items with live calculated CGPA & metadata
        $requestRows = collect($requests->items())->map(function (TranscriptRequest $req) use ($gpa): array {
            $student = $req->student;
            $stats = $gpa->statsForStudent($student);
            $hasResults = $student->results->isNotEmpty();

            return [
                'id' => $req->id,
                'student_id' => $student->id,
                'index_number' => $student->index_number,
                'name' => trim(($student->lastname ?? '').' '.($student->firstname ?? '').' '.($student->othernames ?? '')),
                'program_name' => $student->program?->name ?? '—',
                'cgpa' => $stats['cgpa'],
                'has_results' => $hasResults,
                'status' => $req->status,
                'purpose' => $req->purpose ?? '—',
                'remarks' => $req->remarks,
                'created_at' => $req->created_at,
            ];
        });

        return view('livewire.admin.grading.transcript-index-page', [
            'programs' => Program::query()->orderBy('name')->get(['id', 'name']),
            'requests' => $requests,
            'requestRows' => $requestRows,
        ])->layout('components.layouts.admin', [
            'title' => __('Transcripts'),
            'headerTitle' => __('Transcripts Management'),
            'headerDescription' => __('Generate, preview, and process student transcript requests.'),
        ]);
    }
}
