<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Reports;

use App\Models\AcademicSession;
use App\Models\Payment;
use App\Models\Program;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PaymentReportPage extends Component
{
    use WithPagination;

    // Filters
    public ?int $academicSessionId = null;
    public ?string $paymentMethod = null;
    public ?string $startDate = null;
    public ?string $endDate = null;

    protected $queryString = [
        'academicSessionId' => ['except' => ''],
        'paymentMethod' => ['except' => ''],
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
    ];

    public function mount(): void
    {
        $currentSession = AcademicSession::where('is_current', true)->first();
        if ($currentSession) {
            $this->academicSessionId = $currentSession->id;
        } else {
            $this->academicSessionId = AcademicSession::orderByDesc('id')->value('id');
        }
    }

    public function updatedAcademicSessionId(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentMethod(): void
    {
        $this->resetPage();
    }

    public function updatedStartDate(): void
    {
        $this->resetPage();
    }

    public function updatedEndDate(): void
    {
        $this->resetPage();
    }

    public function exportCSV()
    {
        $session = $this->academicSessionId ? AcademicSession::find($this->academicSessionId) : null;
        $sessionName = $session ? $session->name : 'All';

        $query = Payment::query()
            ->with(['student.user', 'feeStructure.program'])
            ->when($this->academicSessionId, function($q) {
                $q->whereHas('feeStructure', fn($fs) => $fs->where('session_id', $this->academicSessionId));
            })
            ->when($this->paymentMethod, fn($q) => $q->where('payment_method', $this->paymentMethod))
            ->when($this->startDate, fn($q) => $q->whereDate('payment_date', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('payment_date', '<=', $this->endDate));

        $records = $query->orderByDesc('payment_date')->get();
        $filename = 'payment_report_' . str_replace('/', '_', $sessionName) . '_' . date('Ymd_His') . '.csv';

        $callback = function() use ($records) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($file, [
                'Reference Number',
                'Student Index',
                'Student Name',
                'Program',
                'Method',
                'Amount Paid (GHS)',
                'Payment Date'
            ]);

            foreach ($records as $row) {
                fputcsv($file, [
                    $row->reference_number ?? '',
                    $row->student?->index_number ?? '',
                    $row->student?->lastname . ', ' . ($row->student?->firstname ?? ''),
                    $row->feeStructure?->program?->name ?? '',
                    $row->payment_method,
                    number_format((float) $row->amount_paid, 2, '.', ''),
                    $row->payment_date ? $row->payment_date->format('Y-m-d') : ''
                ]);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Expires' => '0',
        ]);
    }

    public function exportExcel()
    {
        $session = $this->academicSessionId ? AcademicSession::find($this->academicSessionId) : null;
        $sessionName = $session ? $session->name : 'All';

        $query = Payment::query()
            ->with(['student.user', 'feeStructure.program'])
            ->when($this->academicSessionId, function($q) {
                $q->whereHas('feeStructure', fn($fs) => $fs->where('session_id', $this->academicSessionId));
            })
            ->when($this->paymentMethod, fn($q) => $q->where('payment_method', $this->paymentMethod))
            ->when($this->startDate, fn($q) => $q->whereDate('payment_date', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('payment_date', '<=', $this->endDate));

        $records = $query->orderByDesc('payment_date')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Payment Report');

        // Headers
        $sheet->setCellValue('A1', 'Reference Number');
        $sheet->setCellValue('B1', 'Student Index');
        $sheet->setCellValue('C1', 'Student Name');
        $sheet->setCellValue('D1', 'Program');
        $sheet->setCellValue('E1', 'Method');
        $sheet->setCellValue('F1', 'Amount Paid (GHS)');
        $sheet->setCellValue('G1', 'Payment Date');

        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        $rowNum = 2;
        foreach ($records as $row) {
            $sheet->setCellValue('A' . $rowNum, $row->reference_number ?? '');
            $sheet->setCellValue('B' . $rowNum, $row->student?->index_number ?? '');
            $sheet->setCellValue('C' . $rowNum, $row->student?->lastname . ', ' . ($row->student?->firstname ?? ''));
            $sheet->setCellValue('D' . $rowNum, $row->feeStructure?->program?->name ?? '');
            $sheet->setCellValue('E' . $rowNum, $row->payment_method);
            $sheet->setCellValue('F' . $rowNum, number_format((float) $row->amount_paid, 2, '.', ''));
            $sheet->setCellValue('G' . $rowNum, $row->payment_date ? $row->payment_date->format('Y-m-d') : '');
            $rowNum++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'payment_report_' . str_replace('/', '_', $sessionName) . '_' . date('Ymd_His') . '.xlsx';

        $callback = function() use ($writer) {
            $writer->save('php://output');
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function render(): View
    {
        $monthExpr = match (Schema::getConnection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', payment_date)",
            default => "DATE_FORMAT(payment_date, '%Y-%m')",
        };

        // Base collections query
        $baseQuery = Payment::query()
            ->when($this->academicSessionId, function($q) {
                $q->whereHas('feeStructure', fn($fs) => $fs->where('session_id', $this->academicSessionId));
            })
            ->when($this->paymentMethod, fn($q) => $q->where('payment_method', $this->paymentMethod))
            ->when($this->startDate, fn($q) => $q->whereDate('payment_date', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->whereDate('payment_date', '<=', $this->endDate));

        // Stats
        $allTimeTotal = (float) Payment::query()->sum('amount_paid');
        $selectedPeriodTotal = (float) $baseQuery->sum('amount_paid');
        $transactionCount = $baseQuery->count();
        $avgTransaction = $transactionCount > 0 ? ($selectedPeriodTotal / $transactionCount) : 0.0;

        // Group by Month
        $byMonth = (clone $baseQuery)
            ->select([
                DB::raw("{$monthExpr} as ym"),
                DB::raw('SUM(amount_paid) as total'),
                DB::raw('COUNT(*) as cnt'),
            ])
            ->whereNotNull('payment_date')
            ->groupBy('ym')
            ->orderByDesc('ym')
            ->limit(12)
            ->get();

        // Group by Program
        $byProgram = (clone $baseQuery)
            ->join('fee_structures', 'payments.fee_structure_id', '=', 'fee_structures.id')
            ->join('programs', 'fee_structures.program_id', '=', 'programs.id')
            ->select([
                'programs.name as program_name',
                DB::raw('SUM(payments.amount_paid) as total'),
                DB::raw('COUNT(payments.id) as cnt'),
            ])
            ->groupBy('programs.id', 'programs.name')
            ->orderByDesc('total')
            ->get();

        // Group by Payment Method
        $byMethod = (clone $baseQuery)
            ->select([
                'payment_method',
                DB::raw('SUM(amount_paid) as total'),
                DB::raw('COUNT(*) as cnt'),
            ])
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        // Detailed Transaction List paginated
        $transactions = (clone $baseQuery)
            ->with(['student.user', 'feeStructure.program'])
            ->orderByDesc('payment_date')
            ->paginate(10);

        return view('livewire.admin.reports.payment-report-page', [
            'allTimeTotal' => $allTimeTotal,
            'selectedPeriodTotal' => $selectedPeriodTotal,
            'transactionCount' => $transactionCount,
            'avgTransaction' => $avgTransaction,
            'byMonth' => $byMonth,
            'byProgram' => $byProgram,
            'byMethod' => $byMethod,
            'transactions' => $transactions,
            'sessions' => AcademicSession::orderByDesc('id')->get(),
            'paymentMethods' => ['Cash', 'Bank Draft', 'Mobile Money', 'Bank Transfer', 'Check'],
        ])->layout('components.layouts.admin', [
            'title' => __('Payment Reports'),
            'headerTitle' => __('Financial Payment Reports'),
            'headerDescription' => __('View consolidated financial summaries, monthly revenue streams, and payment method stats.')
        ]);
    }
}
