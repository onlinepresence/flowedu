<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add result_slip_id to grades
        Schema::table('grades', function (Blueprint $table) {
            $table->foreignId('result_slip_id')->nullable()->constrained('result_slips')->cascadeOnDelete();
        });

        // 2. Add result_slip_id and admin_amended to results
        Schema::table('results', function (Blueprint $table) {
            $table->foreignId('result_slip_id')->nullable()->constrained('result_slips')->nullOnDelete();
            $table->boolean('admin_amended')->default(false);
        });

        // 3. Backfill data
        $grades = DB::table('grades')
            ->leftJoin('results', 'grades.result_id', '=', 'results.id')
            ->join('students', 'grades.student_id', '=', 'students.id')
            ->select([
                'grades.id as grade_id',
                'grades.status',
                'grades.review_comments',
                'grades.teacher_id',
                'results.id as result_id',
                'results.course_id',
                'results.academic_session_id',
                'students.program_id',
                'students.current_year as level',
            ])
            ->get();

        $slips = [];
        foreach ($grades as $grade) {
            // Find course details for semester
            $course = null;
            if ($grade->course_id) {
                $course = DB::table('courses')->where('id', $grade->course_id)->first();
            }
            $semStr = $course ? $course->course_semester : 'Semester 1';
            $semesterVal = str_contains((string)$semStr, '2') ? 2 : 1;

            $teacherId = $grade->teacher_id;
            $programId = $grade->program_id;
            $courseId = $grade->course_id ?? 1; // Fallback if result is missing
            $sessionId = $grade->academic_session_id ?? 1;
            $level = $grade->level ?? '100';

            $key = "{$teacherId}-{$programId}-{$courseId}-{$sessionId}-{$level}-{$semesterVal}";

            if (!isset($slips[$key])) {
                $slipId = DB::table('result_slips')->insertGetId([
                    'slip_number' => 'SLIP-' . date('Y') . '-' . strtoupper(Str::random(6)),
                    'teacher_id' => $teacherId,
                    'program_id' => $programId,
                    'course_id' => $courseId,
                    'academic_session_id' => $sessionId,
                    'level' => $level,
                    'semester' => $semesterVal,
                    'status' => $grade->status ?? 'pending',
                    'review_comments' => $grade->review_comments,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $slips[$key] = $slipId;
            } else {
                $slipId = $slips[$key];
            }

            // Update grade
            DB::table('grades')->where('id', $grade->grade_id)->update([
                'result_slip_id' => $slipId
            ]);

            // Update result if exists
            if ($grade->result_id) {
                DB::table('results')->where('id', $grade->result_id)->update([
                    'result_slip_id' => $slipId
                ]);
            }
        }

        // 4. Clean up grades table columns
        Schema::table('grades', function (Blueprint $table) {
            $table->dropForeign(['result_id']);
            $table->dropColumn(['result_id', 'status', 'review_comments']);
        });
    }

    public function down(): void
    {
        // Add back status, review_comments, and result_id to grades
        Schema::table('grades', function (Blueprint $table) {
            $table->foreignId('result_id')->nullable()->constrained('results')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->text('review_comments')->nullable();
        });

        // Restore status and review_comments from result_slips if possible
        $grades = DB::table('grades')
            ->join('result_slips', 'grades.result_slip_id', '=', 'result_slips.id')
            ->select('grades.id', 'result_slips.status', 'result_slips.review_comments')
            ->get();

        foreach ($grades as $grade) {
            DB::table('grades')->where('id', $grade->id)->update([
                'status' => $grade->status,
                'review_comments' => $grade->review_comments,
            ]);
        }

        Schema::table('grades', function (Blueprint $table) {
            $table->dropForeign(['result_slip_id']);
            $table->dropColumn('result_slip_id');
        });

        Schema::table('results', function (Blueprint $table) {
            $table->dropForeign(['result_slip_id']);
            $table->dropColumn(['result_slip_id', 'admin_amended']);
        });
    }
};
