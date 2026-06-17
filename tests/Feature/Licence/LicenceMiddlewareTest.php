<?php

namespace Tests\Feature\Licence;

use App\Models\SchoolLicence;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\AdminSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class LicenceMiddlewareTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    public function test_licence_middleware_allows_finance_when_enforcement_disabled(): void
    {
        config(['licence.enforce' => false]);

        $school = $this->createTestSchool();
        SchoolLicence::forceCreate([
            'school_id' => $school->id,
            'module_finance' => false,
            'max_active_students' => null,
            'licence_start' => now()->toDateString(),
            'licence_end' => null,
            'support_until' => now()->addYear()->toDateString(),
            'notes' => null,
            'external_ref' => null,
            'licence_key' => null,
        ]);

        $user = User::factory()->create(['type' => 'student']);

        $this->actingAs($user)
            ->get(route('testing.licence.finance'))
            ->assertOk()
            ->assertSee('ok');
    }

    public function test_licence_middleware_redirects_when_tier_insufficient(): void
    {
        config(['licence.enforce' => true]);

        $school = $this->createTestSchool();
        SchoolLicence::forceCreate([
            'school_id' => $school->id,
            'module_finance' => false,
            'max_active_students' => null,
            'licence_start' => now()->toDateString(),
            'licence_end' => null,
            'support_until' => now()->addYear()->toDateString(),
            'notes' => null,
            'external_ref' => null,
            'licence_key' => null,
        ]);

        $user = User::factory()->create(['type' => 'student']);

        $this->actingAs($user)
            ->get(route('testing.licence.finance'))
            ->assertRedirect(route('licence.required', ['feature' => 'finance']))
            ->assertSessionHas('system_message');
    }

    public function test_licence_required_page_shows_message(): void
    {
        $school = $this->createTestSchool();
        SchoolLicence::forceCreate([
            'school_id' => $school->id,
            'module_finance' => false,
            'max_active_students' => null,
            'licence_start' => now()->toDateString(),
            'licence_end' => null,
            'support_until' => now()->addYear()->toDateString(),
            'notes' => null,
            'external_ref' => null,
            'licence_key' => null,
        ]);

        $user = User::factory()->create(['type' => 'student']);

        $this->actingAs($user)
            ->get(route('licence.required', ['feature' => 'finance']))
            ->assertOk()
            ->assertSee('Upgrade required for Financial Portal', false);
    }

    public function test_licence_required_page_shows_subscription_link_for_owner_admin(): void
    {
        $this->seed(AdminSystemSeeder::class);

        $school = $this->createTestSchool();
        SchoolLicence::forceCreate([
            'school_id' => $school->id,
            'module_finance' => false,
            'max_active_students' => null,
            'licence_start' => now()->toDateString(),
            'licence_end' => null,
            'support_until' => now()->addYear()->toDateString(),
            'notes' => null,
            'external_ref' => null,
            'licence_key' => null,
        ]);

        $ownerRoleId = UserRole::query()->where('name', 'owner')->value('id');
        $this->assertNotNull($ownerRoleId);

        $user = User::factory()->create([
            'type' => 'admin',
            'username' => 'owneruser',
        ]);

        DB::table('admins')->insert([
            'user_id' => $user->id,
            'type' => $ownerRoleId,
            'lastname' => 'Owner',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('licence.required', ['feature' => 'finance']))
            ->assertOk()
            ->assertSee('View licence', false);
    }
}
