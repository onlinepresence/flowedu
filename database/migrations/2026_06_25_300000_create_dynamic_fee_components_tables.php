<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_components', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('default_percentage', 5, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('fee_structure_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_structure_id')->constrained('fee_structures')->cascadeOnDelete();
            $table->foreignId('fee_component_id')->constrained('fee_components')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });

        Schema::table('fee_structures', function (Blueprint $table) {
            $table->unsignedInteger('semester_id')->nullable();
            $table->foreign('semester_id')->references('id')->on('semesters')->nullOnDelete();

            // Replace previous unique index with a semester-aware one
            // We create the new unique index first so MySQL has a covering index for program_id, then drop the old one.
            $table->unique(['program_id', 'level', 'session_id', 'semester_id'], 'fee_structures_prog_lvl_sess_sem_unique');
            $table->dropUnique('fee_structures_program_level_session_unique');
        });
    }

    public function down(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->dropUnique('fee_structures_prog_lvl_sess_sem_unique');
            $table->dropColumn('semester_id');
            $table->unique(['program_id', 'level', 'session_id'], 'fee_structures_program_level_session_unique');
        });

        Schema::dropIfExists('fee_structure_items');
        Schema::dropIfExists('fee_components');
    }
};
