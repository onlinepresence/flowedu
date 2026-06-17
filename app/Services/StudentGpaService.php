<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Result;
use App\Models\Student;

/**
 * CGPA from published grade points with unit weight per course (no credit_hours on courses yet).
 */
final class StudentGpaService
{
    /**
     * @return array{cgpa: string, credit_hours: int, points: float}
     */
    public function statsForStudent(Student $student): array
    {
        $cnt = (int) Result::query()
            ->where('student_id', $student->id)
            ->whereNotNull('grade_points')
            ->count();
        $pts = (float) Result::query()
            ->where('student_id', $student->id)
            ->whereNotNull('grade_points')
            ->sum('grade_points');
        $cgpa = $cnt > 0 ? $pts / $cnt : 0.0;

        return [
            'cgpa' => number_format($cgpa, 2, '.', ''),
            'credit_hours' => $cnt,
            'points' => $pts,
        ];
    }
}
