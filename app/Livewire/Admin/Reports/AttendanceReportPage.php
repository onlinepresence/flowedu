<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Reports;

use App\Models\AcademicInformation;
use App\Models\AcademicSession;
use App\Models\Program;
use App\Models\Student;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AttendanceReportPage extends Component
{
    use WithPagination;

    // Filters
    public ?int $academicSessionId = null;
    public ?int $programId = null;
    public ?string $level = null;
    public ?string $eligibilityStatus = null;

    protected $queryString = [
        'academicSessionId' => ['except' => ''],
        'programId' => ['except' => ''],
        'level' => ['except' => ''],
        'eligibilityStatus' => ['except' => ''],
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

    public function updatedEligibilityStatus(): void
    {
        $this->resetPage();
    }

    private function getMinThresholdInfo(): array
    {
        $settings = Setting::query()
            ->where('category', 'system_preferences')
            ->pluck('setting_value', 'setting_key');
        
        $showPolicy = (bool) ($settings['system_preferences.show_attendance_policy'] ?? true);
        $minThreshold = (int) ($settings['system_preferences.min_attendance_threshold'] ?? 75);
        $minDays = ($minThreshold / 100) * 120;

        return [$showPolicy, $minThreshold, $minDays];
    }

    public function exportCSV()
    {
        $session = $this->academicSessionId ? AcademicSession::find($this->academicSessionId) : null;
        $sessionName = $session ? $session->name : 'All';

        [$showPolicy, $minThreshold, $minDays] = $this->getMinThresholdInfo();

        $query = AcademicInformation::query()
            ->with(['student.user', 'student.program'])
            ->when($session, fn($q) => $q->where('academic_session', $session->name))
            ->when($this->programId, fn($q) => $q->where('program_id', $this->programId))
            ->when($this->level, fn($q) => $q->where('class_level', $this->level))
            ->when($this->eligibilityStatus === 'eligible', fn($q) => $q->where('attendance_record', '>=', $minDays))
            ->when($this->eligibilityStatus === 'ineligible', fn($q) => $q->where('attendance_record', '<', $minDays));

        $records = $query->orderByDesc('attendance_record')->get();
        $filename = 'attendance_report_' . str_replace('/', '_', $sessionName) . '_' . date('Ymd_His') . '.csv';

        $callback = function() use ($records, $minThreshold) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($file, [
                'Rank',
                'Index Number',
                'Student Name',
                'Program',
                'Level',
                'Session',
                'Days Attended',
                'Attendance Rate',
                'Exam Eligibility'
            ]);

            foreach ($records as $index => $row) {
                $rate = min(100.0, ($row->attendance_record / 120) * 100);
                fputcsv($file, [
                    $index + 1,
                    $row->student?->index_number ?? '',
                    $row->student?->lastname . ', ' . ($row->student?->firstname ?? ''),
                    $row->student?->program?->name ?? '',
                    $row->class_level,
                    $row->academic_session,
                    $row->attendance_record,
                    number_format($rate, 1) . '%',
                    $rate >= $minThreshold ? 'Eligible' : 'Ineligible'
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

        [$showPolicy, $minThreshold, $minDays] = $this->getMinThresholdInfo();

        $query = AcademicInformation::query()
            ->with(['student.user', 'student.program'])
            ->when($session, fn($q) => $q->where('academic_session', $session->name))
            ->when($this->programId, fn($q) => $q->where('program_id', $this->programId))
            ->when($this->level, fn($q) => $q->where('class_level', $this->level))
            ->when($this->eligibilityStatus === 'eligible', fn($q) => $q->where('attendance_record', '>=', $minDays))
            ->when($this->eligibilityStatus === 'ineligible', fn($q) => $q->where('attendance_record', '<', $minDays));

        $records = $query->orderByDesc('attendance_record')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Attendance Report');

        // Headers
        $sheet->setCellValue('A1', 'Rank');
        $sheet->setCellValue('B1', 'Index Number');
        $sheet->setCellValue('C1', 'Student Name');
        $sheet->setCellValue('D1', 'Program');
        $sheet->setCellValue('E1', 'Level');
        $sheet->setCellValue('F1', 'Session');
        $sheet->setCellValue('G1', 'Days Attended');
        $sheet->setCellValue('H1', 'Attendance Rate');
        $sheet->setCellValue('I1', 'Exam Eligibility');

        $sheet->getStyle('A1:I1')->getFont()->setBold(true);

        $rowNum = 2;
        foreach ($records as $index => $row) {
            $rate = min(100.0, ($row->attendance_record / 120) * 100);
            $sheet->setCellValue('A' . $rowNum, $index + 1);
            $sheet->setCellValue('B' . $rowNum, $row->student?->index_number ?? '');
            $sheet->setCellValue('C' . $rowNum, $row->student?->lastname . ', ' . ($row->student?->firstname ?? ''));
            $sheet->setCellValue('D' . $rowNum, $row->student?->program?->name ?? '');
            $sheet->setCellValue('E' . $rowNum, $row->class_level);
            $sheet->setCellValue('F' . $rowNum, $row->academic_session);
            $sheet->setCellValue('G' . $rowNum, $row->attendance_record);
            $sheet->setCellValue('H' . $rowNum, number_format($rate, 1) . '%');
            $sheet->setCellValue('I' . $rowNum, $rate >= $minThreshold ? 'Eligible' : 'Ineligible');
            $rowNum++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'attendance_report_' . str_replace('/', '_', $sessionName) . '_' . date('Ymd_His') . '.xlsx';

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

        [$showPolicy, $minThreshold, $minDays] = $this->getMinThresholdInfo();

        // Base query
        $baseQuery = AcademicInformation::query()
            ->when($sessionName, fn($q) => $q->where('academic_session', $sessionName))
            ->when($this->programId, fn($q) => $q->where('program_id', $this->programId))
            ->when($this->level, fn($q) => $q->where('class_level', $this->level))
            ->when($this->eligibilityStatus === 'eligible', fn($q) => $q->where('attendance_record', '>=', $minDays))
            ->when($this->eligibilityStatus === 'ineligible', fn($q) => $q->where('attendance_record', '<', $minDays));

        // Stats
        $totalDays = (int) $baseQuery->sum('attendance_record');
        $totalRecords = $baseQuery->count();
        $avgDays = $totalRecords > 0 ? ($totalDays / $totalRecords) : 0.0;

        // Top Performing Class Level (Highest average attendance)
        $topLevelRow = AcademicInformation::query()
            ->select('class_level', DB::raw('AVG(attendance_record) as avg_days'))
            ->when($sessionName, fn($q) => $q->where('academic_session', $sessionName))
            ->groupBy('class_level')
            ->orderByDesc('avg_days')
            ->first();
        $topLevel = $topLevelRow ? 'Level ' . $topLevelRow->class_level : '—';

        // Group by Program
        $byProgram = AcademicInformation::query()
            ->join('programs', 'academic_information.program_id', '=', 'programs.id')
            ->select([
                'programs.name as program_name',
                DB::raw('COUNT(DISTINCT academic_information.student_id) as student_count'),
                DB::raw('AVG(academic_information.attendance_record) as avg_days')
            ])
            ->when($sessionName, fn($q) => $q->where('academic_information.academic_session', $sessionName))
            ->when($this->level, fn($q) => $q->where('academic_information.class_level', $this->level))
            ->groupBy('programs.id', 'programs.name')
            ->orderByDesc('avg_days')
            ->get();

        // Group by Level
        $byLevel = AcademicInformation::query()
            ->select([
                'class_level',
                DB::raw('COUNT(DISTINCT student_id) as student_count'),
                DB::raw('AVG(attendance_record) as avg_days')
            ])
            ->when($sessionName, fn($q) => $q->where('academic_session', $sessionName))
            ->when($this->programId, fn($q) => $q->where('program_id', $this->programId))
            ->groupBy('class_level')
            ->orderBy('class_level')
            ->get();

        // Student Detail rows paginated
        $rows = AcademicInformation::query()
            ->with(['student.user', 'student.program'])
            ->when($sessionName, fn($q) => $q->where('academic_session', $sessionName))
            ->when($this->programId, fn($q) => $q->where('program_id', $this->programId))
            ->when($this->level, fn($q) => $q->where('class_level', $this->level))
            ->when($this->eligibilityStatus === 'eligible', fn($q) => $q->where('attendance_record', '>=', $minDays))
            ->when($this->eligibilityStatus === 'ineligible', fn($q) => $q->where('attendance_record', '<', $minDays))
            ->orderByDesc('attendance_record')
            ->paginate(10);

        return view('livewire.admin.reports.attendance-report-page', [
            'totalDays' => $totalDays,
            'totalRecords' => $totalRecords,
            'avgDays' => $avgDays,
            'topLevel' => $topLevel,
            'byProgram' => $byProgram,
            'byLevel' => $byLevel,
            'rows' => $rows,
            'sessions' => AcademicSession::orderByDesc('id')->get(),
            'programs' => Program::orderBy('name')->get(),
            'minThreshold' => $minThreshold,
            'showPolicy' => $showPolicy,
        ])->layout('components.layouts.admin', [
            'title' => __('Attendance Reports'),
            'headerTitle' => __('Student Attendance Reports'),
            'headerDescription' => __('View compiled student attendance metrics, class-wise and program-wise averages.')
        ]);
    }
}
