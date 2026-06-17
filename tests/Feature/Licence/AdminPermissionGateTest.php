<?php

namespace Tests\Feature\Licence;

use App\Models\Admin;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AdminPermissionGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_bypasses_permission_gates(): void
    {
        $role = UserRole::updateOrCreate(
            ['name' => 'owner'],
            [
                'role_name' => 'owner',
                'display_name' => 'Owner',
                'permissions' => [],
            ]
        );

        $user = User::factory()->create(['type' => 'admin']);
        Admin::forceCreate([
            'user_id' => $user->id,
            'type' => $role->id,
            'lastname' => 'Test',
            'status' => 'active',
        ]);

        $this->assertTrue(Gate::forUser($user)->allows('admin.approve_registrations'));
        $this->assertTrue($user->hasAdminPermission('approve_registrations'));
    }

    public function test_registrar_with_permission_allowed(): void
    {
        $role = UserRole::updateOrCreate(
            ['name' => 'registrar'],
            [
                'role_name' => 'registrar',
                'display_name' => 'Registrar',
                'permissions' => ['approve_registrations'],
            ]
        );

        $user = User::factory()->create(['type' => 'admin']);
        Admin::forceCreate([
            'user_id' => $user->id,
            'type' => $role->id,
            'lastname' => 'Test',
            'status' => 'active',
        ]);

        $this->assertTrue(Gate::forUser($user)->allows('admin.approve_registrations'));
    }

    public function test_registrar_without_permission_denied(): void
    {
        $role = UserRole::updateOrCreate(
            ['name' => 'registrar'],
            [
                'role_name' => 'registrar',
                'display_name' => 'Registrar',
                'permissions' => [],
            ]
        );

        $user = User::factory()->create(['type' => 'admin']);
        Admin::forceCreate([
            'user_id' => $user->id,
            'type' => $role->id,
            'lastname' => 'Test',
            'status' => 'active',
        ]);

        $this->assertFalse(Gate::forUser($user)->allows('admin.approve_registrations'));
    }

    public function test_student_denied_admin_abilities(): void
    {
        $user = User::factory()->create(['type' => 'student']);

        $this->assertFalse(Gate::forUser($user)->allows('admin.approve_registrations'));
    }

    public function test_system_admin_bypasses_permission_gates(): void
    {
        $role = UserRole::updateOrCreate(
            ['name' => 'system_admin'],
            [
                'role_name' => 'system_admin',
                'display_name' => 'System administrator',
                'permissions' => [],
            ]
        );

        $user = User::factory()->create(['type' => 'admin']);
        Admin::forceCreate([
            'user_id' => $user->id,
            'type' => $role->id,
            'lastname' => 'Test',
            'status' => 'active',
        ]);

        $this->assertTrue(Gate::forUser($user)->allows('admin.nav_settings_roles'));
        $this->assertFalse($user->hasAdminPermission('nav_settings_roles'));
    }
}
