<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bank name, account number, and SSNIT are optional on the student profile.
 * The original create migration marked them nullable; this is a defensive
 * re-statement so live databases created from an older schema (NOT NULL)
 * accept blanks as NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'account_bank')) {
                $table->string('account_bank')->nullable()->change();
            }
            if (Schema::hasColumn('students', 'account_number')) {
                $table->string('account_number')->nullable()->change();
            }
            if (Schema::hasColumn('students', 'ssnit_number')) {
                $table->string('ssnit_number')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        // Intentionally left as nullable: these fields are optional by design.
    }
};
