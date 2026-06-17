<?php

declare(strict_types=1);

namespace App\Livewire\Teacher;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\AcademicSession;
use App\Models\TeachingPracticeSupervision;
use App\Services\SchoolLicenceService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class TeacherPracticumPage extends Component
{
    use DispatchesCollegeToasts, WithPagination;

    public ?int $academicSessionId = null;
    public string $search = '';

    // Evaluation Form properties
    public ?int $selectedSupervisionId = null;
    public string $studentName = '';
    public string $partnershipSchool = '';
    public string $score = '';
    public string $evaluationNotes = '';
    public bool $showEvaluateModal = false;

    public function mount(SchoolLicenceService $licenceService): void
    {
        abort_unless($licenceService->can('practicum'), 403);

        $session = AcademicSession::where('is_current', true)->first() ?? AcademicSession::first();
        if ($session) {
            $this->academicSessionId = (int)$session->id;
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openEvaluateModal(int $supervisionId): void
    {
        $this->resetValidation();
        $supervision = TeachingPracticeSupervision::with('student.user')->findOrFail($supervisionId);

        $this->selectedSupervisionId = $supervisionId;
        $this->studentName = $supervision->student->user->name;
        $this->partnershipSchool = $supervision->partnership_school;
        $this->score = $supervision->score !== null ? (string)$supervision->score : '';
        $this->evaluationNotes = $supervision->evaluation_notes ?? '';

        $this->showEvaluateModal = true;
        $this->dispatch('open-modal', 'evaluate-student-modal');
    }

    public function closeEvaluateModal(): void
    {
        $this->showEvaluateModal = false;
        $this->dispatch('close-modal', 'evaluate-student-modal');
    }

    public function saveEvaluation(): void
    {
        $this->validate([
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'evaluationNotes' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $supervision = TeachingPracticeSupervision::findOrFail($this->selectedSupervisionId);
        $supervision->update([
            'score' => (float)$this->score,
            'evaluation_notes' => $this->evaluationNotes,
            'status' => 'evaluated',
            'evaluated_at' => now(),
        ]);

        $this->showEvaluateModal = false;
        $this->dispatch('close-modal', 'evaluate-student-modal');
        $this->collegeToast(__('Evaluation checklist submitted successfully.'));
    }

    public function downloadRoster()
    {
        $teacher = auth()->user()?->teacher;
        if (!$teacher) {
            abort(403);
        }

        if (!$this->academicSessionId) {
            $this->collegeToast(__('No academic session active.'), 'danger');
            return null;
        }

        $session = AcademicSession::findOrFail($this->academicSessionId);
        $supervisions = TeachingPracticeSupervision::with('student.user')
            ->where('teacher_id', $teacher->id)
            ->where('academic_session_id', $this->academicSessionId)
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
        ];

        $filename = str_replace(['/', '\\'], '_', 'my_practicum_roster_' . $session->name . '.csv');

        $callback = function() use ($supervisions) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($file, ['Student Name', 'Student Email', 'Student Index', 'Partnership School', 'Status', 'Score (%)', 'Evaluation Notes']);
            foreach ($supervisions as $s) {
                fputcsv($file, [
                    $s->student->user->name,
                    $s->student->user->email,
                    $s->student->index_number,
                    $s->partnership_school,
                    $s->status,
                    $s->score !== null ? number_format((float)$s->score, 2) : '',
                    $s->evaluation_notes ?? ''
                ]);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    public function render(): View
    {
        $teacher = auth()->user()?->teacher;
        $sessions = AcademicSession::orderBy('name', 'desc')->get();

        $query = TeachingPracticeSupervision::with('student.user')
            ->where('teacher_id', $teacher?->id ?? 0)
            ->where('academic_session_id', $this->academicSessionId);

        if ($this->search !== '') {
            $query->whereHas('student.user', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        $supervisions = $query->paginate(15);

        return view('livewire.teacher.teacher-practicum-page', [
            'sessions' => $sessions,
            'supervisions' => $supervisions,
        ])->layout('components.layouts.teacher', [
            'title' => __('Teaching Practice Supervision'),
            'headerDescription' => __('Evaluate and monitor student teacher performance during classroom practicum placements.'),
        ]);
    }
}
