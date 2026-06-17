<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Reports;

use App\Models\Program;
use App\Models\Student;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class EnrollmentReportPage extends Component
{
    use WithPagination;

    // Filters
    public ?int $programId = null;
    public ?string $level = null;
    public ?string $nationality = null;
    public ?string $gender = null;

    protected $queryString = [
        'programId' => ['except' => ''],
        'level' => ['except' => ''],
        'nationality' => ['except' => ''],
        'gender' => ['except' => ''],
    ];

    public function updatedProgramId(): void
    {
        $this->resetPage();
    }

    public function updatedLevel(): void
    {
        $this->resetPage();
    }

    public function updatedNationality(): void
    {
        $this->resetPage();
    }

    public function updatedGender(): void
    {
        $this->resetPage();
    }

    public function exportCSV()
    {
        $query = Student::query()
            ->with(['program'])
            ->when($this->programId, fn($q) => $q->where('program_id', $this->programId))
            ->when($this->level, fn($q) => $q->where('current_year', $this->level))
            ->when($this->nationality, fn($q) => $q->where('nationality', $this->nationality))
            ->when($this->gender, fn($q) => $q->where('gender', $this->gender));

        $records = $query->orderBy('lastname')->get();
        $filename = 'enrollment_report_' . date('Ymd_His') . '.csv';

        $callback = function() use ($records) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($file, [
                'Index Number',
                'Lastname',
                'Firstname',
                'Program',
                'Level',
                'Gender',
                'Nationality',
                'Status',
                'New Student'
            ]);

            foreach ($records as $row) {
                fputcsv($file, [
                    $row->index_number,
                    $row->lastname,
                    $row->firstname ?? '',
                    $row->program?->name ?? '',
                    $row->current_year,
                    ucfirst($row->gender),
                    $row->nationality,
                    $row->approved ? ($row->graduated ? 'Graduated' : 'Approved') : 'Pending Approval',
                    $row->is_new ? 'Yes' : 'No'
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
        $query = Student::query()
            ->with(['program'])
            ->when($this->programId, fn($q) => $q->where('program_id', $this->programId))
            ->when($this->level, fn($q) => $q->where('current_year', $this->level))
            ->when($this->nationality, fn($q) => $q->where('nationality', $this->nationality))
            ->when($this->gender, fn($q) => $q->where('gender', $this->gender));

        $records = $query->orderBy('lastname')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Enrollment Report');

        // Headers
        $sheet->setCellValue('A1', 'Index Number');
        $sheet->setCellValue('B1', 'Lastname');
        $sheet->setCellValue('C1', 'Firstname');
        $sheet->setCellValue('D1', 'Program');
        $sheet->setCellValue('E1', 'Level');
        $sheet->setCellValue('F1', 'Gender');
        $sheet->setCellValue('G1', 'Nationality');
        $sheet->setCellValue('H1', 'Status');
        $sheet->setCellValue('I1', 'New Student');

        $sheet->getStyle('A1:I1')->getFont()->setBold(true);

        $rowNum = 2;
        foreach ($records as $row) {
            $sheet->setCellValue('A' . $rowNum, $row->index_number);
            $sheet->setCellValue('B' . $rowNum, $row->lastname);
            $sheet->setCellValue('C' . $rowNum, $row->firstname ?? '');
            $sheet->setCellValue('D' . $rowNum, $row->program?->name ?? '');
            $sheet->setCellValue('E' . $rowNum, $row->current_year);
            $sheet->setCellValue('F' . $rowNum, ucfirst($row->gender));
            $sheet->setCellValue('G' . $rowNum, $row->nationality);
            $sheet->setCellValue('H' . $rowNum, $row->approved ? ($row->graduated ? 'Graduated' : 'Approved') : 'Pending Approval');
            $sheet->setCellValue('I' . $rowNum, $row->is_new ? 'Yes' : 'No');
            $rowNum++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'enrollment_report_' . date('Ymd_His') . '.xlsx';

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
        // Statistics
        $totalEnrolled = Student::query()->where('approved', true)->where('graduated', false)->count();
        $totalPending = Student::query()->where('approved', false)->count();
        $totalGraduated = Student::query()->where('graduated', true)->count();
        $totalNew = Student::query()->where('is_new', true)->where('approved', true)->count();

        // Enrollment Matrix (Program vs Year Level)
        $enrollmentMatrix = Student::query()
            ->join('programs', 'students.program_id', '=', 'programs.id')
            ->select([
                'programs.name as program_name',
                DB::raw("SUM(CASE WHEN current_year = '100' THEN 1 ELSE 0 END) as level_100"),
                DB::raw("SUM(CASE WHEN current_year = '200' THEN 1 ELSE 0 END) as level_200"),
                DB::raw("SUM(CASE WHEN current_year = '300' THEN 1 ELSE 0 END) as level_300"),
                DB::raw("SUM(CASE WHEN current_year = '400' THEN 1 ELSE 0 END) as level_400"),
                DB::raw("COUNT(students.id) as total")
            ])
            ->where('students.approved', true)
            ->where('students.graduated', false)
            ->groupBy('programs.id', 'programs.name')
            ->orderByDesc('total')
            ->get();

        // Gender Distribution
        $genderDistribution = Student::query()
            ->select('gender', DB::raw('COUNT(*) as count'))
            ->where('approved', true)
            ->where('graduated', false)
            ->groupBy('gender')
            ->get()
            ->pluck('count', 'gender')
            ->toArray();

        $maleCount = $genderDistribution['male'] ?? ($genderDistribution['Male'] ?? 0);
        $femaleCount = $genderDistribution['female'] ?? ($genderDistribution['Female'] ?? 0);
        $totalGender = $maleCount + $femaleCount;
        $malePercentage = $totalGender > 0 ? ($maleCount / $totalGender) * 100 : 0.0;
        $femalePercentage = $totalGender > 0 ? ($femaleCount / $totalGender) * 100 : 0.0;

        // Nationality Breakdown
        $nationalities = Student::query()
            ->select('nationality', DB::raw('COUNT(*) as count'))
            ->where('approved', true)
            ->where('graduated', false)
            ->groupBy('nationality')
            ->orderByDesc('count')
            ->get();

        // Filtered Students List paginated
        $studentsList = Student::query()
            ->with(['program'])
            ->when($this->programId, fn($q) => $q->where('program_id', $this->programId))
            ->when($this->level, fn($q) => $q->where('current_year', $this->level))
            ->when($this->nationality, fn($q) => $q->where('nationality', $this->nationality))
            ->when($this->gender, fn($q) => $q->where('gender', $this->gender))
            ->orderBy('lastname')
            ->paginate(10);

        // Unique Nationalities for dropdown
        $allNationalities = Student::query()
            ->select('nationality')
            ->distinct()
            ->whereNotNull('nationality')
            ->pluck('nationality');

        return view('livewire.admin.reports.enrollment-report-page', [
            'totalEnrolled' => $totalEnrolled,
            'totalPending' => $totalPending,
            'totalGraduated' => $totalGraduated,
            'totalNew' => $totalNew,
            'enrollmentMatrix' => $enrollmentMatrix,
            'maleCount' => $maleCount,
            'femaleCount' => $femaleCount,
            'malePercentage' => $malePercentage,
            'femalePercentage' => $femalePercentage,
            'nationalities' => $nationalities,
            'studentsList' => $studentsList,
            'programs' => Program::orderBy('name')->get(),
            'allNationalities' => $allNationalities,
        ])->layout('components.layouts.admin', [
            'title' => __('Enrollment Reports'),
            'headerTitle' => __('Enrollment & Demographics Reports'),
            'headerDescription' => __('View student enrollment status, gender ratios, nationality metrics, and cohort program distributions.')
        ]);
    }
}
