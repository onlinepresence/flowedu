<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('teacher_portal_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('permissions')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Seed default roles
        DB::table('teacher_portal_roles')->insert([
            [
                'name' => 'lecturer',
                'display_name' => 'Lecturer',
                'permissions' => json_encode(['courses', 'students', 'assessments', 'communication']),
                'description' => 'Standard teaching faculty with full access to portal features.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'coordinator',
                'display_name' => 'Programme Coordinator',
                'permissions' => json_encode(['courses', 'students', 'assessments']),
                'description' => 'Academic coordinator managing courses, student attendance, and grades.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'tutor',
                'display_name' => 'Tutor / Teaching Assistant',
                'permissions' => json_encode(['courses', 'students']),
                'description' => 'Tutor with access to courses and student records but no grade entry.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_portal_roles');
    }
};
