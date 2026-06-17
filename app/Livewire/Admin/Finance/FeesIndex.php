<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Finance;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\AcademicSession;
use App\Models\FeeStructure;
use App\Models\FeePayment;
use App\Models\Payment;
use App\Models\Program;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class FeesIndex extends Component
{
    use DispatchesCollegeToasts;
    use WithPagination;

    public string $activeTab = 'payments';

    public string $receiptHeaderTitle = '';

    public string $receiptHeaderSubtitle = '';

    public string $receiptContactInfo = '';

    public string $receiptFooterNote = '';

    public bool $receiptShowSignature = true;

    public bool $receiptShowStamp = true;

    public string $filterSessionId = '';

    public string $search = '';

    public string $filterLevel = '';

    public string $program_id = '';

    public string $level = '';

    public string $session_id = '';

    public string $tuition_fee = '';

    public string $library_fee = '';

    public string $lab_fee = '';

    public string $medical_fee = '';

    public string $sports_fee = '';

    public string $examination_fee = '';

    public string $registration_fee = '';

    public string $ict_fee = '';

    public string $id_card_fee = '';

    public string $facility_maintenance_fee = '';

    public string $utility_fee = '';

    public string $field_trip_fee = '';

    public string $internship_fee = '';

    public string $src_dues = '';

    /** @var array<string, bool> */
    public array $extraFeeEnabled = [];

    public bool $showStructureModal = false;

    public bool $showStructuresTableSection = true;

    public ?int $editingStructureId = null;

    protected $queryString = [
        'activeTab' => ['except' => 'payments'],
        'filterSessionId' => ['except' => ''],
        'search' => ['except' => ''],
        'filterLevel' => ['except' => ''],
    ];

    public function mount(): void
    {
        $latestSession = AcademicSession::query()->orderByDesc('start_date')->value('id');
        if ($latestSession !== null) {
            $this->filterSessionId = (string) $latestSession;
            $this->session_id = (string) $latestSession;
        }

        $this->loadExtraFeeConfiguration();
        $this->loadReceiptSettings();
    }

    public function updatedFilterSessionId(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterLevel(): void
    {
        $this->resetPage();
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function toggleSection(string $section): void
    {
        if ($section === 'structure-form') {
            $this->showStructureModal = ! $this->showStructureModal;
            if (! $this->showStructureModal) {
                $this->cancelEdit();
            }
            return;
        }

        if ($section === 'structures-table') {
            $this->showStructuresTableSection = ! $this->showStructuresTableSection;
        }
    }

    public function openStructureModal(): void
    {
        $this->cancelEdit();
        $currentSessionId = AcademicSession::query()->where('is_current', true)->value('id');
        if ($currentSessionId !== null) {
            $this->session_id = (string) $currentSessionId;
        }
        $this->showStructureModal = true;
        $this->dispatch('open-modal', 'fee-structure-modal');
    }

    public function cancelEdit(): void
    {
        $this->editingStructureId = null;
        $this->program_id = '';
        $this->level = '';
        $this->session_id = '';
        $this->tuition_fee = '';
        foreach (array_keys($this->extraFeeCatalog()) as $key) {
            $this->{$key} = '';
        }
        $this->resetValidation();
    }

    public function editStructure(int $id): void
    {
        $structure = FeeStructure::query()->findOrFail($id);
        $this->editingStructureId = $structure->id;
        $this->program_id = (string) $structure->program_id;
        $this->level = (string) $structure->level;
        $this->session_id = (string) $structure->session_id;
        $this->tuition_fee = (string) $structure->tuition_fee;

        foreach (array_keys($this->extraFeeCatalog()) as $key) {
            $this->{$key} = (string) ($structure->{$key} ?? '0.00');
        }

        $this->showStructureModal = true;
        $this->dispatch('open-modal', 'fee-structure-modal');
    }

    public function deleteStructure(int $id): void
    {
        $hasPayments = Payment::query()->where('fee_structure_id', $id)->exists();
        if ($hasPayments) {
            $this->collegeToast(__('Cannot delete fee structure because payments have already been recorded against it.'), 'error');
            return;
        }

        FeeStructure::query()->whereKey($id)->delete();
        $this->collegeToast(__('Fee structure deleted successfully.'));
    }

    public function createStructureFromProgram(): void
    {
        $rules = [
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'level' => ['required', 'integer', 'in:100,200,300,400'],
            'session_id' => ['required', 'integer', 'exists:academic_sessions,id'],
            'tuition_fee' => ['required', 'numeric', 'min:0'],
        ];

        foreach (array_keys($this->extraFeeCatalog()) as $key) {
            $rules[$key] = ['nullable', 'numeric', 'min:0'];
        }

        foreach (array_keys($this->extraFeeCatalog()) as $key) {
            if (($this->extraFeeEnabled[$key] ?? false) !== true) {
                $rules[$key] = ['nullable'];
            }
        }

        $this->validate($rules);

        $totals = [
            'tuition_fee' => (float) $this->tuition_fee,
        ];
        foreach (array_keys($this->extraFeeCatalog()) as $key) {
            $totals[$key] = $this->amountFromField($key);
        }

        $totalAmount = array_sum($totals);

        if ($this->editingStructureId !== null) {
            $structure = FeeStructure::query()->findOrFail($this->editingStructureId);
            $structure->update([
                'program_id' => (int) $this->program_id,
                'level' => (int) $this->level,
                'session_id' => (int) $this->session_id,
                ...$totals,
                'total_amount' => $totalAmount,
            ]);
            $this->editingStructureId = null;
            $this->collegeToast(__('Fee structure updated successfully.'));
        } else {
            FeeStructure::query()->updateOrCreate(
                [
                    'program_id' => (int) $this->program_id,
                    'level' => (int) $this->level,
                    'session_id' => (int) $this->session_id,
                ],
                [
                    ...$totals,
                    'total_amount' => $totalAmount,
                    'created_by' => auth()->id(),
                ]
            );
            $this->collegeToast(__('Fee structure created successfully.'));
        }

        $this->filterSessionId = $this->session_id;
        $this->showStructureModal = false;
        $this->dispatch('close-modal', 'fee-structure-modal');
        $this->cancelEdit();
        $this->resetPage();
    }

    public function fillFromProgramCost(): void
    {
        $this->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
        ]);

        $program = Program::query()->findOrFail((int) $this->program_id);
        $base = (float) ($program->cost ?? 0);
        if ($base <= 0) {
            $this->addError('program_id', __('Selected program has no base cost set.'));

            return;
        }

        // College-friendly default split; values remain editable before save.
        $this->tuition_fee = number_format($base * 0.70, 2, '.', '');
        $this->library_fee = number_format($base * 0.05, 2, '.', '');
        $this->lab_fee = number_format($base * 0.08, 2, '.', '');
        $this->medical_fee = number_format($base * 0.05, 2, '.', '');
        $this->sports_fee = number_format($base * 0.04, 2, '.', '');
        $this->examination_fee = number_format($base * 0.08, 2, '.', '');
        $this->registration_fee = number_format($base * 0.02, 2, '.', '');
        $this->ict_fee = number_format($base * 0.02, 2, '.', '');
        $this->id_card_fee = number_format($base * 0.005, 2, '.', '');
        $this->facility_maintenance_fee = number_format($base * 0.005, 2, '.', '');
        $this->utility_fee = number_format($base * 0.005, 2, '.', '');
        $this->field_trip_fee = number_format($base * 0.005, 2, '.', '');
        $this->internship_fee = number_format($base * 0.005, 2, '.', '');
        $this->src_dues = number_format($base * 0.005, 2, '.', '');
    }

    public function saveExtraFeeConfiguration(): void
    {
        $catalogKeys = array_keys($this->extraFeeCatalog());
        $selected = [];
        foreach ($catalogKeys as $key) {
            if (($this->extraFeeEnabled[$key] ?? false) === true) {
                $selected[] = $key;
            }
        }

        Setting::query()->updateOrCreate(
            ['setting_key' => 'finance.extra_fee_keys'],
            [
                'category' => 'finance',
                'setting_value' => (string) json_encode($selected),
                'data_type' => 'json',
                'description' => 'Enabled extra fee components',
                'updated_by' => auth()->id(),
            ]
        );

        $this->collegeToast(__('Fee component configuration saved.'));
    }

    public function approveRequest(int $requestId): void
    {
        $request = \App\Models\FeeBreakdownRequest::findOrFail($requestId);
        $request->update([
            'status' => 'approved',
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        $request->student->user->notify(new \App\Notifications\CollegeNotification(
            __('Fee Breakdown Approved'),
            __('Your request for a detailed breakdown of Level :level fees for the :session session has been approved.', [
                'level' => $request->feeStructure->level,
                'session' => $request->feeStructure->session->name,
            ]),
            route('student.fees.index')
        ));

        $this->collegeToast(__('Request approved successfully.'));
    }

    public function rejectRequest(int $requestId): void
    {
        $request = \App\Models\FeeBreakdownRequest::findOrFail($requestId);
        $request->update([
            'status' => 'rejected',
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        $request->student->user->notify(new \App\Notifications\CollegeNotification(
            __('Fee Breakdown Request Declined'),
            __('Your request for a detailed breakdown of Level :level fees for the :session session was declined.', [
                'level' => $request->feeStructure->level,
                'session' => $request->feeStructure->session->name,
            ]),
            route('student.fees.index')
        ));

        $this->collegeToast(__('Request declined successfully.'));
    }

    public function render(): View
    {
        $structures = FeeStructure::query()
            ->with(['program', 'session'])
            ->when($this->filterSessionId !== '', fn ($q) => $q->where('session_id', (int) $this->filterSessionId))
            ->orderByDesc('session_id')
            ->orderBy('program_id')
            ->orderBy('level')
            ->paginate(20, ['*'], 'structuresPage');

        // Query actual Payment transactions instead of FeePayment ledger
        $payments = Payment::query()
            ->with(['student.user', 'feeStructure.program', 'feeStructure.session'])
            ->when($this->search !== '', function ($q): void {
                $q->whereHas('student', function ($s): void {
                    $s->where('index_number', 'like', '%'.$this->search.'%')
                        ->orWhere('lastname', 'like', '%'.$this->search.'%')
                        ->orWhere('firstname', 'like', '%'.$this->search.'%');
                });
            })
            ->latest()
            ->paginate(20, ['*'], 'paymentsPage');

        // Stats calculations
        $totalPaid = (float) Payment::query()
            ->when($this->search !== '', function ($q): void {
                $q->whereHas('student', function ($s): void {
                    $s->where('index_number', 'like', '%'.$this->search.'%')
                        ->orWhere('lastname', 'like', '%'.$this->search.'%')
                        ->orWhere('firstname', 'like', '%'.$this->search.'%');
                });
            })
            ->sum('amount_paid');

        $totalBalance = (float) FeePayment::query()
            ->when($this->search !== '', function ($q): void {
                $q->where(function ($inner): void {
                    $inner->where('lastname', 'like', '%'.$this->search.'%')
                        ->orWhere('othernames', 'like', '%'.$this->search.'%')
                        ->orWhereHas('student', function ($s): void {
                            $s->where('index_number', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->sum('balance');

        $requests = \App\Models\FeeBreakdownRequest::query()
            ->with(['student.user', 'feeStructure.program', 'feeStructure.session'])
            ->latest()
            ->paginate(20, ['*'], 'requestsPage');

        $pendingRequestsCount = \App\Models\FeeBreakdownRequest::where('status', 'pending')->count();

        return view('livewire.admin.finance.fees-index', [
            'programs' => Program::query()->orderBy('name')->get(['id', 'name', 'cost']),
            'sessions' => AcademicSession::query()->orderByDesc('start_date')->get(['id', 'name', 'start_date', 'is_current']),
            'structures' => $structures,
            'payments' => $payments,
            'totalPaid' => $totalPaid,
            'totalBalance' => $totalBalance,
            'extraFeeCatalog' => $this->extraFeeCatalog(),
            'requests' => $requests,
            'pendingRequestsCount' => $pendingRequestsCount,
        ])->layout('components.layouts.admin', [
            'title' => __('Fees Management'),
            'headerDescription' => __('Configure program fee structures, fee components, and view recent transactions.')
        ]);
    }

    private function loadExtraFeeConfiguration(): void
    {
        $catalog = $this->extraFeeCatalog();
        $raw = Setting::query()
            ->where('setting_key', 'finance.extra_fee_keys')
            ->value('setting_value');

        $selected = is_string($raw) ? json_decode($raw, true) : null;
        $selectedKeys = is_array($selected) && $selected !== [] ? $selected : array_keys($catalog);

        foreach (array_keys($catalog) as $key) {
            $this->extraFeeEnabled[$key] = in_array($key, $selectedKeys, true);
        }
    }

    /**
     * @return array<string, string>
     */
    private function extraFeeCatalog(): array
    {
        return [
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

    private function loadReceiptSettings(): void
    {
        $raw = Setting::query()
            ->where('setting_key', 'finance.receipt_settings')
            ->value('setting_value');

        $settings = is_string($raw) ? json_decode($raw, true) : null;

        $this->receiptHeaderTitle = (string) ($settings['header_title'] ?? __('College of Education'));
        $this->receiptHeaderSubtitle = (string) ($settings['header_subtitle'] ?? __('Official Tuition & Fees Payment Receipt'));
        $this->receiptContactInfo = (string) ($settings['contact_info'] ?? __('+233 24 000 0000 | finance@college.edu.gh'));
        $this->receiptFooterNote = (string) ($settings['footer_note'] ?? __('Thank you for your payment. This is an official computer-generated receipt.'));
        $this->receiptShowSignature = (bool) ($settings['show_signature'] ?? true);
        $this->receiptShowStamp = (bool) ($settings['show_stamp'] ?? true);
    }

    public function saveReceiptSettings(): void
    {
        $this->validate([
            'receiptHeaderTitle' => ['required', 'string', 'max:100'],
            'receiptHeaderSubtitle' => ['required', 'string', 'max:150'],
            'receiptContactInfo' => ['nullable', 'string', 'max:255'],
            'receiptFooterNote' => ['nullable', 'string', 'max:500'],
            'receiptShowSignature' => ['boolean'],
            'receiptShowStamp' => ['boolean'],
        ]);

        $payload = [
            'header_title' => trim($this->receiptHeaderTitle),
            'header_subtitle' => trim($this->receiptHeaderSubtitle),
            'contact_info' => trim($this->receiptContactInfo),
            'footer_note' => trim($this->receiptFooterNote),
            'show_signature' => $this->receiptShowSignature,
            'show_stamp' => $this->receiptShowStamp,
        ];

        Setting::query()->updateOrCreate(
            ['setting_key' => 'finance.receipt_settings'],
            [
                'category' => 'finance',
                'setting_value' => json_encode($payload),
                'data_type' => 'json',
                'description' => 'Custom receipt layout settings',
                'updated_by' => auth()->id(),
            ]
        );

        $this->collegeToast(__('Receipt settings saved successfully.'));
    }

    private function amountFromField(string $field): float
    {
        /** @var mixed $value */
        $value = $this->{$field} ?? '';

        return (float) ($value === '' ? 0 : $value);
    }
}
