<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_licences', function (Blueprint $table) {
            $table->dropColumn('package_tier');

            $table->boolean('core_timetable')->default(true);
            $table->boolean('core_attendance')->default(true);
            $table->boolean('core_memos')->default(true);
            $table->boolean('core_impersonation')->default(true);

            $table->boolean('module_finance')->default(false);
            $table->boolean('module_staff_hr')->default(false);
            $table->boolean('module_reports')->default(false);
            $table->boolean('module_evaluations')->default(false);
            $table->boolean('module_student_welfare')->default(false);
            $table->boolean('module_progression')->default(false);
            $table->boolean('module_system_admin')->default(false);
            $table->boolean('module_teacher_tools')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('school_licences', function (Blueprint $table) {
            $table->string('package_tier')->default('complete');

            $table->dropColumn([
                'core_timetable',
                'core_attendance',
                'core_memos',
                'core_impersonation',
                'module_finance',
                'module_staff_hr',
                'module_reports',
                'module_evaluations',
                'module_student_welfare',
                'module_progression',
                'module_system_admin',
                'module_teacher_tools',
            ]);
        });
    }
};
