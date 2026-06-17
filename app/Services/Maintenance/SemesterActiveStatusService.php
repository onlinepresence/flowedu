<?php

declare(strict_types=1);

namespace App\Services\Maintenance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Ports legacy {@see process_session_status_updates()} from includes/auto-jobs.php
 * (semester active flag from calendar dates).
 */
final class SemesterActiveStatusService
{
    public function run(): bool
    {
        try {
            $today = now()->toDateString();

            $driver = DB::getDriverName();

            if ($driver === 'mysql') {
                $off = DB::affectingStatement(
                    'UPDATE semesters SET is_active = 0
                     WHERE is_active != 0
                       AND (
                         start_date IS NULL
                         OR end_date IS NULL
                         OR start_date > ?
                         OR end_date < ?
                       )',
                    [$today, $today]
                );

                $on = DB::affectingStatement(
                    'UPDATE semesters SET is_active = 1
                     WHERE start_date IS NOT NULL
                       AND end_date IS NOT NULL
                       AND start_date <= ?
                       AND end_date >= ?
                       AND is_active != 1',
                    [$today, $today]
                );
            } else {
                // SQLite / others: date comparison via query builder
                $off = DB::table('semesters')
                    ->where('is_active', '!=', 0)
                    ->where(function ($q) use ($today): void {
                        $q->whereNull('start_date')
                            ->orWhereNull('end_date')
                            ->orWhereDate('start_date', '>', $today)
                            ->orWhereDate('end_date', '<', $today);
                    })
                    ->update(['is_active' => 0]);

                $on = DB::table('semesters')
                    ->whereNotNull('start_date')
                    ->whereNotNull('end_date')
                    ->whereDate('start_date', '<=', $today)
                    ->whereDate('end_date', '>=', $today)
                    ->where('is_active', '!=', 1)
                    ->update(['is_active' => 1]);
            }

            Log::info('college.maintenance.semesters', [
                'deactivated' => $off,
                'activated' => $on,
                'date' => $today,
            ]);

            return true;
        } catch (Throwable $e) {
            Log::error('college.maintenance.semesters_failed', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
