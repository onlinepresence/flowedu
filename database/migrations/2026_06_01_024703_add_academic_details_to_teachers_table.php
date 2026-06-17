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
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('title', 30)->nullable()->after('othernames');
            $table->string('office_location', 150)->nullable()->after('department_id');
            $table->string('office_hours', 200)->nullable()->after('office_location');
            $table->string('orcid_id', 50)->nullable()->after('specialization');
            $table->string('google_scholar_url', 255)->nullable()->after('orcid_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn([
                'title',
                'office_location',
                'office_hours',
                'orcid_id',
                'google_scholar_url',
            ]);
        });
    }
};
