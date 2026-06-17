<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\MedicalHistory;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StudentMedicalPage extends Component
{
    use DispatchesCollegeToasts;

    public string $activeTab = 'profile';

    public string $correctionType = '';

    public string $correctionDescription = '';

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['profile', 'immunizations', 'contacts', 'history'])) {
            $this->activeTab = $tab;
        }
    }

    public function submitCorrectionRequest(): void
    {
        $this->validate([
            'correctionType' => ['required', 'string', 'in:allergy,condition,medication,insurance,other'],
            'correctionDescription' => ['required', 'string', 'min:5', 'max:2000'],
        ], [], [
            'correctionType' => __('Request Type'),
            'correctionDescription' => __('Correction / Update Details'),
        ]);

        // Simulating submission to health clinic system
        $this->reset(['correctionType', 'correctionDescription']);
        $this->dispatch('close-modal', 'correction-request-modal');

        $this->collegeToast(__('Your health profile update request has been successfully submitted to the campus clinic.'));
    }

    public function render(): View
    {
        $student = auth()->user()?->student;
        if ($student === null) {
            abort(403);
        }

        $records = MedicalHistory::query()
            ->where('student_id', $student->id)
            ->with('academicSession')
            ->orderByDesc('id')
            ->get();

        $latestRecord = $records->first();

        // Get single registered guardian/parent as emergency contact
        $guardian = $student->parentGuardians()->first();

        return view('livewire.student.student-medical-page', [
            'student' => $student,
            'record' => $latestRecord,
            'records' => $records,
            'guardian' => $guardian,
        ])->layout('components.layouts.student', ['title' => __('Medical Info'), 'hideHeader' => true]);
    }
}
