<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Admin;
use App\Models\SchoolLicence;
use App\Models\User;
use App\Models\UserRole;
use App\Policies\UserPolicy;
use App\Services\SchoolLicenceService;
use Database\Seeders\AdminSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    public function test_impersonate_allows_owner_targeting_student(): void
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();
        $ownerRoleId = UserRole::query()->where('name', 'owner')->value('id');

        $owner = User::factory()->create(['type' => 'admin', 'username' => 'o1']);
        $a = new Admin;
        $a->user_id = $owner->id;
        $a->type = $ownerRoleId;
        $a->save();

        $student = User::factory()->create(['type' => 'student', 'username' => 's1', 'active' => true]);

        $policy = app(UserPolicy::class);

        $this->assertTrue($policy->impersonate($owner, $student));
    }

    public function test_impersonate_denies_target_that_can_start_impersonation(): void
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();
        $ownerRoleId = UserRole::query()->where('name', 'owner')->value('id');

        $ownerA = User::factory()->create(['type' => 'admin', 'username' => 'a1']);
        $admA = new Admin;
        $admA->user_id = $ownerA->id;
        $admA->type = $ownerRoleId;
        $admA->save();

        $ownerB = User::factory()->create(['type' => 'admin', 'username' => 'b1']);
        $admB = new Admin;
        $admB->user_id = $ownerB->id;
        $admB->type = $ownerRoleId;
        $admB->save();

        $policy = app(UserPolicy::class);

        $this->assertFalse($policy->impersonate($ownerA, $ownerB));
    }

    public function test_update_for_user_settings_allows_owner_on_non_owner_target(): void
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();
        $ownerRoleId = UserRole::query()->where('name', 'owner')->value('id');

        $owner = User::factory()->create(['type' => 'admin', 'username' => 'own1']);
        $adminRow = new Admin;
        $adminRow->user_id = $owner->id;
        $adminRow->type = $ownerRoleId;
        $adminRow->save();

        $student = User::factory()->create(['type' => 'student', 'username' => 'stu1']);

        $policy = app(UserPolicy::class);

        $this->assertTrue($policy->updateForUserSettings($owner, $student));
    }

    public function test_update_for_user_settings_denies_system_admin_on_owner_target(): void
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();
        $ownerRoleId = UserRole::query()->where('name', 'owner')->value('id');
        $sysRoleId = UserRole::query()->where('name', 'system_admin')->value('id');

        $owner = User::factory()->create(['type' => 'admin', 'username' => 'own2']);
        $adminOwner = new Admin;
        $adminOwner->user_id = $owner->id;
        $adminOwner->type = $ownerRoleId;
        $adminOwner->save();

        $sys = User::factory()->create(['type' => 'admin', 'username' => 'sys2']);
        $adminSys = new Admin;
        $adminSys->user_id = $sys->id;
        $adminSys->type = $sysRoleId;
        $adminSys->save();

        $policy = app(UserPolicy::class);

        $this->assertFalse($policy->updateForUserSettings($sys, $owner));
    }

    public function test_toggle_active_denies_deactivating_self(): void
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();
        $ownerRoleId = UserRole::query()->where('name', 'owner')->value('id');

        $owner = User::factory()->create(['type' => 'admin', 'username' => 'own3', 'active' => true]);
        $adminRow = new Admin;
        $adminRow->user_id = $owner->id;
        $adminRow->type = $ownerRoleId;
        $adminRow->save();

        $policy = app(UserPolicy::class);

        $this->assertFalse($policy->toggleActiveForUserSettings($owner, $owner));
    }

    public function test_update_for_user_settings_denies_when_system_admin_licence_blocked(): void
    {
        config(['licence.enforce' => true]);
        $this->seed(AdminSystemSeeder::class);
        $school = $this->createTestSchool();
        app(SchoolLicenceService::class)->refresh();
        SchoolLicence::query()->updateOrCreate(
            ['school_id' => $school->id],
            [
                'module_system_admin' => false,
                'max_active_students' => null,
                'licence_start' => now()->toDateString(),
                'licence_end' => null,
                'support_until' => now()->addYear()->toDateString(),
                'notes' => null,
                'external_ref' => null,
                'licence_key' => null,
            ],
        );
        app(SchoolLicenceService::class)->refresh();

        $ownerRoleId = UserRole::query()->where('name', 'owner')->value('id');
        $owner = User::factory()->create(['type' => 'admin', 'username' => 'own4']);
        $adminRow = new Admin;
        $adminRow->user_id = $owner->id;
        $adminRow->type = $ownerRoleId;
        $adminRow->save();

        $student = User::factory()->create(['type' => 'student', 'username' => 'stu2']);

        $policy = app(UserPolicy::class);

        $this->assertFalse($policy->updateForUserSettings($owner, $student));
    }
}
