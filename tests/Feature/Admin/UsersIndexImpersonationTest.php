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

class UsersIndexImpersonationTest extends TestCase
{
    use ActsAsOwnerAdmin;
    use CreatesTestSchool;
    use RefreshDatabase;

    public function test_owner_can_impersonate_student_from_users_index_livewire(): void
    {
        $owner = $this->actingOwnerAdmin();
        $student = User::factory()->create([
            'type' => 'student',
            'username' => 'stu_users_idx',
            'active' => true,
        ]);

        Livewire::actingAs($owner)
            ->test(UsersIndexPage::class)
            ->call('impersonate', $student->id)
            ->assertRedirect(route('post.login.redirect'));

        $this->assertSame($student->id, auth()->id());
        $this->assertTrue(session()->has('college_impersonator_id'));
    }

    public function test_registrar_cannot_impersonate_from_users_index_livewire(): void
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();
        $roleId = UserRole::query()->where('name', 'registrar')->value('id');
        $this->assertNotNull($roleId);

        $registrar = User::factory()->create(['type' => 'admin', 'username' => 'reg_users_idx']);
        $admin = new Admin;
        $admin->user_id = $registrar->id;
        $admin->type = $roleId;
        $admin->save();

        $student = User::factory()->create([
            'type' => 'student',
            'username' => 'stu_reg_block',
            'active' => true,
        ]);

        Livewire::actingAs($registrar)
            ->test(UsersIndexPage::class)
            ->call('impersonate', $student->id)
            ->assertHasErrors(['impersonate']);
    }
}
