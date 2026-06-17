<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Result;
use App\Models\ResultSlip;
use App\Models\GradePoint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ResultSlipApprovalService
{
    public function approve(ResultSlip $slip, int $adminUserId): void
    {
        DB::transaction(function () use ($slip, $adminUserId) {
            $slip->update([
                'status' => ResultSlip::STATUS_APPROVED,
                'approved_by' => $adminUserId,
                'approved_at' => now(),
            ]);

            $gradeScale = GradePoint::query()->orderByDesc('min_score')->get();

            foreach ($slip->grades as $grade) {
                $totalScore = floatval($grade->class_score) + floatval($grade->exam_score);

                // Find grade points mapping
                $gradeLetter = 'F';
                $gradePts = 0.0;
                foreach ($gradeScale as $gp) {
                    if ($totalScore >= $gp->min_score && $totalScore <= $gp->max_score) {
                        $gradeLetter = $gp->grade;
                        $gradePts = (float) $gp->points;
                        break;
                    }
                }

                Result::query()->updateOrCreate(
                    [
                        'student_id' => $grade->student_id,
                        'course_id' => $slip->course_id,
                        'academic_session_id' => $slip->academic_session_id,
                    ],
                    [
                        'score' => $totalScore,
                        'grade' => $gradeLetter,
                        'grade_points' => $gradePts,
                        'entered_by' => $adminUserId,
                        'entered_date' => now(),
                        'teacher_id' => $slip->teacher_id,
                        'result_token' => 'RES-' . Str::random(12),
                        'result_slip_id' => $slip->id,
                        'admin_amended' => false,
                    ]
                );
            }
        });
    }

    public function reject(ResultSlip $slip, ?string $comments): void
    {
        DB::transaction(function () use ($slip, $comments) {
            $slip->update([
                'status' => ResultSlip::STATUS_REJECTED,
                'review_comments' => $comments,
                'approved_by' => null,
                'approved_at' => null,
            ]);

            // If an approved slip is rejected, its corresponding results should be deleted
            Result::query()->where('result_slip_id', $slip->id)->delete();
        });
    }
}
