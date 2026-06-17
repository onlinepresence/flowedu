<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('disciplinary_records', function (Blueprint $table) {
            $table->unsignedInteger('academic_session_id')->nullable()->after('program_id');
            $table->foreign('academic_session_id')->references('id')->on('academic_sessions')->onDelete('set null');
        });

        Schema::table('student_clearances', function (Blueprint $table) {
            $table->unsignedInteger('academic_session_id')->nullable()->after('student_id');
            $table->foreign('academic_session_id')->references('id')->on('academic_sessions')->onDelete('set null');
        });

        Schema::table('scholarship_recipients', function (Blueprint $table) {
            $table->unsignedInteger('academic_session_id')->nullable()->after('student_id');
            $table->foreign('academic_session_id')->references('id')->on('academic_sessions')->onDelete('set null');
        });

        Schema::table('fee_payments', function (Blueprint $table) {
            $table->unsignedInteger('academic_session_id')->nullable()->after('student_id');
            $table->unsignedInteger('semester_id')->nullable()->after('academic_session_id');
            
            $table->foreign('academic_session_id')->references('id')->on('academic_sessions')->onDelete('set null');
            $table->foreign('semester_id')->references('id')->on('semesters')->onDelete('set null');
        });

        Schema::table('teacher_attendance_sheets', function (Blueprint $table) {
            $table->unsignedInteger('academic_session_id')->nullable()->after('course_id');
            $table->unsignedInteger('semester_id')->nullable()->after('academic_session_id');
            
            $table->foreign('academic_session_id')->references('id')->on('academic_sessions')->onDelete('set null');
            $table->foreign('semester_id')->references('id')->on('semesters')->onDelete('set null');
        });

        Schema::table('course_materials', function (Blueprint $table) {
            $table->unsignedInteger('academic_session_id')->nullable()->after('teacher_id');
            $table->unsignedInteger('semester_id')->nullable()->after('academic_session_id');
            
            $table->foreign('academic_session_id')->references('id')->on('academic_sessions')->onDelete('set null');
            $table->foreign('semester_id')->references('id')->on('semesters')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_materials', function (Blueprint $table) {
            $table->dropForeign(['academic_session_id']);
            $table->dropForeign(['semester_id']);
            $table->dropColumn(['academic_session_id', 'semester_id']);
        });

        Schema::table('teacher_attendance_sheets', function (Blueprint $table) {
            $table->dropForeign(['academic_session_id']);
            $table->dropForeign(['semester_id']);
            $table->dropColumn(['academic_session_id', 'semester_id']);
        });

        Schema::table('fee_payments', function (Blueprint $table) {
            $table->dropForeign(['academic_session_id']);
            $table->dropForeign(['semester_id']);
            $table->dropColumn(['academic_session_id', 'semester_id']);
        });

        Schema::table('scholarship_recipients', function (Blueprint $table) {
            $table->dropForeign(['academic_session_id']);
            $table->dropColumn('academic_session_id');
        });

        Schema::table('student_clearances', function (Blueprint $table) {
            $table->dropForeign(['academic_session_id']);
            $table->dropColumn('academic_session_id');
        });

        Schema::table('disciplinary_records', function (Blueprint $table) {
            $table->dropForeign(['academic_session_id']);
            $table->dropColumn('academic_session_id');
        });
    }
};
