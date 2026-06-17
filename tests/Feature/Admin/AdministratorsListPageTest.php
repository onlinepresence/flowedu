<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Staff\AdministratorsListPage;
use App\Models\Admin;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\ActsAsOwnerAdmin;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class AdministratorsListPageTest extends TestCase
{
    use ActsAsOwnerAdmin;
    use CreatesTestSchool;
    use RefreshDatabase;

    public function test_owner_can_view_administrators_and_create_new_one(): void
    {
        $owner = $this->actingOwnerAdmin();

        $faculty = Faculty::query()->create(['name' => 'Humanities']);
        $department = Department::query()->create([
            'name' => 'Languages',
            'faculty_id' => $faculty->id,
        ]);

        $registrarRole = UserRole::query()->where('name', 'registrar')->firstOrFail();

        Livewire::actingAs($owner)
            ->test(AdministratorsListPage::class)
            ->set('lastname', 'Registrar')
            ->set('othernames', 'Jane')
            ->set('username', 'jane_reg')
            ->set('email', 'jane@example.test')
            ->set('password', '') // default should be Password@1
            ->set('type', $registrarRole->id)
            ->set('department_id', $department->id)
            ->set('faculty_id', $faculty->id)
            ->set('gender', 'female')
            ->set('position_title', 'Head Registrar')
            ->set('phone_number', '0245555555')
            ->set('status', 'active')
            ->call('saveCreate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.test',
            'username' => 'jane_reg',
            'type' => 'admin',
        ]);

        $this->assertDatabaseHas('admins', [
            'lastname' => 'Registrar',
            'othernames' => 'Jane',
            'gender' => 'female',
            'type' => $registrarRole->id,
        ]);

        $adminUser = User::query()->where('username', 'jane_reg')->firstOrFail();
        $this->assertTrue(auth()->attempt([
            'username' => 'jane_reg',
            'password' => 'Password@1',
        ]));
    }

    public function test_cannot_create_administrator_with_owner_role(): void
    {
        $owner = $this->actingOwnerAdmin();
        $ownerRole = UserRole::query()->where('name', 'owner')->firstOrFail();

        Livewire::actingAs($owner)
            ->test(AdministratorsListPage::class)
            ->set('lastname', 'Cheat')
            ->set('othernames', 'User')
            ->set('username', 'cheat_user')
            ->set('email', 'cheat@example.test')
            ->set('type', $ownerRole->id)
            ->call('saveCreate')
            ->assertHasErrors(['type']);
    }

    public function test_can_deactivate_administrator(): void
    {
        $owner = $this->actingOwnerAdmin();
        $registrarRole = UserRole::query()->where('name', 'registrar')->firstOrFail();

        $adminUser = User::factory()->create([
            'type' => 'admin',
            'username' => 'deactivateme',
        ]);
        $adminProfile = Admin::query()->create([
            'user_id' => $adminUser->id,
            'lastname' => 'Profile',
            'othernames' => 'Deactivate',
            'type' => $registrarRole->id,
            'status' => 'active',
        ]);

        Livewire::actingAs($owner)
            ->test(AdministratorsListPage::class)
            ->call('openDeactivateModal', $adminProfile->id)
            ->assertSet('deactivatingAdminId', $adminProfile->id)
            ->call('confirmDeactivate')
            ->assertHasNoErrors();

        $adminProfile->refresh();
        $adminUser->refresh();

        $this->assertSame('inactive', $adminProfile->status);
        $this->assertFalse($adminUser->active);
    }
}
