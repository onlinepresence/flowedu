<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Reports;

use App\Models\AcademicInformation;
use App\Models\AcademicSession;
use App\Models\Program;
use App\Models\Result;
use App\Models\Student;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AcademicReportPage extends Component
{
    use WithPagination;

    // Filters
    public ?int $academicSessionId = null;
    public ?int $programId = null;
    public ?string $level = null;

    protected $queryString = [
        'academicSessionId' => ['except' => ''],
        'programId' => ['except' => ''],
        'level' => ['except' => ''],
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

    public function updatedProgramId(): void
    {
        $this->resetPage();
    }

    public function updatedLevel(): void
    {
        $this->resetPage();
    }

    public function exportCSV()
    {
        $session = $this->academicSessionId ? AcademicSession::find($this->academicSessionId) : null;
        $sessionName = $session ? $session->name : 'All';

        $query = AcademicInformation::query()
            ->with(['student.user', 'student.program'])
            ->when($session, fn($q) => $q->where('academic_session', $session->name))
            ->when($this->programId, fn($q) => $q->where('program_id', $this->programId))
            ->when($this->level, fn($q) => $q->where('class_level', $this->level));

        $records = $query->orderByDesc('gpa')->get();
        $filename = 'academic_report_' . str_replace('/', '_', $sessionName) . '_' . date('Ymd_His') . '.csv';

        $callback = function() use ($records) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($file, [
                'Rank',
                'Index Number',
                'Student Name',
                'Program',
                'Level',
                'Session',
                'GPA'
            ]);

            foreach ($records as $index => $row) {
                fputcsv($file, [
                    $index + 1,
                    $row->student?->index_number ?? '',
                    $row->student?->lastname . ', ' . ($row->student?->firstname ?? ''),
                    $row->student?->program?->name ?? '',
                    $row->class_level,
                    $row->academic_session,
                    number_format((float) $row->gpa, 2, '.', '')
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

        $query = AcademicInformation::query()
            ->with(['student.user', 'student.program'])
            ->when($session, fn($q) => $q->where('academic_session', $session->name))
            ->when($this->programId, fn($q) => $q->where('program_id', $this->programId))
            ->when($this->level, fn($q) => $q->where('class_level', $this->level));

        $records = $query->orderByDesc('gpa')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Academic Report');

        // Headers
        $sheet->setCellValue('A1', 'Rank');
        $sheet->setCellValue('B1', 'Index Number');
        $sheet->setCellValue('C1', 'Student Name');
        $sheet->setCellValue('D1', 'Program');
        $sheet->setCellValue('E1', 'Level');
        $sheet->setCellValue('F1', 'Session');
        $sheet->setCellValue('G1', 'GPA');

        // Bold Headers
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        $rowNum = 2;
        foreach ($records as $index => $row) {
            $sheet->setCellValue('A' . $rowNum, $index + 1);
            $sheet->setCellValue('B' . $rowNum, $row->student?->index_number ?? '');
            $sheet->setCellValue('C' . $rowNum, $row->student?->lastname . ', ' . ($row->student?->firstname ?? ''));
            $sheet->setCellValue('D' . $rowNum, $row->student?->program?->name ?? '');
            $sheet->setCellValue('E' . $rowNum, $row->class_level);
            $sheet->setCellValue('F' . $rowNum, $row->academic_session);
            $sheet->setCellValue('G' . $rowNum, number_format((float) $row->gpa, 2, '.', ''));
            $rowNum++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'academic_report_' . str_replace('/', '_', $sessionName) . '_' . date('Ymd_His') . '.xlsx';

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
        $session = $this->academicSessionId ? AcademicSession::find($this->academicSessionId) : null;
        $sessionName = $session ? $session->name : null;

        // Statistics queries
        $studentQuery = Student::query()
            ->when($this->programId, fn($q) => $q->where('program_id', $this->programId))
            ->when($this->level, fn($q) => $q->where('current_year', $this->level));
        $studentCount = $studentQuery->count();

        $programCount = Program::query()->count();

        // Calculate average GPA
        $avgGpa = (float) AcademicInformation::query()
            ->when($sessionName, fn($q) => $q->where('academic_session', $sessionName))
            ->when($this->programId, fn($q) => $q->where('program_id', $this->programId))
            ->when($this->level, fn($q) => $q->where('class_level', $this->level))
            ->avg('gpa');

        // Pass rate calculation
        $resultsQuery = Result::query()
            ->when($this->academicSessionId, fn($q) => $q->where('academic_session_id', $this->academicSessionId))
            ->when($this->programId, function($q) {
                $q->whereHas('student', fn($s) => $s->where('program_id', $this->programId));
            })
            ->when($this->level, function($q) {
                $q->whereHas('student', fn($s) => $s->where('current_year', $this->level));
            });

        $totalResults = $resultsQuery->count();
        $passedResults = $resultsQuery->where('grade', '!=', 'F')->count();
        $passRate = $totalResults > 0 ? ($passedResults / $totalResults) * 100 : 0.0;

        // Group by Program
        $byProgram = AcademicInformation::query()
            ->join('programs', 'academic_information.program_id', '=', 'programs.id')
            ->select([
                'programs.name as program_name',
                DB::raw('COUNT(DISTINCT academic_information.student_id) as student_count'),
                DB::raw('AVG(academic_information.gpa) as avg_gpa')
            ])
            ->when($sessionName, fn($q) => $q->where('academic_information.academic_session', $sessionName))
            ->when($this->level, fn($q) => $q->where('academic_information.class_level', $this->level))
            ->groupBy('programs.id', 'programs.name')
            ->orderByDesc('avg_gpa')
            ->get();

        // Grade Distribution
        $rawGrades = Result::query()
            ->select('grade', DB::raw('COUNT(*) as count'))
            ->when($this->academicSessionId, fn($q) => $q->where('academic_session_id', $this->academicSessionId))
            ->when($this->programId, function($q) {
                $q->whereHas('student', fn($s) => $s->where('program_id', $this->programId));
            })
            ->when($this->level, function($q) {
                $q->whereHas('student', fn($s) => $s->where('current_year', $this->level));
            })
            ->groupBy('grade')
            ->get()
            ->pluck('count', 'grade')
            ->toArray();

        $gradeScale = ['A' => 0, 'B+' => 0, 'B' => 0, 'C' => 0, 'F' => 0];
        foreach ($gradeScale as $key => $val) {
            $gradeScale[$key] = $rawGrades[$key] ?? 0;
        }

        // Students Academic Performance paginated
        $studentsList = AcademicInformation::query()
            ->with(['student.user', 'student.program'])
            ->when($sessionName, fn($q) => $q->where('academic_session', $sessionName))
            ->when($this->programId, fn($q) => $q->where('program_id', $this->programId))
            ->when($this->level, fn($q) => $q->where('class_level', $this->level))
            ->orderByDesc('gpa')
            ->paginate(10);

        return view('livewire.admin.reports.academic-report-page', [
            'studentCount' => $studentCount,
            'programCount' => $programCount,
            'avgGpa' => $avgGpa,
            'passRate' => $passRate,
            'byProgram' => $byProgram,
            'gradeScale' => $gradeScale,
            'studentsList' => $studentsList,
            'sessions' => AcademicSession::orderByDesc('id')->get(),
            'programs' => Program::orderBy('name')->get(),
        ])->layout('components.layouts.admin', [
            'title' => __('Academic Reports'),
            'headerTitle' => __('Academic Performance Reports'),
            'headerDescription' => __('View student GPA distributions, course pass rates, and program-wise performance statistics.')
        ]);
    }
}
