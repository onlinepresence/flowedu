<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Finance;

use App\Livewire\Concerns\DispatchesCollegeToasts;
use App\Models\AcademicSession;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\Payment;
use App\Models\Program;
use App\Models\Student;
use App\Services\Finance\FeeCalculationService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class FinanceOutstandingPage extends Component
{
    use DispatchesCollegeToasts;
    use WithPagination;

    public string $search = '';

    public string $filterProgramId = '';

    public string $filterLevel = '';

    public string $filterSessionId = '';

    // Record Payment Modal properties
    public bool $showRecordModal = false;

    public string $student_id = '';

    public string $fee_structure_id = '';

    public string $amount_paid = '';

    public string $payment_method = 'Cash';

    public string $payment_date = '';

    public string $reference_number = '';

    public string $selectedStudentName = '';

    // Print Modal Properties
    public bool $showPrintModal = false;

    public string $printGrouping = 'all_one_sheet'; // all_one_sheet or grouped_by_class

    protected $queryString = [
        'search' => ['except' => ''],
        'filterProgramId' => ['except' => ''],
        'filterLevel' => ['except' => ''],
        'filterSessionId' => ['except' => ''],
    ];

    public function mount(): void
    {
        $currentSession = AcademicSession::where('is_current', true)->value('id');
        if ($currentSession !== null) {
            $this->filterSessionId = (string) $currentSession;
        }
        $this->payment_date = now()->toDateString();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterProgramId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterLevel(): void
    {
        $this->resetPage();
    }

    public function updatedFilterSessionId(): void
    {
        $this->resetPage();
    }

    public function initRecordPayment(int $studentId): void
    {
        $student = Student::with('user')->findOrFail($studentId);
        $this->student_id = (string) $student->id;
        $this->selectedStudentName = $student->lastname . ', ' . ($student->othernames ?? '') . ' (' . $student->index_number . ')';
        $this->amount_paid = '';
        $this->payment_method = 'Cash';
        $this->payment_date = now()->toDateString();
        $this->reference_number = '';

        // Retrieve student's outstanding fee structure
        $activeSessionId = $this->filterSessionId !== '' ? (int) $this->filterSessionId : AcademicSession::where('is_current', true)->value('id');
        if ($activeSessionId) {
            $structure = FeeStructure::where('program_id', $student->program_id)
                ->where('level', (int) $student->current_year)
                ->where('session_id', $activeSessionId)
                ->first();
            if ($structure) {
                $this->fee_structure_id = (string) $structure->id;
                
                // Pre-populate amount paid with their current balance
                $ledger = FeePayment::where('student_id', $student->id)->first();
                if ($ledger) {
                    $this->amount_paid = (string) $ledger->balance;
                }
            }
        }

        $this->showRecordModal = true;
        $this->dispatch('open-modal', 'record-outstanding-modal');
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

        Payment::query()->create([
            'student_id' => (int) $this->student_id,
            'fee_structure_id' => (int) $this->fee_structure_id,
            'amount_paid' => (float) $this->amount_paid,
            'payment_method' => $this->payment_method,
            'payment_date' => $this->payment_date,
            'reference_number' => $this->reference_number !== '' ? $this->reference_number : null,
            'status' => 'completed',
            'received_by' => auth()->id(),
        ]);

        $student = Student::findOrFail((int) $this->student_id);
        $structure = FeeStructure::findOrFail((int) $this->fee_structure_id);
        $session = AcademicSession::findOrFail($structure->session_id);

        $service = new FeeCalculationService();
        $service->syncFeePaymentLedger($student, $session);

        $this->showRecordModal = false;
        $this->dispatch('close-modal', 'record-outstanding-modal');
        $this->collegeToast(__('Payment recorded successfully.'));
        $this->resetPage();
    }

    public function openPrintModal(): void
    {
        $this->showPrintModal = true;
        $this->dispatch('open-modal', 'print-debtors-modal');
    }

    public function closePrintModal(): void
    {
        $this->showPrintModal = false;
        $this->dispatch('close-modal', 'print-debtors-modal');
    }

    public function exportToExcel()
    {
        $session = $this->filterSessionId !== '' ? AcademicSession::find((int) $this->filterSessionId) : null;
        $sessionName = $session ? $session->name : 'All';
        
        $baseQuery = FeePayment::query()
            ->with(['student.program', 'student.user', 'department'])
            ->when($this->search !== '', function ($q): void {
                $q->where(function ($inner): void {
                    $inner->where('lastname', 'like', '%'.$this->search.'%')
                        ->orWhere('othernames', 'like', '%'.$this->search.'%')
                        ->orWhereHas('student', function ($s): void {
                            $s->where('index_number', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->when($this->filterLevel !== '', fn ($q) => $q->where('class_level', $this->filterLevel))
            ->when($this->filterProgramId !== '', function ($q): void {
                $q->whereHas('student', function ($s): void {
                    $s->where('program_id', (int) $this->filterProgramId);
                });
            });

        $allRecords = $baseQuery->get();
        $filteredRows = collect();

        $service = new FeeCalculationService();

        foreach ($allRecords as $row) {
            if ($session && $row->student) {
                $calcs = $service->calculateStudentFees($row->student, $session);
                $row->balance = $calcs['balance'];
                $row->amount_paid = $calcs['amount_paid'];
            }
            if ($row->balance > 0) {
                $filteredRows->push($row);
            }
        }

        $filteredRows = $filteredRows->sortByDesc('balance');

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="debtors_list_' . str_replace('/', '_', $sessionName) . '_' . date('Ymd_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($filteredRows) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV Headers
            fputcsv($file, [
                'Level',
                'Index Number',
                'Lastname',
                'Othernames',
                'Program',
                'Department',
                'Total Paid',
                'Owed Balance'
            ]);

            foreach ($filteredRows as $row) {
                fputcsv($file, [
                    $row->class_level,
                    $row->student?->index_number ?? '',
                    $row->lastname,
                    $row->othernames ?? '',
                    $row->student?->program?->name ?? '',
                    $row->department?->name ?? '',
                    number_format((float) $row->amount_paid, 2, '.', ''),
                    number_format((float) $row->balance, 2, '.', '')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render(): View
    {
        $programs = Program::query()->orderBy('name')->get(['id', 'name']);
        $sessions = AcademicSession::query()->orderByDesc('start_date')->get(['id', 'name']);

        // Base query for fee payments
        $baseQuery = FeePayment::query()
            ->with(['student.program', 'student.user', 'department'])
            ->when($this->search !== '', function ($q): void {
                $q->where(function ($inner): void {
                    $inner->where('lastname', 'like', '%'.$this->search.'%')
                        ->orWhere('othernames', 'like', '%'.$this->search.'%')
                        ->orWhereHas('student', function ($s): void {
                            $s->where('index_number', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->when($this->filterLevel !== '', fn ($q) => $q->where('class_level', $this->filterLevel))
            ->when($this->filterProgramId !== '', function ($q): void {
                $q->whereHas('student', function ($s): void {
                    $s->where('program_id', (int) $this->filterProgramId);
                });
            });

        $allRecords = $baseQuery->get();
        $filteredRows = collect();

        $service = new FeeCalculationService();
        $session = $this->filterSessionId !== '' ? AcademicSession::find((int) $this->filterSessionId) : null;

        foreach ($allRecords as $row) {
            if ($session && $row->student) {
                $calcs = $service->calculateStudentFees($row->student, $session);
                $row->balance = $calcs['balance'];
                $row->amount_paid = $calcs['amount_paid'];
            }
            if ($row->balance > 0) {
                $filteredRows->push($row);
            }
        }

        // Sort by balance descending
        $filteredRows = $filteredRows->sortByDesc('balance');

        $totalOutstanding = (float) $filteredRows->sum('balance');
        $studentsInArrears = $filteredRows->count();
        $averageDebt = $studentsInArrears > 0 ? ($totalOutstanding / $studentsInArrears) : 0.0;

        // Custom pagination
        $currentPage = $this->getPage();
        $perPage = 20;
        $paginatedItems = $filteredRows->slice(($currentPage - 1) * $perPage, $perPage)->values()->all();
        
        $rows = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems,
            $filteredRows->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        // Grouped by class for print layout
        $printData = $filteredRows->groupBy('class_level')->sortBy(fn($g, $key) => (int)$key);

        return view('livewire.admin.finance.finance-outstanding-page', [
            'rows' => $rows,
            'programs' => $programs,
            'sessions' => $sessions,
            'totalOutstanding' => $totalOutstanding,
            'studentsInArrears' => $studentsInArrears,
            'averageDebt' => $averageDebt,
            'printData' => $printData,
            'allDebtors' => $filteredRows,
        ])->layout('components.layouts.admin', [
            'title' => __('Outstanding Fees'),
            'headerDescription' => __('Track and manage student accounts with active arrears.')
        ]);
    }
}
