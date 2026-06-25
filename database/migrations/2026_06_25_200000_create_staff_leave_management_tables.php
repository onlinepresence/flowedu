<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->integer('max_leave_days');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('staff_leave_type_id')->nullable()->constrained('staff_leave_types')->nullOnDelete();
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('staff_leave_type_id')->constrained('staff_leave_types')->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('requested_days');
            $table->string('status', 32)->default('pending'); // pending, approved, rejected
            $table->text('reason')->nullable();
            $table->boolean('is_emergency')->default(false);
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedInteger('academic_session_id')->nullable();
            $table->foreign('academic_session_id')->references('id')->on('academic_sessions')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['staff_leave_type_id']);
            $table->dropColumn('staff_leave_type_id');
        });
        Schema::dropIfExists('staff_leave_types');
    }
};
