<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Finance;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\AcademicSession;
use App\Models\Scholarship;
use App\Models\ScholarshipRecipient;
use App\Models\Program;
use App\Models\Student;
use App\Services\Finance\FeeCalculationService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class AllowancesIndexPage extends Component
{
    use DispatchesCollegeToasts;
    use WithPagination;

    public string $recipientSearch = '';

    public string $recipientStatus = '';

    public string $programFilter = '';

    public string $levelFilter = '';

    // Award Allowance Modal properties
    public bool $showAwardModal = false;

    public string $awardStudentSearch = '';

    public string $award_student_id = '';

    public string $award_amount = '';

    public string $award_date = '';

    // Bulk Award Allowance properties
    public bool $showBulkAwardModal = false;

    public string $bulk_amount = '';

    public string $bulk_date = '';

    public array $selectedIds = [];

    public bool $selectAll = false;

    protected $queryString = [
        'recipientSearch' => ['except' => ''],
        'recipientStatus' => ['except' => ''],
        'programFilter' => ['except' => ''],
        'levelFilter' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->award_date = now()->toDateString();
        $this->bulk_date = now()->toDateString();
    }

    public function updatedRecipientSearch(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedRecipientStatus(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedProgramFilter(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedLevelFilter(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    private function resetSelection(): void
    {
        $this->selectedIds = [];
        $this->selectAll = false;
    }

    public function openAwardModal(): void
    {
        $this->resetAwardForm();
        $this->showAwardModal = true;
        $this->dispatch('open-modal', 'allowance-award-modal');
    }

    public function selectAwardStudent(int $id): void
    {
        $student = Student::with('user')->find($id);
        if ($student) {
            $this->award_student_id = (string) $student->id;
            $this->awardStudentSearch = $student->lastname . ', ' . ($student->othernames ?? '') . ' (' . $student->index_number . ')';
        }
    }

    public function awardScholarship(): void
    {
        $this->validate([
            'award_student_id' => ['required', 'exists:students,id'],
            'award_amount' => ['required', 'numeric', 'min:0.01'],
            'award_date' => ['required', 'date'],
        ]);

        $scheme = $this->getSystemAllowanceScheme();

        ScholarshipRecipient::query()->create([
            'student_id' => (int) $this->award_student_id,
            'scholarship_id' => $scheme->id,
            'amount_awarded' => (float) $this->award_amount,
            'award_date' => $this->award_date,
            'status' => 'approved',
        ]);

        // Sync ledger using central FeeCalculationService (just in case)
        $student = Student::find((int) $this->award_student_id);
        $currentSession = AcademicSession::where('is_current', true)->first();
        if ($student && $currentSession) {
            $service = new FeeCalculationService();
            $service->syncFeePaymentLedger($student, $currentSession);
        }

        $this->showAwardModal = false;
        $this->resetAwardForm();
        $this->dispatch('close-modal', 'allowance-award-modal');
        $this->collegeToast(__('Allowance awarded successfully.'));
        $this->resetPage();
    }

    public function openBulkAwardModal(): void
    {
        $this->bulk_amount = '';
        $this->bulk_date = now()->toDateString();
        $this->showBulkAwardModal = true;
        $this->dispatch('open-modal', 'allowance-bulk-award-modal');
    }

    public function bulkAwardScholarship(): void
    {
        $this->validate([
            'bulk_amount' => ['required', 'numeric', 'min:0.01'],
            'bulk_date' => ['required', 'date'],
        ]);

        $scheme = $this->getSystemAllowanceScheme();

        $activeStudents = Student::query()
            ->where('approved', true)
            ->where('graduated', false)
            ->get();

        $currentSessionId = AcademicSession::activeSessionId();

        $count = 0;
        foreach ($activeStudents as $student) {
            ScholarshipRecipient::query()->create([
                'student_id' => $student->id,
                'scholarship_id' => $scheme->id,
                'amount_awarded' => (float) $this->bulk_amount,
                'award_date' => $this->bulk_date,
                'status' => 'applied',
                'academic_session_id' => $currentSessionId,
            ]);
            $count++;
        }

        $this->showBulkAwardModal = false;
        $this->dispatch('close-modal', 'allowance-bulk-award-modal');
        $this->collegeToast(__(':count student allowances created pending approval.', ['count' => $count]));
        $this->resetPage();
    }

    public function updateApplicationStatus(int $applicationId, string $status): void
    {
        if (! in_array($status, ['approved', 'rejected', 'active'], true)) {
            return;
        }

        $row = ScholarshipRecipient::query()->findOrFail($applicationId);
        $payload = ['status' => $status];
        if ($status === 'approved' && $row->award_date === null) {
            $payload['award_date'] = now()->toDateString();
        }

        $row->forceFill($payload)->save();

        // Sync using central FeeCalculationService
        if ($row->student) {
            $currentSession = AcademicSession::where('is_current', true)->first();
            if ($currentSession) {
                $service = new FeeCalculationService();
                $service->syncFeePaymentLedger($row->student, $currentSession);
            }
        }

        $this->collegeToast(__('Allowance record updated successfully.'));
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedIds = ScholarshipRecipient::query()
                ->whereHas('scholarship', function ($q): void {
                    $q->whereIn('name', ['Student Allowance', 'Monthly Student Allowance Scheme']);
                })
                ->where('status', 'applied')
                ->when($this->recipientSearch !== '', function ($q): void {
                    $q->whereHas('student', function ($inner): void {
                        $inner->where('index_number', 'like', '%'.$this->recipientSearch.'%')
                            ->orWhere('lastname', 'like', '%'.$this->recipientSearch.'%')
                            ->orWhere('firstname', 'like', '%'.$this->recipientSearch.'%');
                    });
                })
                ->when($this->recipientStatus !== '', fn ($q) => $q->where('status', $this->recipientStatus))
                ->when($this->programFilter !== '', function ($q): void {
                    $q->whereHas('student', function ($inner): void {
                        $inner->where('program_id', $this->programFilter);
                    });
                })
                ->when($this->levelFilter !== '', function ($q): void {
                    $q->whereHas('student', function ($inner): void {
                        $inner->where('current_year', $this->levelFilter);
                    });
                })
                ->pluck('id')
                ->map(fn($id) => (string)$id)
                ->toArray();
        } else {
            $this->selectedIds = [];
        }
    }

    public function bulkApprove(): void
    {
        if (empty($this->selectedIds)) {
            return;
        }

        $rows = ScholarshipRecipient::query()
            ->whereIn('id', $this->selectedIds)
            ->where('status', 'applied')
            ->get();

        $count = 0;
        foreach ($rows as $row) {
            $row->forceFill([
                'status' => 'approved',
                'award_date' => $row->award_date ?? now()->toDateString()
            ])->save();

            // Sync using central FeeCalculationService
            if ($row->student) {
                $currentSession = AcademicSession::where('is_current', true)->first();
                if ($currentSession) {
                    $service = new FeeCalculationService();
                    $service->syncFeePaymentLedger($row->student, $currentSession);
                }
            }
            $count++;
        }

        $this->resetSelection();
        $this->collegeToast(__(':count allowances approved successfully.', ['count' => $count]));
    }

    public function bulkReject(): void
    {
        if (empty($this->selectedIds)) {
            return;
        }

        $count = ScholarshipRecipient::query()
            ->whereIn('id', $this->selectedIds)
            ->where('status', 'applied')
            ->update(['status' => 'rejected']);

        $this->resetSelection();
        $this->collegeToast(__(':count allowances rejected.', ['count' => $count]));
    }

    public function render(): View
    {
        // Recipients / Awards of type 'allowance' (matching Student Allowance or Monthly Student Allowance Scheme)
        $applications = ScholarshipRecipient::query()
            ->whereHas('scholarship', function ($q): void {
                $q->whereIn('name', ['Student Allowance', 'Monthly Student Allowance Scheme']);
            })
            ->with(['student.user', 'student.program', 'scholarship'])
            ->when($this->recipientSearch !== '', function ($q): void {
                $q->whereHas('student', function ($inner): void {
                    $inner->where('index_number', 'like', '%'.$this->recipientSearch.'%')
                        ->orWhere('lastname', 'like', '%'.$this->recipientSearch.'%')
                        ->orWhere('firstname', 'like', '%'.$this->recipientSearch.'%');
                });
            })
            ->when($this->recipientStatus !== '', fn ($q) => $q->where('status', $this->recipientStatus))
            ->when($this->programFilter !== '', function ($q): void {
                $q->whereHas('student', function ($inner): void {
                    $inner->where('program_id', $this->programFilter);
                });
            })
            ->when($this->levelFilter !== '', function ($q): void {
                $q->whereHas('student', function ($inner): void {
                    $inner->where('current_year', $this->levelFilter);
                });
            })
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'applicationsPage');

        $searchedStudents = [];
        if ($this->awardStudentSearch !== '' && $this->award_student_id === '') {
            $searchedStudents = Student::query()
                ->with('user')
                ->where('index_number', 'like', '%'.$this->awardStudentSearch.'%')
                ->orWhere('lastname', 'like', '%'.$this->awardStudentSearch.'%')
                ->orWhere('firstname', 'like', '%'.$this->awardStudentSearch.'%')
                ->limit(6)
                ->get();
        }

        $programs = Program::orderBy('name')->get();

        return view('livewire.admin.finance.allowances-index-page', [
            'applications' => $applications,
            'searchedStudents' => $searchedStudents,
            'programs' => $programs,
        ])->layout('components.layouts.admin', [
            'title' => __('Allowances Management'),
            'hideHeader' => true,
        ]);
    }

    private function getSystemAllowanceScheme(): Scholarship
    {
        return Scholarship::query()->firstOrCreate(
            ['name' => 'Student Allowance'],
            [
                'type' => 'grant',
                'amount' => 0.00,
                'duration_semesters' => 1,
                'status' => 'active',
                'description' => 'System record for monthly student allowances paid directly to student accounts.',
            ]
        );
    }

    private function resetAwardForm(): void
    {
        $this->awardStudentSearch = '';
        $this->award_student_id = '';
        $this->award_amount = '';
        $this->award_date = now()->toDateString();
        $this->resetValidation();
    }
}
