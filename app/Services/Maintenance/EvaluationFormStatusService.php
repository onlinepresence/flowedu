<?php

declare(strict_types=1);

namespace App\Services\Maintenance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Ports legacy {@see process_evaluation_status_updates()} from includes/auto-jobs.php.
 * Uses raw SQL so closed forms can use is_active = -1 (legacy tri-state); avoid Eloquent
 * boolean cast on {@see EvaluationForm} when writing.
 */
final class EvaluationFormStatusService
{
    public function run(): bool
    {
        try {
            $now = now();

            $closed = DB::update(
                "UPDATE evaluation_forms SET is_active = -1, updated_at = ? WHERE end_time < ? AND is_active != -1 AND control_type = 'auto'",
                [$now, $now]
            );

            $opened = DB::update(
                "UPDATE evaluation_forms SET is_active = 1, updated_at = ? WHERE start_time <= ? AND end_time >= ? AND is_active != 1 AND control_type = 'auto'",
                [$now, $now, $now]
            );

            Log::info('college.maintenance.evaluation', [
                'closed_rows' => $closed,
                'opened_rows' => $opened,
            ]);

            return true;
        } catch (Throwable $e) {
            Log::error('college.maintenance.evaluation_failed', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
