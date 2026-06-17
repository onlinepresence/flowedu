<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Support\ClearanceDepartments;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StudentClearancePage extends Component
{
    public bool $isLocked = true;

    /** @var array<string, array{status: string, cleared_by: ?string, cleared_at: ?string}> */
    public array $clearanceStatus = [];

    public string $overallStatus = 'locked';

    public int $clearedCount = 0;

    public int $totalCount = 0;

    public int $finalYear = 400;

    public string $currentYearLabel = '';

    public function mount(): void
    {
        $user = auth()->user();
        $student = $user?->student;

        if ($student === null) {
            abort(403);
        }

        $student->loadMissing('program');
        $this->currentYearLabel = (string) $student->current_year;

        $programLength = (int) ($student->program?->program_length ?? 4);
        $this->finalYear = $programLength * 100;

        $this->isLocked = $this->finalYear !== (int) $student->current_year;

        if ($this->isLocked) {
            $this->overallStatus = 'locked';

            return;
        }

        $rows = $student->clearances()->get()->keyBy('department_key');
        $labels = ClearanceDepartments::definitions();

        foreach ($labels as $key => $_label) {
            $def = ClearanceDepartments::defaultStatusForDepartment($key);
            if ($rows->has($key)) {
                $row = $rows->get($key);
                $clearedByLabel = null;
                if ($row->cleared_by !== null) {
                    $row->loadMissing('clearedBy');
                    $clearedByLabel = $row->clearedBy?->username ?? $row->clearedBy?->email;
                }
                $this->clearanceStatus[$key] = [
                    'status' => $row->status,
                    'cleared_by' => $clearedByLabel,
                    'cleared_at' => $row->cleared_at?->toIso8601String(),
                ];
            } else {
                $this->clearanceStatus[$key] = [
                    'status' => $def,
                    'cleared_by' => null,
                    'cleared_at' => null,
                ];
            }
        }

        $this->computeOverall();
    }

    private function computeOverall(): void
    {
        $cleared = 0;
        $total = 0;

        foreach ($this->clearanceStatus as $row) {
            if ($row['status'] === 'not_required') {
                continue;
            }
            $total++;
            if ($row['status'] === 'cleared') {
                $cleared++;
            }
        }

        $this->clearedCount = $cleared;
        $this->totalCount = $total;

        if ($total === 0) {
            $this->overallStatus = 'pending';

            return;
        }

        if ($cleared === $total) {
            $this->overallStatus = 'cleared';
        } elseif ($cleared > 0) {
            $this->overallStatus = 'partial';
        } else {
            $this->overallStatus = 'pending';
        }
    }

    public function render(): View
    {
        return view('livewire.student.student-clearance-page')
            ->layout('components.layouts.student', [
                'title' => __('Clearance'),
                'headerTitle' => __('Graduation Clearance'),
                'headerDescription' => __('Track your graduation clearance requirements and departmental approvals.'),
            ]);
    }
}
