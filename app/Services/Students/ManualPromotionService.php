<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Models\AcademicSession;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ManualPromotionService
{
    /**
     * Students eligible for preview: approved, not graduated, at from_level, optional program filter.
     * If $restrictToStudentIds is non-empty, only those IDs are returned (must still match filters).
     *
     * @param  list<int>  $restrictToStudentIds
     * @return Collection<int, array{id:int, index_number:string, fullname:string, current_year:string, program_name:string}>
     */
    public function preview(
        string $fromLevel,
        ?int $programId,
        array $restrictToStudentIds,
    ): Collection {
        $q = Student::query()
            ->with('program')
            ->where('approved', true)
            ->where('graduated', false)
            ->where('current_year', $fromLevel)
            ->when($programId !== null && $programId > 0, fn ($q) => $q->where('program_id', $programId));

        if ($restrictToStudentIds !== []) {
            $q->whereIn('id', $restrictToStudentIds);
        }

        return $q
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get()
            ->map(fn (Student $s): array => [
                'id' => $s->id,
                'index_number' => $s->index_number,
                'fullname' => trim(implode(' ', array_filter([$s->firstname, $s->othernames, $s->lastname]))),
                'current_year' => (string) $s->current_year,
                'program_name' => (string) ($s->program?->name ?? ''),
            ]);
    }

    /**
     * @param  list<int>  $studentIds
     */
    public function confirm(
        AcademicSession $session,
        string $fromLevel,
        string $toLevel,
        ?int $programId,
        array $studentIds,
        int $promotedByUserId,
    ): int {
        if ($studentIds === []) {
            return 0;
        }

        $sessionId = (int) $session->getKey();
        $promoted = 0;

        $students = Student::query()
            ->whereIn('id', $studentIds)
            ->where('approved', true)
            ->where('graduated', false)
            ->where('current_year', $fromLevel)
            ->when($programId !== null && $programId > 0, fn ($q) => $q->where('program_id', $programId))
            ->get();

        foreach ($students as $student) {
            $exists = DB::table('promotions')
                ->where('student_id', $student->id)
                ->where('academic_session_id', $sessionId)
                ->where('from_level', (int) $fromLevel)
                ->where('to_level', (int) $toLevel)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::transaction(function () use ($student, $sessionId, $fromLevel, $toLevel, $promotedByUserId, &$promoted): void {
                DB::table('promotions')->insert([
                    'student_id' => $student->id,
                    'from_level' => (int) $fromLevel,
                    'to_level' => (int) $toLevel,
                    'academic_session_id' => $sessionId,
                    'promoted_by' => $promotedByUserId,
                    'promotion_date' => now()->toDateString(),
                    'created_at' => now(),
                ]);

                $student->current_year = $toLevel;
                $student->save();

                $promoted++;
            });
        }

        return $promoted;
    }
}
