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
        Schema::table('non_teaching_staff', function (Blueprint $table) {
            $table->string('profile_pic', 255)->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('non_teaching_staff') && Schema::hasColumn('non_teaching_staff', 'profile_pic')) {
            Schema::table('non_teaching_staff', function (Blueprint $table) {
                $table->dropColumn('profile_pic');
            });
        }
    }
};
