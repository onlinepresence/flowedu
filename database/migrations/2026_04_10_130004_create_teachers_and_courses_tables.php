<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('lastname')->nullable();
            $table->string('othernames')->nullable();
            $table->string('ghana_card')->nullable();
            $table->string('profile_pic')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('nationality', 100)->nullable();
            $table->string('contact_address')->nullable();
            $table->string('phone_number', 30)->nullable();
            $table->string('staff_id', 100)->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete()->cascadeOnUpdate();
            $table->string('rank', 100)->nullable();
            $table->string('qualification', 100)->nullable();
            $table->string('specialization')->nullable();
            $table->enum('employment_type', ['Full-time', 'Part-time', 'Visiting'])->nullable();
            $table->unsignedInteger('years_experience')->default(0);
            $table->string('cv')->nullable();
            $table->string('certificate')->nullable();
            $table->string('id_document')->nullable();
            $table->string('emergency_name')->nullable();
            $table->string('emergency_phone', 30)->nullable();
            $table->text('research_interests')->nullable();
            $table->date('date_of_appointment')->nullable();
            $table->timestamps();
            $table->boolean('password_reset_required')->default(true);
            $table->tinyInteger('is_onboarded')->default(0);
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->enum('course_semester', ['1', '2']);
            $table->enum('year_level', ['1', '2', '3', '4']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
        Schema::dropIfExists('teachers');
    }
};
