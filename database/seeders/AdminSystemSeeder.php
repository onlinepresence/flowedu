<?php

namespace Database\Seeders;

use App\Models\AdminType;
use App\Models\UserRole;
use Illuminate\Database\Seeder;

class AdminSystemSeeder extends Seeder
{
    /**
     * Baseline rows for admin UX (legacy-compatible names; admins.type FK targets user_roles).
     */
    public function run(): void
    {
        AdminType::ensureDefaults();
        UserRole::ensureSystemRoles();
    }
}
