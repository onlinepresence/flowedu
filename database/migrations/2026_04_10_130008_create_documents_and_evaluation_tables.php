<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('related_type', ['student', 'teacher', 'admin']);
            $table->enum('context', ['profile', 'assignment', 'course', 'other'])->default('other');
            $table->string('title')->nullable();
            $table->string('file_path');
            $table->string('file_type', 20)->nullable();
            $table->longText('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent()->nullable();
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `documents` MODIFY `metadata` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL');
        }

        Schema::create('evaluation_forms', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('academic_year', 9)->nullable();
            $table->string('unique_code', 50)->nullable()->unique();
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->enum('control_type', ['auto', 'manual'])->default('auto');
            $table->boolean('is_active')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->foreignId('last_edited_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
        });

        Schema::create('evaluation_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->nullable()->constrained('evaluation_forms')->cascadeOnDelete()->cascadeOnUpdate();
            $table->text('question_text')->nullable();
            $table->integer('question_order')->nullable();
            $table->enum('rating_type', ['scale_5', 'scale_10', 'text_short', 'text_long', 'boolean', 'select_single', 'select_multiple']);
            $table->boolean('is_required')->default(true);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->dateTime('deleted_at')->nullable();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->json('options_json')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('last_edited_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('evaluation_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->nullable()->constrained('evaluation_forms')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('student_id')->nullable()->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('student_department_id')->nullable()->constrained('departments')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('response_code', 50)->nullable()->unique();
            $table->enum('status', ['draft', 'submitted'])->default('draft');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('submitted_at')->nullable();

            $table->unique(['form_id', 'student_id'], 'idx_unique_student_response');
        });

        Schema::create('response_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('response_id')->nullable()->constrained('evaluation_responses')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('question_id')->nullable()->constrained('evaluation_questions')->restrictOnDelete()->cascadeOnUpdate();
            $table->text('question_text_snapshot')->nullable();
            $table->integer('answer_value')->nullable();
            $table->text('answer_text')->nullable();
            $table->dateTime('created_at')->useCurrent();

            $table->unique(['response_id', 'question_id'], 'idx_unique_answer_per_question');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('response_details');
        Schema::dropIfExists('evaluation_responses');
        Schema::dropIfExists('evaluation_questions');
        Schema::dropIfExists('evaluation_forms');
        Schema::dropIfExists('documents');
    }
};
