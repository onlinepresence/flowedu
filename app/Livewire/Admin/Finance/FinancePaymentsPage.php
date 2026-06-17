<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Finance;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\AcademicSession;
use App\Models\FeeStructure;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Setting;
use App\Services\Finance\FeeCalculationService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class FinancePaymentsPage extends Component
{
    use DispatchesCollegeToasts;
    use WithPagination;

    public string $filterSessionId = '';

    public string $searchQuery = '';

    public string $filterMethod = '';

    // Record Payment Modal Properties
    public bool $showRecordModal = false;

    public string $searchStudent = '';

    public string $student_id = '';

    public string $fee_structure_id = '';

    public string $amount_paid = '';

    public string $payment_method = 'Cash';

    public string $payment_date = '';

    public string $reference_number = '';

    // Receipt Modal Property
    public ?int $receiptPaymentId = null;

    public array $studentDebtSummary = [];

    protected $queryString = [
        'filterSessionId' => ['except' => ''],
        'searchQuery' => ['except' => ''],
        'filterMethod' => ['except' => ''],
    ];

    public function mount(): void
    {
        $latestSession = AcademicSession::query()->orderByDesc('start_date')->value('id');
        if ($latestSession !== null) {
            $this->filterSessionId = (string) $latestSession;
        }
        $this->payment_date = now()->toDateString();
    }

    public function updatedFilterSessionId(): void
    {
        $this->resetPage();
    }

    public function updatedSearchQuery(): void
    {
        $this->resetPage();
    }

    public function updatedFilterMethod(): void
    {
        $this->resetPage();
    }

    public function openRecordModal(): void
    {
        $this->resetRecordForm();
        $this->showRecordModal = true;
        $this->dispatch('open-modal', 'record-payment-modal');
    }

    public function selectStudent(int $id): void
    {
        $student = Student::query()->find($id);
        if ($student) {
            $this->student_id = (string) $student->id;
            $this->searchStudent = $student->lastname . ', ' . ($student->othernames ?? '') . ' (' . $student->index_number . ')';
            
            // Auto-select fee structure
            $currentSession = AcademicSession::where('is_current', true)->first();
            if ($currentSession) {
                $structure = FeeStructure::where('program_id', $student->program_id)
                    ->where('level', (int) $student->current_year)
                    ->where('session_id', $currentSession->id)
                    ->first();
                if ($structure) {
                    $this->fee_structure_id = (string) $structure->id;
                }
            }

            // Calculate student debt summary across sessions
            $this->studentDebtSummary = [];
            $sessions = AcademicSession::query()->orderBy('start_date')->get();
            $service = new FeeCalculationService();
            foreach ($sessions as $as) {
                $structures = FeeStructure::query()
                    ->where('program_id', $student->program_id)
                    ->where('session_id', $as->id)
                    ->get();
                
                foreach ($structures as $fs) {
                    $originalYear = $student->current_year;
                    $student->current_year = (string) $fs->level;
                    $calcs = $service->calculateStudentFees($student, $as);
                    $student->current_year = $originalYear;

                    if ($calcs['balance'] > 0) {
                        $this->studentDebtSummary[] = [
                            'session' => $as->name,
                            'level' => $fs->level,
                            'balance' => $calcs['balance'],
                        ];
                    }
                }
            }
        }
    }

    public function recordPayment(): void
    {
        $this->validate([
            'student_id' => ['required', 'exists:students,id'],
            'fee_structure_id' => ['required', 'exists:fee_structures,id'],
            'amount_paid' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'in:Cash,Bank Draft,Mobile Money,Bank Transfer,Check'],
            'payment_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:128'],
        ]);

        $payment = Payment::query()->create([
            'student_id' => (int) $this->student_id,
            'fee_structure_id' => (int) $this->fee_structure_id,
            'amount_paid' => (float) $this->amount_paid,
            'payment_method' => $this->payment_method,
            'payment_date' => $this->payment_date,
            'reference_number' => $this->reference_number !== '' ? $this->reference_number : null,
            'status' => 'completed',
            'received_by' => auth()->id(),
        ]);

        // Sync using central FeeCalculationService
        $student = Student::query()->find((int) $this->student_id);
        $structure = FeeStructure::query()->find((int) $this->fee_structure_id);
        if ($student && $structure) {
            $session = AcademicSession::query()->find($structure->session_id);
            if ($session) {
                $service = new FeeCalculationService();
                $service->syncFeePaymentLedger($student, $session);
            }
        }

        $this->showRecordModal = false;
        $this->resetRecordForm();
        $this->dispatch('close-modal', 'record-payment-modal');
        $this->collegeToast(__('Payment recorded successfully.'));
        $this->resetPage();
    }

    public function openReceipt(int $id): void
    {
        $this->receiptPaymentId = $id;
        $this->dispatch('open-modal', 'print-receipt-modal');
    }

    public function closeReceipt(): void
    {
        $this->receiptPaymentId = null;
        $this->dispatch('close-modal', 'print-receipt-modal');
    }

    public function render(): View
    {
        $sessions = AcademicSession::query()->orderByDesc('start_date')->get(['id', 'name']);

        $rows = Payment::query()
            ->with(['student.user', 'feeStructure.program', 'receiver'])
            ->when($this->filterSessionId !== '', function ($q): void {
                $q->whereHas('feeStructure', function ($inner): void {
                    $inner->where('session_id', (int) $this->filterSessionId);
                });
            })
            ->when($this->filterMethod !== '', fn ($q) => $q->where('payment_method', $this->filterMethod))
            ->when($this->searchQuery !== '', function ($q): void {
                $q->whereHas('student', function ($inner): void {
                    $inner->where('index_number', 'like', '%'.$this->searchQuery.'%')
                        ->orWhere('lastname', 'like', '%'.$this->searchQuery.'%')
                        ->orWhere('firstname', 'like', '%'.$this->searchQuery.'%');
                });
            })
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate(20);

        // Fetch searched students for modal
        $searchedStudents = [];
        if ($this->searchStudent !== '' && $this->student_id === '') {
            $searchedStudents = Student::query()
                ->with('user')
                ->where('index_number', 'like', '%'.$this->searchStudent.'%')
                ->orWhere('lastname', 'like', '%'.$this->searchStudent.'%')
                ->orWhere('firstname', 'like', '%'.$this->searchStudent.'%')
                ->limit(6)
                ->get();
        }

        // Fetch available fee structures for manual form selection
        $feeStructures = [];
        if ($this->student_id !== '') {
            $student = Student::find((int) $this->student_id);
            if ($student) {
                $feeStructures = FeeStructure::query()
                    ->with('session', 'program')
                    ->where('program_id', $student->program_id)
                    ->orderByDesc('session_id')
                    ->get();
            }
        }

        // Compute stats with filters
        $totalsQuery = Payment::query()
            ->when($this->filterSessionId !== '', function ($q): void {
                $q->whereHas('feeStructure', function ($inner): void {
                    $inner->where('session_id', (int) $this->filterSessionId);
                });
            })
            ->when($this->filterMethod !== '', fn ($q) => $q->where('payment_method', $this->filterMethod))
            ->when($this->searchQuery !== '', function ($q): void {
                $q->whereHas('student', function ($inner): void {
                    $inner->where('index_number', 'like', '%'.$this->searchQuery.'%')
                        ->orWhere('lastname', 'like', '%'.$this->searchQuery.'%')
                        ->orWhere('firstname', 'like', '%'.$this->searchQuery.'%');
                });
            });
        
        $totalReceived = (float) $totalsQuery->sum('amount_paid');
        $todayReceived = (float) (clone $totalsQuery)->whereDate('payment_date', now()->toDateString())->sum('amount_paid');
        $transactionCount = $totalsQuery->count();

        // Get receipt details if requested
        $receiptData = [];
        if ($this->receiptPaymentId !== null) {
            $payment = Payment::with(['student.user', 'student.hall', 'student.department', 'student.program', 'feeStructure.session'])->find($this->receiptPaymentId);
            if ($payment && $payment->student && $payment->feeStructure && $payment->feeStructure->session) {
                $service = new FeeCalculationService();
                
                $raw = Setting::query()->where('setting_key', 'finance.receipt_settings')->value('setting_value');
                $settings = is_string($raw) ? json_decode($raw, true) : null;
                
                $receiptData = [
                    'payment' => $payment,
                    'breakdown' => $service->calculateStudentFees($payment->student, $payment->feeStructure->session),
                    'settings' => [
                        'header_title' => (string) ($settings['header_title'] ?? __('College of Education')),
                        'header_subtitle' => (string) ($settings['header_subtitle'] ?? __('Official Tuition & Fees Payment Receipt')),
                        'contact_info' => (string) ($settings['contact_info'] ?? __('+233 24 000 0000 | finance@college.edu.gh')),
                        'footer_note' => (string) ($settings['footer_note'] ?? __('Thank you for your payment. This is an official computer-generated receipt.')),
                        'show_signature' => (bool) ($settings['show_signature'] ?? true),
                        'show_stamp' => (bool) ($settings['show_stamp'] ?? true),
                    ]
                ];
            }
        }

        return view('livewire.admin.finance.finance-payments-page', [
            'rows' => $rows,
            'sessions' => $sessions,
            'searchedStudents' => $searchedStudents,
            'feeStructures' => $feeStructures,
            'totalReceived' => $totalReceived,
            'todayReceived' => $todayReceived,
            'transactionCount' => $transactionCount,
            'receiptData' => $receiptData,
        ])->layout('components.layouts.admin', [
            'title' => __('Payments Ledger'),
            'headerDescription' => __('Record and track student fee payment transactions.')
        ]);
    }

    private function resetRecordForm(): void
    {
        $this->searchStudent = '';
        $this->student_id = '';
        $this->fee_structure_id = '';
        $this->amount_paid = '';
        $this->payment_method = 'Cash';
        $this->payment_date = now()->toDateString();
        $this->reference_number = '';
        $this->studentDebtSummary = [];
        $this->resetValidation();
    }
}
