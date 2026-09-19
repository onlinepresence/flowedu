<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Models\Student;
use Illuminate\Support\Facades\DB;

final class ProcessGraduationService
{
    /**
     * Mark matching students as graduated and insert graduation rows.
     *
     * Only terminal cohorts graduate: the requested level must equal the
     * program's max (program_length x 100), so diploma finalists (200) and
     * degree finalists (400) each graduate through this same path.
     *
     * @return int Number of students graduated
     */
    public function run(
        int $academicSessionId,
        string $level,
        ?int $programId,
        string $graduationDate,
        int $graduatedByUserId,
    ): int {
        if (! in_array($level, ['100', '200', '300', '400'], true)) {
            return 0;
        }

        $graduated = 0;

        // Terminal cohort only: program_length is an integer column, so derive
        // it in PHP ('400' -> 4) and compare integers — portable across
        // drivers (arithmetic-on-enum comparisons misbehave on some builds).
        $programYears = (int) ((int) $level / 100);

        $students = Student::query()
            ->where('approved', true)
            ->where('graduated', false)
            ->where('current_year', $level)
            ->whereHas('program', fn ($q) => $q->where('program_length', $programYears))
            ->when($programId !== null && $programId > 0, fn ($q) => $q->where('program_id', $programId))
            ->get();

        foreach ($students as $student) {
            DB::transaction(function () use ($student, $academicSessionId, $graduationDate, $graduatedByUserId, &$graduated): void {
                DB::table('graduations')->insert([
                    'student_id' => $student->id,
                    'graduation_date' => $graduationDate,
                    'academic_session_id' => $academicSessionId,
                    'graduated_by' => $graduatedByUserId,
                    'status' => 'graduated',
                    'created_at' => now(),
                ]);

                $student->graduated = true;
                $student->save();

                $graduated++;
            });
        }

        return $graduated;
    }
}
