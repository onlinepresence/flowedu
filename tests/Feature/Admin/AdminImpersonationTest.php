<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Settings\UsersIndexPage;
use App\Models\Admin;
use App\Models\AdminImpersonationLog;
use App\Models\User;
use App\Models\UserRole;
use App\Services\AdminImpersonationService;
use Database\Seeders\AdminSystemSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\Concerns\ActsAsOwnerAdmin;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class AdminImpersonationTest extends TestCase
{
    use ActsAsOwnerAdmin;
    use CreatesTestSchool;
    use RefreshDatabase;

    public function test_registrar_cannot_open_impersonation_index(): void
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();
        $roleId = UserRole::query()->where('name', 'registrar')->value('id');
        $this->assertNotNull($roleId);

        $registrar = User::factory()->create(['type' => 'admin', 'username' => 'reg1']);
        $admin = new Admin;
        $admin->user_id = $registrar->id;
        $admin->type = $roleId;
        $admin->save();

        $this->actingAs($registrar)
            ->get(route('admin.impersonation.index'))
            ->assertForbidden();
    }

    public function test_system_admin_can_open_impersonation_index(): void
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();
        $roleId = UserRole::query()->where('name', 'system_admin')->value('id');
        $this->assertNotNull($roleId);

        $sys = User::factory()->create(['type' => 'admin', 'username' => 'sys1']);
        $admin = new Admin;
        $admin->user_id = $sys->id;
        $admin->type = $roleId;
        $admin->save();

        $this->actingAs($sys)
            ->get(route('admin.impersonation.index'))
            ->assertRedirect(route('admin.settings.users'));
    }

    public function test_stop_without_impersonation_is_forbidden(): void
    {
        $owner = $this->actingOwnerAdmin();

        $this->actingAs($owner)
            ->post(route('impersonation.stop'))
            ->assertForbidden();
    }

    public function test_owner_can_impersonate_student_stop_via_http_and_log_closes(): void
    {
        $owner = $this->actingOwnerAdmin();
        $student = User::factory()->create([
            'type' => 'student',
            'username' => 'stu1',
        ]);

        $this->actingAs($owner);

        $service = app(AdminImpersonationService::class);
        $service->start($owner, $student);

        $this->assertSame($student->id, auth()->id());
        $this->assertTrue(session()->has('college_impersonator_id'));

        $log = AdminImpersonationLog::query()->first();
        $this->assertNotNull($log);
        $this->assertNull($log->ended_at);

        $this->post(route('impersonation.stop'))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSame($owner->id, auth()->id());
        $this->assertFalse(session()->has('college_impersonator_id'));
        $this->assertNotNull($log->fresh()->ended_at);
    }

    public function test_owner_cannot_impersonate_another_owner(): void
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();
        $roleId = UserRole::query()->where('name', 'owner')->value('id');

        $ownerA = User::factory()->create(['type' => 'admin', 'username' => 'ownA']);
        $adminA = new Admin;
        $adminA->user_id = $ownerA->id;
        $adminA->type = $roleId;
        $adminA->save();

        $ownerB = User::factory()->create(['type' => 'admin', 'username' => 'ownB']);
        $adminB = new Admin;
        $adminB->user_id = $ownerB->id;
        $adminB->type = $roleId;
        $adminB->save();

        $this->actingAs($ownerA);

        $this->expectException(AuthorizationException::class);

        app(AdminImpersonationService::class)->start($ownerA, $ownerB);
    }

    public function test_nested_impersonation_is_rejected(): void
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();
        $roleId = UserRole::query()->where('name', 'owner')->value('id');

        $owner = User::factory()->create(['type' => 'admin', 'username' => 'ownNest']);
        $adminRow = new Admin;
        $adminRow->user_id = $owner->id;
        $adminRow->type = $roleId;
        $adminRow->save();

        $student = User::factory()->create(['type' => 'student', 'username' => 'stuNest']);
        $otherStudent = User::factory()->create(['type' => 'student', 'username' => 'stuNest2']);

        $this->actingAs($owner);
        $service = app(AdminImpersonationService::class);
        $service->start($owner, $student);

        $this->expectException(AccessDeniedHttpException::class);
        $service->start($owner, $otherStudent);
    }

    public function test_system_admin_can_impersonate_from_users_index_livewire(): void
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();
        $roleId = UserRole::query()->where('name', 'system_admin')->value('id');
        $this->assertNotNull($roleId);

        $sys = User::factory()->create(['type' => 'admin', 'username' => 'sysImp']);
        $admin = new Admin;
        $admin->user_id = $sys->id;
        $admin->type = $roleId;
        $admin->save();

        $student = User::factory()->create([
            'type' => 'student',
            'username' => 'stuImp',
            'active' => true,
        ]);

        $this->actingAs($sys);

        Livewire::test(UsersIndexPage::class)
            ->call('impersonate', $student->id)
            ->assertRedirect(route('post.login.redirect'));

        $this->assertSame($student->id, auth()->id());
        $this->assertTrue(session()->has('college_impersonator_id'));
    }

    public function test_owner_can_impersonate_staff_and_stop_via_http(): void
    {
        $owner = $this->actingOwnerAdmin();
        $staff = User::factory()->create([
            'type' => 'staff',
            'username' => 'stf1',
        ]);

        $this->actingAs($owner);

        $service = app(AdminImpersonationService::class);
        $service->start($owner, $staff);

        $this->assertSame($staff->id, auth()->id());
        $this->assertTrue(session()->has('college_impersonator_id'));

        $log = AdminImpersonationLog::query()->first();
        $this->assertNotNull($log);
        $this->assertNull($log->ended_at);

        $this->post(route('impersonation.stop'))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSame($owner->id, auth()->id());
        $this->assertFalse(session()->has('college_impersonator_id'));
        $this->assertNotNull($log->fresh()->ended_at);
    }
}

