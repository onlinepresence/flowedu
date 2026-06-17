<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('index_number');
            $table->string('admission_index');
            $table->string('othernames')->nullable();
            $table->string('firstname')->nullable();
            $table->string('lastname');
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->date('date_of_birth');
            $table->string('gender');
            $table->string('nationality');
            $table->string('religion')->nullable();
            $table->string('denomination', 100)->nullable();
            $table->enum('current_year', ['100', '200', '300', '400'])->default('100');
            $table->text('contact_address');
            $table->string('phone_number');
            $table->date('admission_date')->nullable();
            $table->boolean('graduated')->default(false);
            $table->string('allergy')->nullable();
            $table->string('insurance_number')->nullable();
            $table->string('ghana_card')->nullable();
            $table->string('account_bank')->nullable();
            $table->string('account_number')->nullable();
            $table->string('ssnit_number')->nullable();
            $table->enum('disability_status', ['no', 'yes'])->default('no');
            $table->string('disability_type')->nullable();
            $table->integer('enroled_at')->nullable();
            $table->integer('completes_at')->nullable();
            $table->integer('graduated_at')->nullable();
            $table->foreignId('hall_id')->constrained('halls')->restrictOnDelete();
            $table->string('profile_pic');
            $table->boolean('is_new')->default(true);
            $table->boolean('approved')->default(false);
            $table->timestamps();

            $table->unique('index_number');
            $table->unique('admission_index');
            $table->unique('ghana_card');
        });

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('lastname')->nullable();
            $table->string('othernames')->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('profile_pic')->nullable();
            $table->string('position_title')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('faculty_id')->nullable()->constrained('faculties')->nullOnDelete();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->date('date_of_appointment')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ghana_card')->nullable();
            $table->foreignId('type')->nullable()->constrained('user_roles');
            $table->timestamps();

            $table->unique('ghana_card');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
        Schema::dropIfExists('students');
    }
};
