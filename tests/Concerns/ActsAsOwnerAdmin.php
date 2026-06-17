<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Admin;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\AdminSystemSeeder;

trait ActsAsOwnerAdmin
{
    protected function actingOwnerAdmin(): User
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();

        $roleId = UserRole::query()->where('name', 'owner')->value('id');

        $user = User::factory()->create([
            'type' => 'admin',
            'username' => 'owneradmin',
        ]);

        $admin = new Admin;
        $admin->user_id = $user->id;
        $admin->type = $roleId;
        $admin->save();

        return $user;
    }
}
