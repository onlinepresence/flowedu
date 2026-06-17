<?php

declare(strict_types=1);

namespace App\Actions\Grading;

use App\Models\GradePoint;
use App\Models\Result;

class UpdateResultScoreAction
{
    public function execute(Result $result, ?float $score, ?int $enteredByUserId): void
    {
        if ($score === null) {
            $result->forceFill([
                'score' => null,
                'grade' => null,
                'grade_points' => null,
                'entered_by' => $enteredByUserId,
                'entered_date' => now()->toDateString(),
            ])->save();

            return;
        }

        $gradePoint = GradePoint::query()
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->orderByDesc('min_score')
            ->first();

        $result->forceFill([
            'score' => $score,
            'grade' => $gradePoint?->grade,
            'grade_points' => $gradePoint?->points,
            'entered_by' => $enteredByUserId,
            'entered_date' => now()->toDateString(),
        ])->save();
    }
}
