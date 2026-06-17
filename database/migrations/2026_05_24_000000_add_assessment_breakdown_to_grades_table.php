<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->decimal('attendance_score', 8, 2)->nullable()->after('teacher_id');
            $table->decimal('midsem_score', 8, 2)->nullable()->after('attendance_score');
            $table->decimal('project_score', 8, 2)->nullable()->after('midsem_score');
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropColumn(['attendance_score', 'midsem_score', 'project_score']);
        });
    }
};
