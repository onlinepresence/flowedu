<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memo_signatories', function (Blueprint $table) {
            $table->integer('step_number')->default(1)->after('user_id');
            $table->string('signature_path')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('memo_signatories', function (Blueprint $table) {
            $table->dropColumn(['step_number', 'signature_path']);
        });
    }
};
