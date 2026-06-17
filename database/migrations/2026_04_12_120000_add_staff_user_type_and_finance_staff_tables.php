<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy finance/staff tables from admin/submit.php + admin/ajax/finance.php.
 * Note: `payments` is separate from `fee_payments` (FeesIndex); unification is a future migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `users` MODIFY `type` ENUM('student', 'teacher', 'admin', 'staff') NOT NULL");
        }

        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->unsignedInteger('level');
            $table->unsignedInteger('session_id');
            $table->foreign('session_id')->references('id')->on('academic_sessions')->cascadeOnDelete();
            $table->decimal('tuition_fee', 12, 2)->default(0);
            $table->decimal('library_fee', 12, 2)->default(0);
            $table->decimal('lab_fee', 12, 2)->default(0);
            $table->decimal('medical_fee', 12, 2)->default(0);
            $table->decimal('sports_fee', 12, 2)->default(0);
            $table->decimal('examination_fee', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['program_id', 'level', 'session_id'], 'fee_structures_program_level_session_unique');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('fee_structure_id')->constrained('fee_structures')->cascadeOnDelete();
            $table->decimal('amount_paid', 12, 2);
            $table->string('payment_method', 64)->nullable();
            $table->date('payment_date')->nullable();
            $table->string('reference_number', 128)->nullable();
            $table->string('status', 32)->default('completed');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('scholarships', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['scholarship', 'grant']);
            $table->decimal('amount', 12, 2);
            $table->text('description')->nullable();
            $table->string('status', 32)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('scholarship_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scholarship_id')->constrained('scholarships')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->decimal('amount_awarded', 12, 2);
            $table->date('award_date')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();
        });

        Schema::create('teacher_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->unsignedInteger('level');
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->unsignedInteger('session_id');
            $table->foreign('session_id')->references('id')->on('academic_sessions')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('assigned_date')->nullable();
            $table->timestamps();

            $table->unique(['teacher_id', 'course_id', 'session_id'], 'teacher_assign_course_session_unique');
        });

        Schema::create('teacher_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->string('role', 128);
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->text('description')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('assigned_date')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();
        });

        Schema::create('staff_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->string('office', 191);
            $table->string('position_title', 191);
            $table->date('assignment_date')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('active');
            $table->timestamps();
        });

        Schema::create('staff_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 128);
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->text('description')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('assigned_date')->nullable();
            $table->string('status', 32)->default('active');
            $table->timestamps();
        });

        Schema::create('non_teaching_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('position', 191);
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->string('phone_number', 32);
            $table->string('status', 32)->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('non_teaching_staff');
        Schema::dropIfExists('staff_roles');
        Schema::dropIfExists('staff_assignments');
        Schema::dropIfExists('teacher_roles');
        Schema::dropIfExists('teacher_assignments');
        Schema::dropIfExists('scholarship_recipients');
        Schema::dropIfExists('scholarships');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('fee_structures');

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `users` MODIFY `type` ENUM('student', 'teacher', 'admin') NOT NULL");
        }
    }
};
