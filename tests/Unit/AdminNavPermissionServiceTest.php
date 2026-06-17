<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Admin;
use App\Models\User;
use App\Models\UserRole;
use App\Services\AdminNavPermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNavPermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_receives_unfiltered_nav_items(): void
    {
        UserRole::ensureSystemRoles();
        $ownerRoleId = UserRole::query()->where('name', 'owner')->value('id');
        $this->assertNotNull($ownerRoleId);

        $user = User::factory()->create(['type' => 'admin', 'username' => 'owner1']);
        Admin::query()->create([
            'user_id' => $user->id,
            'lastname' => 'O',
            'othernames' => 'Owner',
            'ghana_card' => 'GHA-OWNER-1',
            'type' => $ownerRoleId,
        ]);

        $service = new AdminNavPermissionService;
        $items = [
            ['label' => 'Dash', 'route' => 'admin.dashboard', 'permission' => 'nav_dashboard'],
            ['label' => 'Students', 'children' => [
                ['label' => 'All', 'route' => 'admin.students.index', 'permission' => 'nav_students_index'],
            ]],
        ];

        $filtered = $service->filterItemsForUser($user->fresh(), $items);

        $this->assertCount(2, $filtered);
    }

    public function test_custom_role_with_empty_permissions_gets_no_nav_leaves(): void
    {
        UserRole::ensureSystemRoles();

        $role = UserRole::query()->create([
            'role_name' => 'registrar',
            'name' => 'custom-empty-nav',
            'display_name' => 'Custom',
            'permissions' => [],
        ]);

        $user = User::factory()->create(['type' => 'admin', 'username' => 'cust1']);
        Admin::query()->create([
            'user_id' => $user->id,
            'lastname' => 'C',
            'othernames' => 'Custom',
            'ghana_card' => 'GHA-CUST-1',
            'type' => $role->id,
        ]);

        $service = new AdminNavPermissionService;
        $items = [
            ['label' => 'Dash', 'route' => 'admin.dashboard', 'permission' => 'nav_dashboard'],
        ];

        $filtered = $service->filterItemsForUser($user->fresh(), $items);

        $this->assertCount(0, $filtered);
    }
}
