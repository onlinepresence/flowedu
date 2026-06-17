<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_payments', function (Blueprint $table) {
            $table->id();
            $table->string('lastname');
            $table->string('othernames');
            $table->enum('class_level', ['100', '200', '300', '400']);
            $table->decimal('amount_paid', 8, 2);
            $table->decimal('balance', 8, 2)->default(0);
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_id')->constrained('results')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->restrictOnDelete();
            $table->decimal('class_score', 8, 2);
            $table->decimal('exam_score', 8, 2);
            $table->enum('status', ['approved', 'pending', 'rejected'])->default('pending');
            $table->timestamps();
        });

        Schema::create('graduations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('graduation_date');
            $table->unsignedInteger('academic_session_id');
            $table->foreign('academic_session_id')->references('id')->on('academic_sessions')->cascadeOnDelete();
            $table->foreignId('graduated_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->string('status', 32)->default('graduated');
            $table->timestamp('created_at')->nullable()->useCurrent();
        });

        Schema::create('medical_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->text('medical_conditions')->nullable();
            $table->text('allergies')->nullable();
            $table->text('medications')->nullable();
            $table->text('immunization_records')->nullable();
            $table->text('emergency_contacts')->nullable();
            $table->unsignedInteger('academic_session_id')->nullable();
            $table->foreign('academic_session_id')->references('id')->on('academic_sessions')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('parent_guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('name');
            $table->string('relationship');
            $table->string('address')->nullable();
            $table->string('phone_number');
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->integer('from_level');
            $table->integer('to_level');
            $table->unsignedInteger('academic_session_id')->nullable();
            $table->foreign('academic_session_id')->references('id')->on('academic_sessions')->nullOnDelete();
            $table->foreignId('promoted_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->date('promotion_date');
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->unique(
                ['student_id', 'academic_session_id', 'from_level', 'to_level'],
                'uniq_promotion_session_transition'
            );
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('category', 100)->nullable();
            $table->string('setting_key')->nullable()->unique();
            $table->text('setting_value')->nullable();
            $table->enum('data_type', ['string', 'integer', 'boolean', 'json', 'array'])->default('string');
            $table->text('description')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('student_clearances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('department_key', 64);
            $table->enum('status', ['pending', 'cleared', 'not_required'])->default('pending');
            $table->foreignId('cleared_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->dateTime('cleared_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();

            $table->unique(['student_id', 'department_key'], 'uniq_student_department_clearance');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_clearances');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('parent_guardians');
        Schema::dropIfExists('medical_histories');
        Schema::dropIfExists('graduations');
        Schema::dropIfExists('grades');
        Schema::dropIfExists('fee_payments');
    }
};
