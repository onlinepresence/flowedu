<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Models\Result;
use App\Models\TranscriptRequest;
use App\Livewire\Concerns\DispatchesCollegeToasts;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StudentTranscriptPage extends Component
{
    use DispatchesCollegeToasts;

    public string $purpose = '';

    public function requestTranscript(): void
    {
        $student = auth()->user()?->student;
        if ($student === null) {
            abort(403);
        }

        // Eligibility check: only final year or graduated students
        $student->loadMissing('program');
        $programLength = (int) ($student->program?->program_length ?? 4);
        $finalYear = $programLength * 100;
        $isEligible = $student->graduated || (int) $student->current_year >= $finalYear;
        if (! $isEligible) {
            $this->collegeToast(__('Only final year or graduated students can request official transcripts.'), 'error');
            return;
        }

        $this->validate([
            'purpose' => ['nullable', 'string', 'max:255'],
        ]);

        // Check if there's already a pending request
        $pendingExists = TranscriptRequest::query()
            ->where('student_id', $student->id)
            ->where('status', 'pending')
            ->exists();

        if ($pendingExists) {
            $this->collegeToast(__('You already have a pending transcript request.'), 'warning');
            return;
        }

        TranscriptRequest::query()->create([
            'student_id' => $student->id,
            'status' => 'pending',
            'purpose' => trim($this->purpose) !== '' ? trim($this->purpose) : null,
        ]);

        $this->purpose = '';
        $this->dispatch('close-modal', 'request-transcript-modal');
        $this->collegeToast(__('Transcript request submitted successfully.'));
    }

    public function render(): View
    {
        $student = auth()->user()?->student;
        
        $rows = $student
            ? Result::query()
                ->where('student_id', $student->id)
                ->with(['course', 'academicSession'])
                ->orderBy('academic_session_id')
                ->orderBy('course_id')
                ->get()
            : collect();

        $requests = $student
            ? TranscriptRequest::query()
                ->where('student_id', $student->id)
                ->orderByDesc('id')
                ->get()
            : collect();

        if ($student) {
            $student->loadMissing('program');
            $programLength = (int) ($student->program?->program_length ?? 4);
            $finalYear = $programLength * 100;
            $isEligible = $student->graduated || (int) $student->current_year >= $finalYear;
        } else {
            $isEligible = false;
        }

        $settings = \App\Models\Setting::query()
            ->where('category', 'system_preferences')
            ->pluck('setting_value', 'setting_key');
        $redirectEnabled = ($settings['system_preferences.student_grading_redirect'] ?? '0') === '1';
        $externalGradingUrl = (string) ($settings['system_preferences.external_grading_url'] ?? '');

        return view('livewire.student.student-transcript-page', [
            'student' => $student,
            'rows' => $rows,
            'requests' => $requests,
            'isEligible' => $isEligible,
            'redirectEnabled' => $redirectEnabled,
            'externalGradingUrl' => $externalGradingUrl,
        ])->layout('components.layouts.student', [
            'title' => __('Academic Transcript'),
            'headerTitle' => __('Official Transcript'),
            'headerDescription' => __('Request, preview, and track official academic transcript requests.'),
        ]);
    }
}
