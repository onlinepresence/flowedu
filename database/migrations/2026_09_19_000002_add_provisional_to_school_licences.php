<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Provisional licences are core-only rows created by the "continue
     * offline" setup exit. They stay locally editable (badged) until the
     * first successful ControlDesk heartbeat overwrites them with central
     * truth. Existing rows default false = exactly today's behavior.
     */
    public function up(): void
    {
        Schema::table('school_licences', function (Blueprint $table) {
            $table->boolean('provisional')->default(false)->after('external_ref');
        });
    }

    public function down(): void
    {
        Schema::table('school_licences', function (Blueprint $table) {
            $table->dropColumn('provisional');
        });
    }
};
