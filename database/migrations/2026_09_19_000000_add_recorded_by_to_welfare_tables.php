<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Attribute welfare records to the staff member who recorded them
     * (discipline/medical: Dean/VP office; teacher attendance: HOD batch).
     * Nullable so historical rows without an actor stay valid.
     */
    public function up(): void
    {
        Schema::table('disciplinary_records', function (Blueprint $table) {
            $table->foreignId('recorded_by')->nullable()->after('academic_session_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('medical_histories', function (Blueprint $table) {
            $table->foreignId('recorded_by')->nullable()->after('academic_session_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('teacher_attendance_sheets', function (Blueprint $table) {
            $table->foreignId('recorded_by')->nullable()->after('semester_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('teacher_attendance_sheets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recorded_by');
        });

        Schema::table('medical_histories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recorded_by');
        });

        Schema::table('disciplinary_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recorded_by');
        });
    }
};
