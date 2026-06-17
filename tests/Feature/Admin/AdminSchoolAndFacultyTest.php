<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Academic\FacultyIndex;
use App\Livewire\Admin\Settings\SchoolProfileForm;
use App\Models\Admin;
use App\Models\Faculty;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\AdminSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class AdminSchoolAndFacultyTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    private function actingAdmin(): User
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();

        $roleId = UserRole::query()->where('name', 'owner')->value('id');
        $user = User::factory()->create([
            'type' => 'admin',
            'username' => 'testadmin',
        ]);

        $admin = new Admin;
        $admin->user_id = $user->id;
        $admin->type = $roleId;
        $admin->save();

        return $user;
    }

    public function test_admin_can_update_school_profile_via_livewire(): void
    {
        $user = $this->actingAdmin();

        Livewire::actingAs($user)
            ->test(SchoolProfileForm::class)
            ->set('name', 'Updated College')
            ->set('address', 'New Address Line')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('schools', [
            'name' => 'Updated College',
            'address' => 'New Address Line',
        ]);
    }

    public function test_admin_can_add_faculty_via_livewire(): void
    {
        $user = $this->actingAdmin();

        Livewire::actingAs($user)
            ->test(FacultyIndex::class)
            ->set('newName', 'Science')
            ->call('saveFaculty')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('faculties', ['name' => 'Science']);
        $this->assertSame(1, Faculty::query()->where('name', 'Science')->count());
    }
}
