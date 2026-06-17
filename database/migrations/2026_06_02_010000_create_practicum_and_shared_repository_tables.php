<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('shared_lesson_plans');
        Schema::dropIfExists('teaching_practice_supervisions');

        Schema::create('teaching_practice_supervisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->unsignedInteger('academic_session_id');
            $table->foreign('academic_session_id')->references('id')->on('academic_sessions')->onDelete('cascade');
            $table->string('partnership_school');
            $table->string('status')->default('assigned'); // assigned, in_progress, evaluated
            $table->decimal('score', 5, 2)->nullable();
            $table->text('evaluation_notes')->nullable();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'academic_session_id'], 'tp_student_session_unique');
        });

        Schema::create('shared_lesson_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->integer('file_size');
            $table->timestamps();
        });

        // Add module column if it doesn't exist
        if (!Schema::hasColumn('school_licences', 'module_practicum')) {
            Schema::table('school_licences', function (Blueprint $table) {
                $table->boolean('module_practicum')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('school_licences', 'module_practicum')) {
            Schema::table('school_licences', function (Blueprint $table) {
                $table->dropColumn('module_practicum');
            });
        }

        Schema::dropIfExists('shared_lesson_plans');
        Schema::dropIfExists('teaching_practice_supervisions');
    }
};
