<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SQLite stores enum() as VARCHAR + CHECK; MySQL adds `staff` via ALTER in 2026_04_12.
 * Allow `staff` in tests (phpunit uses sqlite :memory:).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('type', 32)->change();
        });
    }

    public function down(): void
    {
        // Non-reversible on SQLite without restoring CHECK constraint semantics.
    }
};
