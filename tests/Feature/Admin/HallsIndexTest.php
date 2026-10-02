<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Setup\SetupHallPage;
use App\Models\Admin;
use App\Models\Hall;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\AdminSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\ActsAsOwnerAdmin;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class HallsIndexTest extends TestCase
{
    use ActsAsOwnerAdmin;
    use CreatesTestSchool;
    use RefreshDatabase;

    public function test_owner_can_open_academic_halls_and_sees_menu(): void
    {
        $owner = $this->actingOwnerAdmin();
        Hall::query()->create(['name' => 'Unity Hall', 'master' => null, 'cost' => 500, 'period' => 'per_year']);

        $this->actingAs($owner)
            ->get(route('admin.academic.halls'))
            ->assertOk()
            ->assertSee('Unity Hall')
            ->assertSee('Halls');
    }

    public function test_owner_can_create_hall_from_academic_page(): void
    {
        $owner = $this->actingOwnerAdmin();

        Livewire::actingAs($owner)
            ->test(SetupHallPage::class)
            ->set('name', 'Independence Hall')
            ->set('cost', '750')
            ->set('period', 'per_semester')
            ->call('saveHall')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('halls', ['name' => 'Independence Hall']);
    }

    public function test_registrar_without_permission_is_blocked(): void
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();

        $user = User::factory()->create(['type' => 'admin', 'username' => 'reg_no_halls']);
        Admin::query()->create([
            'user_id' => $user->id,
            'lastname' => 'Reg',
            'othernames' => 'User',
            'type' => UserRole::query()->where('name', 'registrar')->value('id'),
            'status' => 'active',
        ]);

        $this->actingAs($user)->get(route('admin.academic.halls'))->assertForbidden();
    }

    public function test_setup_wizard_route_still_works_for_owner(): void
    {
        $owner = $this->actingOwnerAdmin();

        $this->actingAs($owner)
            ->get(route('admin.setup.halls'))
            ->assertOk()
            ->assertSee('Add hall');
    }
}
