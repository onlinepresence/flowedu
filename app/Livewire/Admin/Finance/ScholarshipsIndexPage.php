<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Finance;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\AcademicSession;
use App\Models\Scholarship;
use App\Models\ScholarshipRecipient;
use App\Models\Student;
use App\Services\Finance\FeeCalculationService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ScholarshipsIndexPage extends Component
{
    use DispatchesCollegeToasts;
    use WithPagination;

    public string $activeTab = 'recipients';

    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public ?int $editingScholarshipId = null;

    // Available Schemes Properties
    public string $name = '';

    public string $type = 'scholarship';

    public string $amount = '';

    public int $duration_semesters = 1;

    public string $expiry_date = '';

    public string $coverage_type = 'full';

    /** @var array<string, bool> */
    public array $coverage_components = [];

    public string $description = '';

    public string $status = 'active';

    // Filters
    public string $filterSchemeName = '';

    public string $filterSchemeType = '';

    public string $recipientSearch = '';

    public string $recipientSchemeId = '';

    public string $recipientStatus = '';

    // Award Scholarship Modal properties
    public bool $showAwardModal = false;

    public string $awardStudentSearch = '';

    public string $award_student_id = '';

    public string $award_scholarship_id = '';

    public string $award_amount = '';

    public string $award_date = '';

    public array $selectedIds = [];

    public bool $selectAll = false;

    protected $queryString = [
        'activeTab' => ['except' => 'recipients'],
        'filterSchemeName' => ['except' => ''],
        'filterSchemeType' => ['except' => ''],
        'recipientSearch' => ['except' => ''],
        'recipientSchemeId' => ['except' => ''],
        'recipientStatus' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->award_date = now()->toDateString();
        $this->resetCoverageComponents();
    }

    public function updatedFilterSchemeName(): void
    {
        $this->resetPage();
    }

    public function updatedFilterSchemeType(): void
    {
        $this->resetPage();
    }

    public function updatedRecipientSearch(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedRecipientSchemeId(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedRecipientStatus(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    private function resetSelection(): void
    {
        $this->selectedIds = [];
        $this->selectAll = false;
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showCreateModal = true;
        $this->dispatch('open-modal', 'sc-create');
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
        $this->dispatch('close-modal', 'sc-create');
    }

    public function saveCreate(): void
    {
        $rules = $this->rules();
        // If coverage is partial, validate selected components
        if ($this->coverage_type === 'partial') {
            $rules['coverage_components'] = ['required', 'array', 'min:1'];
        }

        $validated = $this->validate($rules);

        $selectedComponents = [];
        if ($this->coverage_type === 'partial') {
            foreach ($this->coverage_components as $comp => $enabled) {
                if ($enabled === true) {
                    $selectedComponents[] = $comp;
                }
            }
        }

        Scholarship::query()->create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'amount' => (float) $validated['amount'],
            'duration_semesters' => (int) $this->duration_semesters,
            'expiry_date' => $this->expiry_date !== '' ? $this->expiry_date : null,
            'coverage_type' => $this->coverage_type,
            'coverage_components' => $this->coverage_type === 'partial' ? $selectedComponents : null,
            'description' => $validated['description'] !== '' ? $validated['description'] : null,
            'status' => 'active', // default active per user request
            'created_by' => auth()->id(),
        ]);

        $this->closeCreateModal();
        $this->collegeToast(__('Scholarship scheme created successfully.'));
        $this->resetPage();
    }

    public function openEditModal(int $id): void
    {
        $row = Scholarship::query()->findOrFail($id);
        $this->editingScholarshipId = $row->id;
        $this->name = $row->name;
        $this->type = $row->type;
        $this->amount = (string) $row->amount;
        $this->duration_semesters = (int) ($row->duration_semesters ?? 1);
        $this->expiry_date = $row->expiry_date ? $row->expiry_date->format('Y-m-d') : '';
        $this->coverage_type = $row->coverage_type ?? 'full';
        
        $this->resetCoverageComponents();
        if ($this->coverage_type === 'partial' && is_array($row->coverage_components)) {
            foreach ($row->coverage_components as $comp) {
                $this->coverage_components[$comp] = true;
            }
        }

        $this->description = (string) ($row->description ?? '');
        $this->status = (string) $row->status;
        $this->showEditModal = true;
        $this->dispatch('open-modal', 'sc-edit');
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingScholarshipId = null;
        $this->resetForm();
        $this->dispatch('close-modal', 'sc-edit');
    }

    public function saveEdit(): void
    {
        if ($this->editingScholarshipId === null) {
            return;
        }

        $rules = $this->rules();
        $rules['status'] = ['required', 'string', 'in:active,inactive,closed'];
        if ($this->coverage_type === 'partial') {
            $rules['coverage_components'] = ['required', 'array', 'min:1'];
        }

        $validated = $this->validate($rules);

        $selectedComponents = [];
        if ($this->coverage_type === 'partial') {
            foreach ($this->coverage_components as $comp => $enabled) {
                if ($enabled === true) {
                    $selectedComponents[] = $comp;
                }
            }
        }

        Scholarship::query()->whereKey($this->editingScholarshipId)->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'amount' => (float) $validated['amount'],
            'duration_semesters' => (int) $this->duration_semesters,
            'expiry_date' => $this->expiry_date !== '' ? $this->expiry_date : null,
            'coverage_type' => $this->coverage_type,
            'coverage_components' => $this->coverage_type === 'partial' ? $selectedComponents : null,
            'description' => $validated['description'] !== '' ? $validated['description'] : null,
            'status' => $validated['status'],
        ]);

        // Sync all recipients of this scholarship in current session
        $recipients = ScholarshipRecipient::where('scholarship_id', $this->editingScholarshipId)->get();
        $currentSession = AcademicSession::where('is_current', true)->first();
        if ($currentSession) {
            $service = new FeeCalculationService();
            foreach ($recipients as $recipient) {
                if ($recipient->student) {
                    $service->syncFeePaymentLedger($recipient->student, $currentSession);
                }
            }
        }

        $this->closeEditModal();
        $this->collegeToast(__('Scholarship scheme updated successfully.'));
    }

    public function openAwardModal(): void
    {
        $this->resetAwardForm();
        $this->showAwardModal = true;
        $this->dispatch('open-modal', 'sc-award-modal');
    }

    public function selectAwardStudent(int $id): void
    {
        $student = Student::with('user')->find($id);
        if ($student) {
            $this->award_student_id = (string) $student->id;
            $this->awardStudentSearch = $student->lastname . ', ' . ($student->othernames ?? '') . ' (' . $student->index_number . ')';
        }
    }

    public function updatedAwardScholarshipId(string $id): void
    {
        if ($id !== '') {
            $scheme = Scholarship::find((int) $id);
            if ($scheme) {
                $this->award_amount = (string) $scheme->amount;
            }
        } else {
            $this->award_amount = '';
        }
    }

    public function awardScholarship(): void
    {
        $this->validate([
            'award_student_id' => ['required', 'exists:students,id'],
            'award_scholarship_id' => ['required', 'exists:scholarships,id'],
            'award_amount' => ['required', 'numeric', 'min:0.01'],
            'award_date' => ['required', 'date'],
        ]);

        $recipient = ScholarshipRecipient::query()->create([
            'student_id' => (int) $this->award_student_id,
            'scholarship_id' => (int) $this->award_scholarship_id,
            'amount_awarded' => (float) $this->award_amount,
            'award_date' => $this->award_date,
            'status' => 'approved',
        ]);

        // Sync ledger using central FeeCalculationService
        $student = Student::find((int) $this->award_student_id);
        $currentSession = AcademicSession::where('is_current', true)->first();
        if ($student && $currentSession) {
            $service = new FeeCalculationService();
            $service->syncFeePaymentLedger($student, $currentSession);
        }

        $this->showAwardModal = false;
        $this->resetAwardForm();
        $this->dispatch('close-modal', 'sc-award-modal');
        $this->collegeToast(__('Scholarship awarded successfully.'));
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
        if ($status === 'approved' && (float) $row->amount_awarded <= 0) {
            $payload['amount_awarded'] = (float) ($row->scholarship?->amount ?? 0);
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

        $this->collegeToast(__('Application updated successfully.'));
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedIds = ScholarshipRecipient::query()
                ->whereHas('scholarship', function ($q): void {
                    $q->whereIn('type', ['scholarship', 'grant'])
                      ->where('name', 'not like', '%allowance%');
                })
                ->where('status', 'applied')
                ->when($this->recipientSearch !== '', function ($q): void {
                    $q->whereHas('student', function ($inner): void {
                        $inner->where('index_number', 'like', '%'.$this->recipientSearch.'%')
                            ->orWhere('lastname', 'like', '%'.$this->recipientSearch.'%')
                            ->orWhere('firstname', 'like', '%'.$this->recipientSearch.'%');
                    });
                })
                ->when($this->recipientSchemeId !== '', fn ($q) => $q->where('scholarship_id', (int) $this->recipientSchemeId))
                ->when($this->recipientStatus !== '', fn ($q) => $q->where('status', $this->recipientStatus))
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
                'award_date' => $row->award_date ?? now()->toDateString(),
                'amount_awarded' => (float) $row->amount_awarded <= 0 ? (float) ($row->scholarship?->amount ?? 0) : (float) $row->amount_awarded,
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
        $this->collegeToast(__(':count applications approved successfully.', ['count' => $count]));
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
        $this->collegeToast(__(':count applications rejected.', ['count' => $count]));
    }

    public function render(): View
    {
        // Available Schemes Query (Scholarship/Grant only)
        $rows = Scholarship::query()
            ->whereIn('type', ['scholarship', 'grant'])
            ->where('name', 'not like', '%allowance%')
            ->withCount(['recipients' => function ($q): void {
                $q->whereIn('status', ['approved', 'active']);
            }])
            ->withSum(['recipients as total_awarded' => function ($q): void {
                $q->whereIn('status', ['approved', 'active']);
            }], 'amount_awarded')
            ->when($this->filterSchemeName !== '', fn ($q) => $q->where('name', 'like', '%'.$this->filterSchemeName.'%'))
            ->when($this->filterSchemeType !== '', fn ($q) => $q->where('type', $this->filterSchemeType))
            ->orderBy('name')
            ->paginate(20, ['*'], 'schemesPage');

        // Recipients / Awards Query (Scholarship/Grant only)
        $applications = ScholarshipRecipient::query()
            ->whereHas('scholarship', function ($q): void {
                $q->whereIn('type', ['scholarship', 'grant'])
                  ->where('name', 'not like', '%allowance%');
            })
            ->with(['student.user', 'student.program', 'scholarship'])
            ->when($this->recipientSearch !== '', function ($q): void {
                $q->whereHas('student', function ($inner): void {
                    $inner->where('index_number', 'like', '%'.$this->recipientSearch.'%')
                        ->orWhere('lastname', 'like', '%'.$this->recipientSearch.'%')
                        ->orWhere('firstname', 'like', '%'.$this->recipientSearch.'%');
                });
            })
            ->when($this->recipientSchemeId !== '', fn ($q) => $q->where('scholarship_id', (int) $this->recipientSchemeId))
            ->when($this->recipientStatus !== '', fn ($q) => $q->where('status', $this->recipientStatus))
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'applicationsPage');

        $activeSchemes = Scholarship::whereIn('type', ['scholarship', 'grant'])
            ->where('name', 'not like', '%allowance%')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'amount']);

        // Fetch searched students for modal
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

        return view('livewire.admin.finance.scholarships-index-page', [
            'rows' => $rows,
            'applications' => $applications,
            'activeSchemes' => $activeSchemes,
            'searchedStudents' => $searchedStudents,
            'extraFeeCatalog' => $this->extraFeeCatalog(),
        ])->layout('components.layouts.admin', [
            'title' => __('Scholarships'),
            'hideHeader' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:scholarship,grant'],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->type = 'scholarship';
        $this->amount = '';
        $this->duration_semesters = 1;
        $this->expiry_date = '';
        $this->coverage_type = 'full';
        $this->resetCoverageComponents();
        $this->description = '';
        $this->status = 'active';
        $this->resetValidation();
    }

    private function resetCoverageComponents(): void
    {
        $this->coverage_components = [];
        foreach (array_keys($this->extraFeeCatalog()) as $key) {
            $this->coverage_components[$key] = false;
        }
    }

    private function resetAwardForm(): void
    {
        $this->awardStudentSearch = '';
        $this->award_student_id = '';
        $this->award_scholarship_id = '';
        $this->award_amount = '';
        $this->award_date = now()->toDateString();
        $this->resetValidation();
    }

    /**
     * @return array<string, string>
     */
    private function extraFeeCatalog(): array
    {
        return [
            'tuition_fee' => __('Tuition fee'),
            'hostel_fee' => __('Hostel / hall fee'),
            'library_fee' => __('Library fee'),
            'lab_fee' => __('Lab fee'),
            'medical_fee' => __('Medical fee'),
            'sports_fee' => __('Sports fee'),
            'examination_fee' => __('Examination fee'),
            'registration_fee' => __('Registration fee'),
            'ict_fee' => __('ICT fee'),
            'id_card_fee' => __('ID card fee'),
            'facility_maintenance_fee' => __('Facility maintenance fee'),
            'utility_fee' => __('Utility fee'),
            'field_trip_fee' => __('Field trip / practicum fee'),
            'internship_fee' => __('Internship / attachment fee'),
            'src_dues' => __('SRC / student union dues'),
        ];
    }
}
