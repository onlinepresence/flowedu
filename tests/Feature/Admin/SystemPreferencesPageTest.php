<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Settings\SystemPreferencesPage;
use App\Livewire\Student\StudentResultsPage;
use App\Models\Admin;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Models\UserRole;
use App\Services\SchoolLicenceService;
use Database\Seeders\AdminSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class SystemPreferencesPageTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    private function actingSystemAdmin(): User
    {
        $this->seed(AdminSystemSeeder::class);
        $school = $this->createTestSchool();

        $roleId = UserRole::query()->where('name', 'system_admin')->value('id');
        $this->assertNotNull($roleId);

        $user = User::factory()->create([
            'type' => 'admin',
            'username' => 'sysprefadmin',
        ]);

        $admin = new Admin;
        $admin->user_id = $user->id;
        $admin->type = $roleId;
        $admin->save();

        return $user;
    }

    public function test_system_preferences_saved_and_persisted(): void
    {
        $user = $this->actingSystemAdmin();

        // Disable license enforcement in config for simplicity
        config(['licence.enforce' => false]);

        Livewire::actingAs($user)
            ->test(SystemPreferencesPage::class)
            ->set('student_grading_redirect', true)
            ->set('external_grading_url', 'https://grading.external-college.edu/dashboard')
            ->set('allow_student_self_registration', false)
            ->set('enable_email_notifications', true)
            ->call('saveSettings')
            ->assertRedirect(route('admin.settings.system-preferences'));

        $this->assertSame('1', Setting::query()->where('setting_key', 'system_preferences.student_grading_redirect')->value('setting_value'));
        $this->assertSame('0', Setting::query()->where('setting_key', 'system_preferences.allow_student_self_registration')->value('setting_value'));
        $this->assertSame('1', Setting::query()->where('setting_key', 'system_preferences.enable_email_notifications')->value('setting_value'));
    }

    public function test_teacher_tools_license_gating_forces_redirect_to_false(): void
    {
        $user = $this->actingSystemAdmin();

        // Enforce license and update database licence record to set teacher tools to false
        config(['licence.enforce' => true]);
        
        $school = \App\Models\School::first();
        // Trigger default licence creation if not exists
        app(SchoolLicenceService::class)->getLicenceRow();
        
        $licence = $school->licence;
        $licence->module_teacher_tools = false;
        $licence->save();
        
        app(SchoolLicenceService::class)->refresh();

        Livewire::actingAs($user)
            ->test(SystemPreferencesPage::class)
            ->set('student_grading_redirect', true)
            ->call('saveSettings');

        $this->assertSame('0', Setting::query()->where('setting_key', 'system_preferences.student_grading_redirect')->value('setting_value'));
    }

    public function test_results_page_redirects_when_preference_enabled(): void
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();

        Setting::query()->create([
            'category' => 'system_preferences',
            'setting_key' => 'system_preferences.student_grading_redirect',
            'setting_value' => '1',
            'data_type' => 'boolean',
            'description' => 'test',
        ]);

        Setting::query()->create([
            'category' => 'system_preferences',
            'setting_key' => 'system_preferences.external_grading_url',
            'setting_value' => 'https://grading.external-college.edu/dashboard',
            'data_type' => 'string',
            'description' => 'test',
        ]);

        $hall = \App\Models\Hall::create(['name' => 'Republic Hall', 'cost' => 500.00, 'period' => 'per_semester']);
        $studentUser = User::factory()->create(['type' => 'student']);
        
        \Illuminate\Database\Eloquent\Model::unguard();
        Student::create([
            'user_id' => $studentUser->id,
            'index_number' => 'STD001',
            'admission_index' => 'ADM-1001',
            'lastname' => 'Doe',
            'firstname' => 'John',
            'date_of_birth' => '2000-01-01',
            'gender' => 'male',
            'nationality' => 'Ghanaian',
            'religion' => 'Christian',
            'phone_number' => '+233 24 000 1111',
            'contact_address' => '10 Accra Road',
            'admission_date' => '2025-09-01',
            'current_year' => '100',
            'is_new' => false,
            'approved' => true,
            'hall_id' => $hall->id,
            'profile_pic' => 'images/auth/login-office.jpeg',
        ]);

        Livewire::actingAs($studentUser)
            ->test(StudentResultsPage::class)
            ->assertRedirect('https://grading.external-college.edu/dashboard');
    }

    public function test_registration_blocked_when_self_registration_disabled(): void
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();

        Setting::query()->create([
            'category' => 'system_preferences',
            'setting_key' => 'system_preferences.allow_student_self_registration',
            'setting_value' => '0',
            'data_type' => 'boolean',
            'description' => 'test',
        ]);

        $this->get(route('register'))
            ->assertStatus(403);
    }
}
