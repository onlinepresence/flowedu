<?php

declare(strict_types=1);

namespace App\Services\Maintenance;

use App\Models\AcademicSession;
use App\Models\Promotion;
use App\Models\Setting;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Ports the intended behaviour of legacy {@see process_auto_promotion()} (includes/auto-jobs.php).
 * The legacy worker called this then exited; debug code after the query was unreachable.
 */
final class AutoPromotionService
{
    public function run(): bool
    {
        if ($this->promotionMode() !== 'auto') {
            return true;
        }

        $session = AcademicSession::query()->where('is_current', true)->first();

        if ($session === null) {
            Log::info('college.maintenance.promotion_skipped', ['reason' => 'no_current_session']);

            return true;
        }

        if ($session->end_date !== null && $session->end_date->lt(now()->startOfDay())) {
            Log::info('college.maintenance.promotion_skipped', ['reason' => 'session_ended']);

            return true;
        }

        $sessionId = (int) $session->getKey();

        try {
            $promoted = 0;

            Student::query()
                ->where('approved', true)
                ->where('graduated', false)
                ->whereNotNull('program_id')
                ->with('program')
                ->chunkById(100, function ($students) use ($sessionId, &$promoted): void {
                    foreach ($students as $student) {
                        $program = $student->program;
                        if ($program === null || $program->program_length === null) {
                            continue;
                        }

                        $current = (int) $student->current_year;
                        $maxYear = (int) $program->program_length * 100;

                        if ($current <= 0 || $current >= $maxYear) {
                            continue;
                        }

                        $toLevel = $current + 100;
                        if ($toLevel > $maxYear) {
                            continue;
                        }

                        $exists = Promotion::query()
                            ->where('student_id', $student->id)
                            ->where('academic_session_id', $sessionId)
                            ->where('from_level', $current)
                            ->where('to_level', $toLevel)
                            ->exists();

                        if ($exists) {
                            continue;
                        }

                        DB::transaction(function () use ($student, $sessionId, $current, $toLevel, &$promoted): void {
                            DB::table('promotions')->insert([
                                'student_id' => $student->id,
                                'from_level' => $current,
                                'to_level' => $toLevel,
                                'academic_session_id' => $sessionId,
                                'promoted_by' => null,
                                'promotion_date' => now()->toDateString(),
                                'created_at' => now(),
                            ]);

                            $student->update(['current_year' => (string) $toLevel]);
                            $promoted++;
                        });
                    }
                });

            Log::info('college.maintenance.promotion', ['students_promoted' => $promoted]);

            return true;
        } catch (Throwable $e) {
            Log::error('college.maintenance.promotion_failed', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function promotionMode(): string
    {
        $raw = Setting::query()
            ->where('setting_key', 'students.promotion_mode')
            ->value('setting_value');

        return $raw === 'manual' ? 'manual' : 'auto';
    }
}
