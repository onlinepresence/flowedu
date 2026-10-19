<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('students')
            ->whereNull('department_id')
            ->whereNotNull('program_id')
            ->orderBy('id')
            ->chunkById(200, function ($students): void {
                $departmentIds = DB::table('programs')
                    ->whereIn('id', $students->pluck('program_id')->unique()->all())
                    ->pluck('department_id', 'id');

                foreach ($students as $student) {
                    $departmentId = $departmentIds[$student->program_id] ?? null;
                    if ($departmentId === null) {
                        continue;
                    }

                    DB::table('students')
                        ->where('id', $student->id)
                        ->update(['department_id' => $departmentId]);
                }
            });
    }

    public function down(): void
    {
        // Data backfill only; no schema changes to reverse.
    }
};
