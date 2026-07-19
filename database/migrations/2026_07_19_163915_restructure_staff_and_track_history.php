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
        // 1. Add office column to admins table
        Schema::table('admins', function (Blueprint $table) {
            $table->string('office')->nullable()->after('position_title');
        });

        // 2. Drop redundant tables safely
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('staff_assignments');
        Schema::dropIfExists('staff_roles');
        Schema::dropIfExists('non_teaching_staff');
        Schema::enableForeignKeyConstraints();

        // 3. Create office_assignment_histories table
        Schema::create('office_assignment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('user_roles')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('faculty_id')->nullable()->constrained('faculties')->nullOnDelete();
            $table->string('office')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->string('status')->default('active'); // active, ended
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('office_assignment_histories');

        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn('office');
        });

        // Re-creating dropped tables for safety during rollback
        Schema::create('non_teaching_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('lastname');
            $table->string('othernames');
            $table->string('phone_number')->nullable();
            $table->string('gender')->nullable();
            $table->string('position_title')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('staff_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->string('office');
            $table->string('position_title');
            $table->date('assignment_date')->nullable();
            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('staff_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('users')->cascadeOnDelete();
            $table->string('role');
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->text('description')->nullable();
            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete();
            $table->date('assigned_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }
};
