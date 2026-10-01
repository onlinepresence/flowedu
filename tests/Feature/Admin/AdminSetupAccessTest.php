<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Http\Middleware\EnsureSetupOwnerAccess;
use App\Livewire\Admin\Setup\AdminSetupPersonalPage;
use App\Models\Admin;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\AdminSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Livewire\Livewire;
use Tests\Concerns\ActsAsOwnerAdmin;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class AdminSetupAccessTest extends TestCase
{
    use ActsAsOwnerAdmin;
    use CreatesTestSchool;
    use RefreshDatabase;

    private function actingRegistrar(string $username = 'registrar1'): User
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();

        $user = User::factory()->create(['type' => 'admin', 'username' => $username]);
        Admin::query()->create([
            'user_id' => $user->id,
            'lastname' => 'Reg',
            'othernames' => 'User',
            'type' => UserRole::query()->where('name', 'registrar')->value('id'),
            'status' => 'active',
        ]);

        return $user;
    }

    public function test_non_owner_sees_only_personal_information_in_setup_menu(): void
    {
        // Mirrors an account created via Settings > User Accounts (no admin row yet).
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();
        $user = User::factory()->create(['type' => 'admin', 'username' => 'newadmin']);

        $this->actingAs($user)
            ->get(route('admin.setup.personal'))
            ->assertOk()
            ->assertSee('Personal Information')
            ->assertDontSee('Package & licence')
            ->assertDontSee('Setup School')
            ->assertDontSee('Activate System');
    }

    public function test_owner_sees_full_setup_menu(): void
    {
        $owner = $this->actingOwnerAdmin();

        $this->actingAs($owner)
            ->get(route('admin.setup.personal'))
            ->assertOk()
            ->assertSee('Personal Information')
            ->assertSee('Package & licence');
    }

    public function test_non_owner_save_redirects_to_dashboard_without_owner_role(): void
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();
        $user = User::factory()->create(['type' => 'admin', 'username' => 'newadmin']);

        Livewire::actingAs($user)
            ->test(AdminSetupPersonalPage::class)
            ->set('isSetupFlow', true)
            ->set('username', 'newadmin')
            ->set('lastname', 'Admin')
            ->set('othernames', 'New')
            ->set('ghana_card', 'GHA-999999999-0')
            ->set('gender', 'other')
            ->set('phone_number', '0240000000')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.dashboard'));

        $row = Admin::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertNull($row->type);
        $this->assertFalse($user->refresh()->isAdminOwner());
    }

    public function test_bootstrap_save_still_crowns_owner_and_continues_wizard(): void
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();
        $user = User::factory()->create(['type' => 'admin', 'username' => '']);

        $this->withSession(['admin_register' => true]);

        Livewire::actingAs($user)
            ->test(AdminSetupPersonalPage::class)
            ->set('isSetupFlow', true)
            ->set('username', 'bootstrapowner')
            ->set('lastname', 'Admin')
            ->set('othernames', 'Super')
            ->set('ghana_card', 'GHA-111111111-0')
            ->set('gender', 'other')
            ->set('phone_number', '0240000001')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.setup.school'));

        $this->assertTrue($user->refresh()->isAdminOwner());
    }

    public function test_non_owner_cannot_open_setup_wizard_urls(): void
    {
        $registrar = $this->actingRegistrar();

        $this->actingAs($registrar)
            ->get(route('admin.setup.licence'))
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($registrar)
            ->get(route('admin.setup.school'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_profile_less_non_owner_is_sent_to_personal_setup(): void
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();
        $user = User::factory()->create(['type' => 'admin', 'username' => '']);

        $request = Request::create(route('admin.setup.licence'), 'GET');
        $request->setUserResolver(fn () => $user);

        $response = (new EnsureSetupOwnerAccess)->handle($request, fn () => response('passed'));

        $this->assertTrue($response->isRedirect(route('admin.setup.personal')));
    }

    public function test_owner_and_bootstrap_pass_setup_guard(): void
    {
        $owner = $this->actingOwnerAdmin();

        foreach ([$owner] as $user) {
            $request = Request::create(route('admin.setup.licence'), 'GET');
            $request->setUserResolver(fn () => $user);

            $response = (new EnsureSetupOwnerAccess)->handle($request, fn () => response('passed'));

            $this->assertSame('passed', $response->getContent());
        }

        $this->seed(AdminSystemSeeder::class);
        $plain = User::factory()->create(['type' => 'admin', 'username' => 'plain']);
        $request = Request::create(route('admin.setup.licence'), 'GET');
        $request->setUserResolver(fn () => $plain);
        $request->setLaravelSession(session()->driver());
        session()->put('admin_register', true);

        $response = (new EnsureSetupOwnerAccess)->handle($request, fn () => response('passed'));

        $this->assertSame('passed', $response->getContent());
    }

    public function test_null_type_admin_is_not_crowned_when_owner_exists(): void
    {
        $this->actingOwnerAdmin();

        $user = User::factory()->create(['type' => 'admin', 'username' => 'roleless']);
        Admin::query()->create([
            'user_id' => $user->id,
            'lastname' => 'Role',
            'othernames' => 'Less',
            'type' => null,
            'status' => 'active',
        ]);

        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();

        $this->assertNull(Admin::query()->where('user_id', $user->id)->value('type'));
        $this->assertFalse($user->refresh()->isAdminOwner());
    }
}
