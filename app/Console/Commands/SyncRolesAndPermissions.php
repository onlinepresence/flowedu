<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AdminType;
use App\Models\UserRole;
use Illuminate\Console\Command;

class SyncRolesAndPermissions extends Command
{
    protected $signature = 'college:sync-roles';

    protected $description = 'Synchronize default admin types, roles and merge their baseline permissions.';

    public function handle(): int
    {
        $this->info('Starting sync of roles and permissions...');

        AdminType::ensureDefaults();
        $this->info('Admin types defaulted successfully.');

        UserRole::ensureSystemRoles();
        $this->info('System user roles and permissions synced/merged successfully.');

        return self::SUCCESS;
    }
}
