<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\Models\School;
use App\Models\Student;
use RuntimeException;

/**
 * Legacy create_index_number() from includes/functions.php (school + year + department + padded id).
 */
final class AssignOfficialStudentIndexNumberAction
{
    public function execute(Student $student): string
    {
        $school = School::current();
        if ($school === null) {
            throw new RuntimeException('School context required to assign index number.');
        }

        $schoolPart = str_pad((string) (int) $school->id, 2, '0', STR_PAD_LEFT);
        $year = now()->format('y');
        $deptPart = str_pad((string) (int) ($student->department_id ?? 0), 2, '0', STR_PAD_LEFT);

        $n = (int) $student->id;

        do {
            $idPart = str_pad((string) $n, 4, '0', STR_PAD_LEFT);
            $candidate = $schoolPart.$year.$deptPart.$idPart;
            $taken = Student::query()
                ->where('index_number', $candidate)
                ->where('id', '!=', $student->id)
                ->exists();
            if (! $taken) {
                return $candidate;
            }
            $n++;
        } while (true);
    }
}
