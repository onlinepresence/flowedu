<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\Scholarship;
use App\Models\ScholarshipRecipient;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StudentScholarshipsPage extends Component
{
    use DispatchesCollegeToasts;

    public ?int $viewingScholarshipId = null;
    public bool $showDetailsModal = false;
    public ?int $cancellingApplicationId = null;

    public function viewDetails(int $id): void
    {
        $this->viewingScholarshipId = $id;
        $this->showDetailsModal = true;
    }

    public function closeDetailsModal(): void
    {
        $this->showDetailsModal = false;
        $this->viewingScholarshipId = null;
    }

    public function confirmCancel(int $id): void
    {
        $this->cancellingApplicationId = $id;
        $this->dispatch('open-modal', 'confirm-cancel-scholarship');
    }

    public function cancel(): void
    {
        if ($this->cancellingApplicationId === null) {
            return;
        }

        $user = auth()->user();
        $student = $user?->student;
        if ($student === null) {
            abort(403);
        }

        $application = ScholarshipRecipient::query()
            ->where('id', $this->cancellingApplicationId)
            ->where('student_id', $student->id)
            ->where('status', 'applied')
            ->first();

        if ($application === null) {
            $this->collegeToast(__('This application cannot be cancelled or has already been reviewed.'), 'danger');
            return;
        }

        $application->delete();

        // Sync payment ledger via central FeeCalculationService
        $currentSession = \App\Models\AcademicSession::where('is_current', true)->first();
        if ($currentSession) {
            $service = new \App\Services\Finance\FeeCalculationService();
            $service->syncFeePaymentLedger($student, $currentSession);
        }

        $this->cancellingApplicationId = null;
        $this->collegeToast(__('Application cancelled successfully.'));
    }

    public function apply(int $scholarshipId): void
    {
        $user = auth()->user();
        $student = $user?->student;
        if ($student === null) {
            abort(403);
        }

        $scholarship = Scholarship::query()
            ->whereKey($scholarshipId)
            ->where('status', 'active')
            ->first();
        if ($scholarship === null) {
            $this->collegeToast(__('This scholarship is not available for application.'), 'danger');
            return;
        }

        $exists = ScholarshipRecipient::query()
            ->where('scholarship_id', $scholarship->id)
            ->where('student_id', $student->id)
            ->exists();

        if ($exists) {
            $this->collegeToast(__('You have already applied for this scholarship.'), 'warning');
            return;
        }

        ScholarshipRecipient::query()->create([
            'scholarship_id' => $scholarship->id,
            'student_id' => $student->id,
            'amount_awarded' => 0,
            'award_date' => null,
            'status' => 'applied',
        ]);

        $this->closeDetailsModal();
        $this->collegeToast(__('Application submitted successfully.'));
    }

    public function render(): View
    {
        $student = auth()->user()?->student;
        $appliedScholarshipIds = $student
            ? ScholarshipRecipient::query()
                ->where('student_id', $student->id)
                ->pluck('scholarship_id')
                ->all()
                : [];

        $available = Scholarship::query()
            ->where('status', 'active')
            ->where('name', '!=', 'Student Allowance')
            ->where('name', '!=', 'Monthly Student Allowance Scheme')
            ->orderBy('name')
            ->get();

        $myApplications = $student
            ? ScholarshipRecipient::query()
                ->where('student_id', $student->id)
                ->whereHas('scholarship', function ($q) {
                    $q->where('name', '!=', 'Student Allowance')
                      ->where('name', '!=', 'Monthly Student Allowance Scheme');
                })
                ->with('scholarship')
                ->latest('id')
                ->get()
            : collect();

        $viewingScholarship = null;
        if ($this->viewingScholarshipId !== null) {
            $viewingScholarship = Scholarship::find($this->viewingScholarshipId);
        }

        return view('livewire.student.student-scholarships-page', [
            'available' => $available,
            'myApplications' => $myApplications,
            'appliedScholarshipIds' => $appliedScholarshipIds,
            'viewingScholarship' => $viewingScholarship,
        ])->layout('components.layouts.student', ['title' => __('Scholarships'), 'hideHeader' => true]);
    }
}
