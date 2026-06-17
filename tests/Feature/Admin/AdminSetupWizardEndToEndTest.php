<?php

namespace Tests\Feature\Admin;

use App\Jobs\SendCollegeNotificationMailJob;
use App\Livewire\Admin\Academic\FacultyIndex;
use App\Livewire\Admin\Settings\SchoolProfileForm;
use App\Livewire\Admin\Setup\AdminSetupPersonalPage;
use App\Livewire\Admin\Setup\SetupActivatePage;
use App\Livewire\Admin\Setup\SetupDepartmentPage;
use App\Livewire\Admin\Setup\SetupHallPage;
use App\Livewire\Admin\Setup\SetupLicenceForm;
use App\Livewire\Admin\Setup\SetupProgramPage;
use App\Models\Department;
use App\Models\School;
use App\Models\User;
use Database\Seeders\AdminSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AdminSetupWizardEndToEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_wizard_from_empty_db_to_ready_and_dashboard(): void
    {
        $this->seed(AdminSystemSeeder::class);

        $this->assertDatabaseCount('schools', 0);

        session(['admin_register' => true]);

        Volt::test('pages.auth.register')
            ->set('email', 'owner@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('system_secret', config('college.system_registration_secret'))
            ->call('register')
            ->assertRedirect(route('admin.setup.personal', absolute: false));

        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(AdminSetupPersonalPage::class)
            ->set('username', 'owneruser')
            ->set('lastname', 'Admin')
            ->set('othernames', 'Super')
            ->set('ghana_card', 'GHA-123456789-0')
            ->call('save')
            ->assertHasNoErrors();

        Livewire::actingAs($user)
            ->test(SchoolProfileForm::class)
            ->set('name', 'New College')
            ->set('address', '1 Campus Road')
            ->set('email', 'school@example.com')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('schools', [
            'name' => 'New College',
            'ready' => false,
        ]);

        Livewire::actingAs($user)
            ->test(SetupLicenceForm::class)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.setup.faculties', absolute: false));

        Livewire::actingAs($user)
            ->test(FacultyIndex::class)
            ->set('newName', 'Engineering')
            ->call('saveFaculty')
            ->assertHasNoErrors();

        Livewire::actingAs($user)
            ->test(SetupDepartmentPage::class)
            ->set('name', 'Computer Science')
            ->call('saveDepartment')
            ->assertHasNoErrors();

        $departmentId = (string) Department::query()->where('name', 'Computer Science')->value('id');

        Livewire::actingAs($user)
            ->test(SetupProgramPage::class)
            ->set('department_id', $departmentId)
            ->set('name', 'BSc CS')
            ->set('certificate', 'Bachelor')
            ->set('cost', '5000')
            ->set('program_length', 4)
            ->call('saveProgram')
            ->assertHasNoErrors();

        Livewire::actingAs($user)
            ->test(SetupHallPage::class)
            ->set('name', 'Hall A')
            ->set('cost', '1200')
            ->set('period', 'per_year')
            ->call('saveHall')
            ->assertHasNoErrors();

        Queue::fake();

        Livewire::actingAs($user)
            ->test(SetupActivatePage::class)
            ->call('setReady', true)
            ->assertHasNoErrors();

        Queue::assertPushed(SendCollegeNotificationMailJob::class, function (SendCollegeNotificationMailJob $job) use ($user): bool {
            return $job->toAddress === $user->email
                && str_contains($job->htmlBody, 'activated');
        });

        $school = School::query()->first();
        $this->assertNotNull($school);
        $this->assertTrue($school->ready);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk();

        $this->assertFalse(session('admin_register', false));
    }
}
