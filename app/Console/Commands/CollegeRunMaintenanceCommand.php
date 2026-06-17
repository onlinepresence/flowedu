<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Maintenance\AutoPromotionService;
use App\Services\Maintenance\EvaluationFormStatusService;
use App\Services\Maintenance\SemesterActiveStatusService;
use Illuminate\Console\Command;

class CollegeRunMaintenanceCommand extends Command
{
    protected $signature = 'college:maintenance
                            {task=all : One of evaluation, semester, promotion, or all}';

    protected $description = 'Run scheduled college maintenance (legacy auto-jobs parity).';

    public function handle(
        EvaluationFormStatusService $evaluation,
        SemesterActiveStatusService $semester,
        AutoPromotionService $promotion,
    ): int {
        $task = strtolower((string) $this->argument('task'));

        $ok = match ($task) {
            'evaluation' => $evaluation->run(),
            'semester' => $semester->run(),
            'promotion' => $promotion->run(),
            'all' => $evaluation->run() && $semester->run() && $promotion->run(),
            default => $this->invalidTask($task),
        };

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    private function invalidTask(string $task): bool
    {
        $this->error("Unknown task [{$task}]. Use evaluation, semester, promotion, or all.");

        return false;
    }
}
