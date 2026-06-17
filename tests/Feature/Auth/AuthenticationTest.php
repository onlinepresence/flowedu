<?php

namespace Tests\Feature\Auth;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\Hall;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestSchool();
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.login');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.login', $user->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('post.login.redirect', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_users_can_authenticate_with_username(): void
    {
        $user = User::factory()->create([
            'username' => 'STU2024001',
        ]);

        $component = Volt::test('pages.auth.login')
            ->set('form.login', 'STU2024001')
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('post.login.redirect', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.login', $user->email)
            ->set('form.password', 'wrong-password');

        $component->call('login');

        $component
            ->assertHasErrors('form.login')
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_inactive_users_cannot_authenticate(): void
    {
        $user = User::factory()->create(['active' => false]);

        $component = Volt::test('pages.auth.login')
            ->set('form.login', $user->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component->assertHasErrors('form.login');
        $this->assertGuest();
    }

    public function test_student_can_login_with_index_number_as_username(): void
    {
        $faculty = Faculty::query()->create(['name' => 'F']);
        $department = Department::query()->forceCreate([
            'name' => 'D',
            'faculty_id' => $faculty->id,
        ]);
        $program = Program::query()->forceCreate([
            'name' => 'Prog',
            'department_id' => $department->id,
            'certificate' => 'Cert',
            'cost' => 100,
        ]);
        $hall = Hall::query()->create([
            'name' => 'Hall',
            'cost' => 0,
            'period' => 'per_year',
        ]);

        $indexNumber = 'ADM-IDX-99001';
        $user = User::factory()->create([
            'type' => 'student',
            'username' => $indexNumber,
        ]);

        Student::query()->forceCreate([
            'user_id' => $user->id,
            'index_number' => $indexNumber,
            'admission_index' => $indexNumber,
            'lastname' => 'Test',
            'firstname' => 'Student',
            'date_of_birth' => '2001-01-01',
            'gender' => 'male',
            'nationality' => 'GH',
            'contact_address' => 'Addr',
            'phone_number' => '0249876543',
            'hall_id' => $hall->id,
            'program_id' => $program->id,
            'profile_pic' => 'students/profiles/x.jpg',
            'ghana_card' => 'GHA-111111111-1',
            'approved' => true,
            'is_new' => false,
            'department_id' => $program->department_id,
        ]);

        $component = Volt::test('pages.auth.login')
            ->set('form.login', $indexNumber)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('post.login.redirect', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_navigation_menu_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get('/profile');

        $response
            ->assertOk()
            ->assertSeeVolt('layout.navigation');
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('layout.navigation');

        $component->call('logout');

        $component
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
