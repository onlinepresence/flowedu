<?php

namespace Tests\Feature\Licence;

use App\Models\Admin;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_access_owner_only_route(): void
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

        $this->actingAs($user)
            ->get(route('testing.admin.owner'))
            ->assertOk()
            ->assertSee('owner-ok');
    }

    public function test_registrar_cannot_access_owner_only_route(): void
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

        $this->actingAs($user)
            ->get(route('testing.admin.owner'))
            ->assertForbidden();
    }
}
