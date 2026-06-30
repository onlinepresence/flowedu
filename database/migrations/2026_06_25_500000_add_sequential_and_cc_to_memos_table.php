<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memos', function (Blueprint $table) {
            $table->boolean('route_sequentially')->default(false)->after('signing_user_id');
            $table->json('cc_recipients')->nullable()->after('route_sequentially');
        });
    }

    public function down(): void
    {
        Schema::table('memos', function (Blueprint $table) {
            $table->dropColumn(['route_sequentially', 'cc_recipients']);
        });
    }
};
