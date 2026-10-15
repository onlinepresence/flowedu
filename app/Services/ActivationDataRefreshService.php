<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Admin;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Fresh-slate clearing for trial activation ("start fresh" switch).
 *
 * Deletes business records table-by-table from the LIVE schema (so new
 * tables are covered automatically) except an explicit keep-list, inside
 * one transaction with foreign keys off. The keeper (the signed-in owner
 * activating) and the school identity + just-activated licence row always
 * survive. Uploaded files on disk and queued jobs are left alone.
 */
final class ActivationDataRefreshService
{
    /**
     * Tables whose rows are NEVER touched. Everything else present in the
     * schema is business data and gets cleared (users/admins are scoped to
     * the keeper separately below).
     *
     * @var list<string>
     */
    public const KEEP_TABLES = [
        // Framework / infra.
        'migrations',
        'jobs',
        'job_batches',
        'failed_jobs',
        'cache',
        'sessions',
        'password_reset_tokens',
        'personal_access_tokens',
        // School identity + licence + app config.
        'schools',
        'school_licences',
        'settings',
        // Auth + reference scaffolding (rebuilt only by seeders).
        'user_roles',
        'admin_types',
        'teacher_roles',
        'teacher_portal_roles',
        'staff_roles',
        'products',
        'user_file_categories',
        'staff_leave_types',
    ];

    /**
     * @return array{ok: bool, message?: string, cleared?: array<string, int>, total?: int}
     */
    public function refresh(School $school, int $keeperId): array
    {
        $keeper = User::query()->find($keeperId);
        if ($keeper === null) {
            return ['ok' => false, 'message' => __('Your login was not found — sign in again, then activate.')];
        }

        $cleared = [];

        try {
            // Converge by passes: delete every target table, retrying blocked
            // ones after their children clear. Works identically on MySQL and
            // SQLite (which ignores foreign_key_checks inside transactions,
            // so a pragma-off approach cannot be trusted there). A pass with
            // zero progress means a reference cycle — fail safe with a
            // rollback instead of looping forever.
            DB::transaction(function () use ($keeperId, &$cleared): void {
                $remaining = $this->targetTables();
                $maxPasses = max(10, count($remaining) + 5);

                for ($pass = 0; $pass < $maxPasses && $remaining !== []; $pass++) {
                    $progress = false;

                    foreach ($remaining as $i => $table) {
                        try {
                            $count = $table === 'users'
                                ? User::query()->where('id', '!=', $keeperId)->delete()
                                : ($table === 'admins'
                                    ? Admin::query()->where('user_id', '!=', $keeperId)->delete()
                                    : DB::table($table)->delete());
                        } catch (\Throwable) {
                            continue; // Still referenced — retry on the next pass.
                        }

                        unset($remaining[$i]);
                        $progress = true;

                        if ($count > 0) {
                            $cleared[$table] = (int) $count;
                        }
                    }

                    if (! $progress) {
                        throw new \RuntimeException(
                            'Activation refresh could not clear ['.implode(',', $remaining).'] (still referenced).'
                        );
                    }
                }

                if ($remaining !== []) {
                    throw new \RuntimeException('Activation refresh did not converge.');
                }
            });
        } catch (\Throwable $e) {
            report($e);

            return ['ok' => false, 'message' => __('Could not clear existing records — nothing was changed. Try again.')];
        }

        $total = array_sum($cleared);

        Log::info('activation.data_refresh', [
            'school_id' => $school->id,
            'keeper_id' => $keeperId,
            'tables' => count($cleared),
            'records' => $total,
        ]);

        return ['ok' => true, 'cleared' => $cleared, 'total' => $total];
    }

    /**
     * Live schema tables minus the keep-list (plus users/admins, which are
     * keeper-scoped by the caller). Internal SQLite bookkeeping excluded.
     *
     * @return list<string>
     */
    private function targetTables(): array
    {
        try {
            $tables = Schema::getTables();
        } catch (\Throwable) {
            return [];
        }

        $names = [];
        foreach ($tables as $table) {
            $name = is_array($table) ? ($table['name'] ?? null) : ($table->name ?? null);
            if (! is_string($name) || $name === '') {
                continue;
            }
            // Strip schema qualification (e.g. pgsql "public.users").
            if (str_contains($name, '.')) {
                $name = (string) substr($name, (int) strrpos($name, '.') + 1);
            }
            $names[] = $name;
        }

        return array_values(array_filter($names, static fn (string $name): bool => $name !== 'sqlite_sequence'
            && ! in_array($name, self::KEEP_TABLES, true)
            && Schema::hasTable($name)));
    }
}
