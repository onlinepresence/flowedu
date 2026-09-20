<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Settings\UsersIndexPage;
use App\Models\Admin;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\AdminSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\ActsAsOwnerAdmin;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

/**
 * User Accounts are core functionality: access follows the
 * nav_settings_users capability, never the licence tier.
 */
class UsersIndexPageAccessTest extends TestCase
{
    use ActsAsOwnerAdmin;
    use CreatesTestSchool;
    use RefreshDatabase;

    private function adminWithRole(string $roleName, string $username): User
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();

        $user = User::factory()->create(['type' => 'admin', 'username' => $username]);
        $admin = new Admin;
        $admin->user_id = $user->id;
        $admin->type = UserRole::query()->where('name', $roleName)->value('id');
        $admin->save();

        return $user;
    }

    public function test_owner_can_open_create_modal_without_licence_module(): void
    {
        $owner = $this->actingOwnerAdmin();
        $this->createTestSchool();

        // Even with enforcement on and every module off, core access holds.
        config(['licence.enforce' => true]);
        app(\App\Services\SchoolLicenceService::class)->getLicenceRow();
        $licence = \App\Models\School::first()->licence()->firstOrFail();
        $licence->forceFill([
            'module_system_admin' => false,
            'module_finance' => false,
        ])->save();
        app(\App\Services\SchoolLicenceService::class)->refresh();

        Livewire::actingAs($owner)
            ->test(UsersIndexPage::class)
            ->call('openCreateModal')
            ->assertHasNoErrors()
            ->assertDispatched('open-modal', 'users-create');
    }

    public function test_role_with_user_accounts_permission_can_create(): void
    {
        UserRole::query()->create([
            'name' => 'user_manager',
            'role_name' => 'user_manager',
            'display_name' => 'User Manager',
            'permissions' => ['nav_settings_users'],
        ]);

        $user = $this->adminWithRole('user_manager', 'user_mgr_idx');
        config(['licence.enforce' => true]);

        Livewire::actingAs($user)
            ->test(UsersIndexPage::class)
            ->call('openCreateModal')
            ->assertHasNoErrors();
    }

    public function test_registrar_without_permission_is_blocked(): void
    {
        $registrar = $this->adminWithRole('registrar', 'reg_no_users_idx');

        Livewire::actingAs($registrar)
            ->test(UsersIndexPage::class)
            ->call('openCreateModal')
            ->assertForbidden();
    }
}
