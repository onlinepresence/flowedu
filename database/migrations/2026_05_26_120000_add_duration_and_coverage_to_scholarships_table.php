<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scholarships', function (Blueprint $table): void {
            $table->unsignedInteger('duration_semesters')->default(1)->after('amount');
            $table->date('expiry_date')->nullable()->after('duration_semesters');
            $table->string('coverage_type', 32)->default('full')->after('expiry_date'); // full, tuition_only, hostel_only, partial
            $table->json('coverage_components')->nullable()->after('coverage_type'); // e.g. ["tuition_fee", "library_fee"]
        });
    }

    public function down(): void
    {
        Schema::table('scholarships', function (Blueprint $table): void {
            $table->dropColumn([
                'duration_semesters',
                'expiry_date',
                'coverage_type',
                'coverage_components',
            ]);
        });
    }
};
