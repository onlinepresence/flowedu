<?php

use App\Models\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        UserRole::ensureSystemRoles();

        $ownerId = UserRole::query()->where('name', 'owner')->value('id');
        if ($ownerId === null) {
            return;
        }

        DB::table('admins')
            ->whereNull('type')
            ->update([
                'type' => $ownerId,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Cannot restore previous null vs intentional assignments.
    }
};
