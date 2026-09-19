<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Human-readable academic year on the fee ledger (e.g. "2026/2027").
     * New rows default via FeePayment's creating hook; existing rows are
     * backfilled from their session (or the current session when unset).
     */
    public function up(): void
    {
        Schema::table('fee_payments', function (Blueprint $table) {
            $table->string('academic_year', 16)->nullable()->after('semester_id');
        });

        $currentName = DB::table('academic_sessions')->where('is_current', true)->value('name')
            ?? now()->format('Y').'/'.((int) now()->format('Y') + 1);

        $sessionNames = DB::table('academic_sessions')->pluck('name', 'id');

        foreach (DB::table('fee_payments')->whereNull('academic_year')->get(['id', 'academic_session_id']) as $row) {
            DB::table('fee_payments')->where('id', $row->id)->update([
                'academic_year' => $sessionNames[$row->academic_session_id] ?? $currentName,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('fee_payments', function (Blueprint $table) {
            $table->dropColumn('academic_year');
        });
    }
};
