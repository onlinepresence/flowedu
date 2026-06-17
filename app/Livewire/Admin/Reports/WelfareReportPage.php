<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Reports;

use App\Models\AcademicSession;
use App\Models\DisciplinaryRecord;
use App\Models\MedicalHistory;
use App\Models\Program;
use App\Models\Student;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class WelfareReportPage extends Component
{
    use WithPagination;

    // Filters
    public ?int $academicSessionId = null;
    public ?int $programId = null;
    public ?string $level = null;
    public string $returnStatus = 'all'; // all, active, resolved
    public string $hasMedicalCondition = 'all'; // all, yes, no

    protected $queryString = [
        'academicSessionId' => ['except' => ''],
        'programId' => ['except' => ''],
        'level' => ['except' => ''],
        'returnStatus' => ['except' => 'all'],
        'hasMedicalCondition' => ['except' => 'all'],
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
        $this->resetPage('disciplinaryPage');
        $this->resetPage('medicalPage');
    }

    public function updatedProgramId(): void
    {
        $this->resetPage('disciplinaryPage');
        $this->resetPage('medicalPage');
    }

    public function updatedLevel(): void
    {
        $this->resetPage('disciplinaryPage');
        $this->resetPage('medicalPage');
    }

    public function updatedReturnStatus(): void
    {
        $this->resetPage('disciplinaryPage');
    }

    public function updatedHasMedicalCondition(): void
    {
        $this->resetPage('medicalPage');
    }

    public function exportDisciplinaryCSV()
    {
        $query = DisciplinaryRecord::query()
            ->with(['program', 'academicSession'])
            ->when($this->programId, fn($q) => $q->where('program_id', $this->programId))
            ->when($this->academicSessionId, function($q) {
                $q->where('academic_session_id', $this->academicSessionId);
            })
            ->when($this->returnStatus !== 'all', function($q) {
                $status = $this->returnStatus === 'resolved';
                $q->where('return_status', $status);
            });

        $records = $query->orderByDesc('date_of_action')->get();
        $filename = 'welfare_disciplinary_report_' . date('Ymd_His') . '.csv';

        $callback = function() use ($records) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($file, [
                'Student Index',
                'Student Name',
                'Program',
                'Academic Session',
                'Offense',
                'Action Taken',
                'Action Date',
                'Return Date',
                'Status'
            ]);

            foreach ($records as $row) {
                fputcsv($file, [
                    $row->index_number,
                    $row->fullname,
                    $row->program?->name ?? '',
                    $row->academicSession?->name ?? 'N/A',
                    $row->offense,
                    $row->action_taken,
                    $row->date_of_action ? $row->date_of_action->format('Y-m-d') : '',
                    $row->return_date ? $row->return_date->format('Y-m-d') : '',
                    $row->return_status ? 'Resolved' : 'Active Suspension/Sanction'
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

    public function exportDisciplinaryExcel()
    {
        $query = DisciplinaryRecord::query()
            ->with(['program', 'academicSession'])
            ->when($this->programId, fn($q) => $q->where('program_id', $this->programId))
            ->when($this->academicSessionId, function($q) {
                $q->where('academic_session_id', $this->academicSessionId);
            })
            ->when($this->returnStatus !== 'all', function($q) {
                $status = $this->returnStatus === 'resolved';
                $q->where('return_status', $status);
            });

        $records = $query->orderByDesc('date_of_action')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Disciplinary Records');

        // Headers
        $sheet->setCellValue('A1', 'Student Index');
        $sheet->setCellValue('B1', 'Student Name');
        $sheet->setCellValue('C1', 'Program');
        $sheet->setCellValue('D1', 'Academic Session');
        $sheet->setCellValue('E1', 'Offense');
        $sheet->setCellValue('F1', 'Action Taken');
        $sheet->setCellValue('G1', 'Action Date');
        $sheet->setCellValue('H1', 'Return Date');
        $sheet->setCellValue('I1', 'Status');

        $sheet->getStyle('A1:I1')->getFont()->setBold(true);

        $rowNum = 2;
        foreach ($records as $row) {
            $sheet->setCellValue('A' . $rowNum, $row->index_number);
            $sheet->setCellValue('B' . $rowNum, $row->fullname);
            $sheet->setCellValue('C' . $rowNum, $row->program?->name ?? '');
            $sheet->setCellValue('D' . $rowNum, $row->academicSession?->name ?? 'N/A');
            $sheet->setCellValue('E' . $rowNum, $row->offense);
            $sheet->setCellValue('F' . $rowNum, $row->action_taken);
            $sheet->setCellValue('G' . $rowNum, $row->date_of_action ? $row->date_of_action->format('Y-m-d') : '');
            $sheet->setCellValue('H' . $rowNum, $row->return_date ? $row->return_date->format('Y-m-d') : '');
            $sheet->setCellValue('I' . $rowNum, $row->return_status ? 'Resolved' : 'Active Suspension/Sanction');
            $rowNum++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'welfare_disciplinary_report_' . date('Ymd_His') . '.xlsx';

        $callback = function() use ($writer) {
            $writer->save('php://output');
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function exportMedicalCSV()
    {
        $query = MedicalHistory::query()
            ->with(['student.program', 'academicSession'])
            ->whereHas('student', function($q) {
                $q->when($this->programId, fn($inner) => $inner->where('program_id', $this->programId))
                  ->when($this->level, fn($inner) => $inner->where('current_year', $this->level));
            })
            ->when($this->academicSessionId, function($q) {
                $q->where('academic_session_id', $this->academicSessionId);
            })
            ->when($this->hasMedicalCondition !== 'all', function($q) {
                if ($this->hasMedicalCondition === 'yes') {
                    $q->where(function($inner) {
                        $inner->where('medical_conditions', '!=', 'None')
                              ->orWhere('allergies', '!=', 'None');
                    });
                } else {
                    $q->where('medical_conditions', 'None')
                      ->where('allergies', 'None');
                }
            });

        $records = $query->get();
        $filename = 'welfare_medical_report_' . date('Ymd_His') . '.csv';

        $callback = function() use ($records) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($file, [
                'Student Index',
                'Student Name',
                'Program',
                'Level',
                'Academic Session',
                'Medical Conditions',
                'Allergies',
                'Remarks'
            ]);

            foreach ($records as $row) {
                fputcsv($file, [
                    $row->student?->index_number ?? '',
                    ($row->student?->lastname ?? '') . ', ' . ($row->student?->firstname ?? ''),
                    $row->student?->program?->name ?? '',
                    $row->student?->current_year ?? '',
                    $row->academicSession?->name ?? 'N/A',
                    $row->medical_conditions,
                    $row->allergies,
                    $row->remarks ?? ''
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

    public function exportMedicalExcel()
    {
        $query = MedicalHistory::query()
            ->with(['student.program', 'academicSession'])
            ->whereHas('student', function($q) {
                $q->when($this->programId, fn($inner) => $inner->where('program_id', $this->programId))
                  ->when($this->level, fn($inner) => $inner->where('current_year', $this->level));
            })
            ->when($this->academicSessionId, function($q) {
                $q->where('academic_session_id', $this->academicSessionId);
            })
            ->when($this->hasMedicalCondition !== 'all', function($q) {
                if ($this->hasMedicalCondition === 'yes') {
                    $q->where(function($inner) {
                        $inner->where('medical_conditions', '!=', 'None')
                              ->orWhere('allergies', '!=', 'None');
                    });
                } else {
                    $q->where('medical_conditions', 'None')
                      ->where('allergies', 'None');
                }
            });

        $records = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Medical Records');

        // Headers
        $sheet->setCellValue('A1', 'Student Index');
        $sheet->setCellValue('B1', 'Student Name');
        $sheet->setCellValue('C1', 'Program');
        $sheet->setCellValue('D1', 'Level');
        $sheet->setCellValue('E1', 'Academic Session');
        $sheet->setCellValue('F1', 'Medical Conditions');
        $sheet->setCellValue('G1', 'Allergies');
        $sheet->setCellValue('H1', 'Remarks');

        $sheet->getStyle('A1:H1')->getFont()->setBold(true);

        $rowNum = 2;
        foreach ($records as $row) {
            $sheet->setCellValue('A' . $rowNum, $row->student?->index_number ?? '');
            $sheet->setCellValue('B' . $rowNum, ($row->student?->lastname ?? '') . ', ' . ($row->student?->firstname ?? ''));
            $sheet->setCellValue('C' . $rowNum, $row->student?->program?->name ?? '');
            $sheet->setCellValue('D' . $rowNum, $row->student?->current_year ?? '');
            $sheet->setCellValue('E' . $rowNum, $row->academicSession?->name ?? 'N/A');
            $sheet->setCellValue('F' . $rowNum, $row->medical_conditions);
            $sheet->setCellValue('G' . $rowNum, $row->allergies);
            $sheet->setCellValue('H' . $rowNum, $row->remarks ?? '');
            $rowNum++;
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'welfare_medical_report_' . date('Ymd_His') . '.xlsx';

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
        // Statistics (Welfare & Discipline)
        $activeCases = DisciplinaryRecord::query()->where('return_status', false)->count();
        $resolvedCases = DisciplinaryRecord::query()->where('return_status', true)->count();
        $totalInfractions = DisciplinaryRecord::query()->count();

        // Medical count (students with conditions or allergies that are not 'None')
        $medicalAlerts = MedicalHistory::query()
            ->where(function($q) {
                $q->where('medical_conditions', '!=', 'None')
                  ->orWhere('allergies', '!=', 'None');
            })->count();

        // 1. Fetch Disciplinary Cases paginated
        $disciplinaryCases = DisciplinaryRecord::query()
            ->with(['program', 'academicSession'])
            ->when($this->programId, fn($q) => $q->where('program_id', $this->programId))
            ->when($this->academicSessionId, function($q) {
                $q->where('academic_session_id', $this->academicSessionId);
            })
            ->when($this->returnStatus !== 'all', function($q) {
                $status = $this->returnStatus === 'resolved';
                $q->where('return_status', $status);
            })
            ->orderByDesc('date_of_action')
            ->paginate(10, ['*'], 'disciplinaryPage');

        // 2. Fetch Medical Registry paginated
        $medicalRegistry = MedicalHistory::query()
            ->with(['student.program', 'academicSession'])
            ->whereHas('student', function($q) {
                $q->when($this->programId, fn($inner) => $inner->where('program_id', $this->programId))
                  ->when($this->level, fn($inner) => $inner->where('current_year', $this->level));
            })
            ->when($this->academicSessionId, function($q) {
                $q->where('academic_session_id', $this->academicSessionId);
            })
            ->when($this->hasMedicalCondition !== 'all', function($q) {
                if ($this->hasMedicalCondition === 'yes') {
                    $q->where(function($inner) {
                        $inner->where('medical_conditions', '!=', 'None')
                              ->orWhere('allergies', '!=', 'None');
                    });
                } else {
                    $q->where('medical_conditions', 'None')
                      ->where('allergies', 'None');
                }
            })
            ->paginate(10, ['*'], 'medicalPage');

        $currentSessionName = $this->academicSessionId 
            ? (AcademicSession::where('id', $this->academicSessionId)->value('name') ?? __('N/A')) 
            : __('All Sessions');

        return view('livewire.admin.reports.welfare-report-page', [
            'activeCases' => $activeCases,
            'resolvedCases' => $resolvedCases,
            'totalInfractions' => $totalInfractions,
            'medicalAlerts' => $medicalAlerts,
            'disciplinaryCases' => $disciplinaryCases,
            'medicalRegistry' => $medicalRegistry,
            'programs' => Program::orderBy('name')->get(),
            'sessions' => AcademicSession::orderByDesc('id')->get(),
            'currentSessionName' => $currentSessionName,
        ])->layout('components.layouts.admin', [
            'title' => __('Welfare & Disciplinary Reports'),
            'headerTitle' => __('Student Welfare & Disciplinary Reports'),
            'headerDescription' => __('View active disciplinary records, suspension tracking, student medical history, and allergy logs.')
        ]);
    }
}
