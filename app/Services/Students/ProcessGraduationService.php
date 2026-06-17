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
     * @return int Number of students graduated
     */
    public function run(
        int $academicSessionId,
        string $level,
        ?int $programId,
        string $graduationDate,
        int $graduatedByUserId,
    ): int {
        if ($level !== '400') {
            return 0;
        }

        $graduated = 0;

        $students = Student::query()
            ->where('approved', true)
            ->where('graduated', false)
            ->where('current_year', $level)
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
