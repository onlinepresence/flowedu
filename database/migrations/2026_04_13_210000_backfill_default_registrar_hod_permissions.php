<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            'registrar' => ['view_dashboard_admin', 'student_management', 'approve_registrations'],
            'hod' => ['view_dashboard_admin', 'course_management', 'teacher_management'],
        ];

        foreach ($rows as $name => $permissions) {
            $encoded = json_encode(array_values($permissions), JSON_THROW_ON_ERROR);

            DB::table('user_roles')
                ->where('name', $name)
                ->where(function ($q): void {
                    $q->whereNull('permissions')
                        ->orWhere('permissions', '')
                        ->orWhere('permissions', '[]');
                })
                ->update([
                    'permissions' => $encoded,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Intentionally empty: cannot distinguish migrated defaults from intentional empty grants.
    }
};
