<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_audits', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        // Seed existing records with UUIDs
        DB::table('system_audits')->orderBy('id')->chunk(100, function ($rows) {
            foreach ($rows as $row) {
                DB::table('system_audits')
                    ->where('id', $row->id)
                    ->update(['uuid' => (string) Str::uuid()]);
            }
        });

        Schema::table('system_audits', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change()->unique();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('system_audits', function (Blueprint $table) {
                $table->dropColumn('uuid');
            });
        }
    }
};
