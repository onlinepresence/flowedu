<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_points', function (Blueprint $table) {
            $table->id();
            $table->string('grade', 32);
            $table->double('min_score');
            $table->double('max_score');
            $table->double('points');
            $table->timestamps();

            $table->unique('grade');
        });

        Schema::create('disciplinary_records', function (Blueprint $table) {
            $table->id();
            $table->string('index_number');
            $table->string('fullname');
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->text('offense');
            $table->text('action_taken');
            $table->text('comments')->nullable();
            $table->date('date_of_action');
            $table->date('return_date')->nullable();
            $table->boolean('return_status')->default(false);
            $table->timestamps();
        });

        Schema::create('academic_information', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->enum('class_level', ['100', '200', '300', '400']);
            $table->string('section')->nullable();
            $table->string('academic_session');
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->string('major_field')->nullable();
            $table->decimal('gpa', 8, 2);
            $table->integer('attendance_record')->default(0);
            $table->foreignId('result_id')->constrained('results')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('activity_name');
            $table->string('role');
            $table->date('participation_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
        Schema::dropIfExists('academic_information');
        Schema::dropIfExists('disciplinary_records');
        Schema::dropIfExists('grade_points');
    }
};
