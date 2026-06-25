<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Settings\SettingsUserRolesPage;
use App\Models\Admin;
use App\Models\AdminType;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\AdminSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class SettingsUserRolesPageTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    private function actingSystemAdmin(): User
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();

        $roleId = UserRole::query()->where('name', 'system_admin')->value('id');
        $this->assertNotNull($roleId);

        $user = User::factory()->create([
            'type' => 'admin',
            'username' => 'sysroles',
        ]);

        $admin = new Admin;
        $admin->user_id = $user->id;
        $admin->type = $roleId;
        $admin->save();

        return $user;
    }

    public function test_system_admin_can_create_custom_role_with_permissions(): void
    {
        $user = $this->actingSystemAdmin();

        Livewire::actingAs($user)
            ->test(SettingsUserRolesPage::class)
            ->call('openCreate')
            ->set('display_name', 'Finance assistant')
            ->set('role_name', 'registrar')
            ->set('name', 'finance-assistant')
            ->set('selectedPermissions', ['nav_dashboard', 'view_dashboard_admin'])
            ->call('saveRole')
            ->assertRedirect(route('admin.settings.roles'));

        $row = UserRole::query()->where('name', 'finance-assistant')->first();
        $this->assertNotNull($row);
        $this->assertSame('Finance assistant', $row->display_name);
        $this->assertSame(['nav_dashboard', 'view_dashboard_admin'], array_values($row->permissions ?? []));
    }

    public function test_mount_ensures_admin_types_when_empty(): void
    {
        $this->createTestSchool();

        UserRole::ensureSystemRoles();
        $roleId = UserRole::query()->where('name', 'system_admin')->value('id');
        $this->assertNotNull($roleId);

        $user = User::factory()->create([
            'type' => 'admin',
            'username' => 'typesfill',
        ]);

        $admin = new Admin;
        $admin->user_id = $user->id;
        $admin->type = $roleId;
        $admin->save();

        $this->assertSame(0, AdminType::query()->count());

        Livewire::actingAs($user)
            ->test(SettingsUserRolesPage::class);

        $this->assertSame(18, AdminType::query()->count());
        $this->assertTrue(AdminType::query()->where('name', 'hod')->exists());
    }

    public function test_cannot_delete_protected_owner_role(): void
    {
        $user = $this->actingSystemAdmin();
        $ownerId = UserRole::query()->where('name', 'owner')->value('id');
        $this->assertNotNull($ownerId);

        Livewire::actingAs($user)
            ->test(SettingsUserRolesPage::class)
            ->call('confirmDelete', $ownerId)
            ->assertHasErrors('delete');
    }
}
