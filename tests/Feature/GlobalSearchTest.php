<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Navigation\GlobalSearch;
use App\Models\Hall;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\ActsAsOwnerAdmin;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use ActsAsOwnerAdmin;
    use CreatesTestSchool;
    use RefreshDatabase;

    public function test_guest_renders_empty_page_list(): void
    {
        $this->createTestSchool();

        Livewire::test(GlobalSearch::class)
            ->assertViewHas('allPages', []);
    }

    public function test_admin_receives_all_admin_pages(): void
    {
        $admin = $this->actingOwnerAdmin();
        $this->actingAs($admin);

        Livewire::test(GlobalSearch::class)
            ->assertViewHas('allPages', function ($pages) {
                $labels = array_column($pages, 'label');
                return in_array('Dashboard', $labels, true);
            });
    }

    public function test_admin_pages_contain_correct_url_for_dashboard(): void
    {
        $admin = $this->actingOwnerAdmin();
        $this->actingAs($admin);

        Livewire::test(GlobalSearch::class)
            ->assertViewHas('allPages', function ($pages) {
                $dashboard = array_filter($pages, fn($p) => $p['label'] === 'Dashboard');
                return count($dashboard) > 0
                    && reset($dashboard)['url'] === route('admin.dashboard');
            });
    }

    public function test_teacher_receives_teacher_pages(): void
    {
        $this->createTestSchool();
        $user = User::factory()->create([
            'type'     => 'teacher',
            'username' => 'teacher1',
        ]);
        $teacher = new Teacher;
        $teacher->user_id = $user->id;
        $teacher->lastname = 'Doe';
        $teacher->othernames = 'Jane';
        $teacher->save();

        $this->actingAs($user);

        Livewire::test(GlobalSearch::class)
            ->assertViewHas('allPages', function ($pages) {
                $labels = array_column($pages, 'label');
                return in_array('My Profile', $labels, true);
            });
    }

    public function test_student_receives_student_pages(): void
    {
        $this->createTestSchool();
        $user = User::factory()->create([
            'type'     => 'student',
            'username' => 'student1',
        ]);

        $this->createApprovedStudent($user);
        $this->actingAs($user);

        Livewire::test(GlobalSearch::class)
            ->assertViewHas('allPages', function ($pages) {
                $labels = array_column($pages, 'label');
                return in_array('Academic › My Results', $labels, true);
            });
    }

    public function test_student_does_not_receive_admin_pages(): void
    {
        $this->createTestSchool();
        $user = User::factory()->create([
            'type'     => 'student',
            'username' => 'student1',
        ]);

        $this->createApprovedStudent($user);
        $this->actingAs($user);

        Livewire::test(GlobalSearch::class)
            ->assertViewHas('allPages', function ($pages) {
                // "Grade Points" and similar admin-only pages must not appear
                $labels = array_column($pages, 'label');
                return ! in_array('Grading › Grade Points', $labels, true);
            });
    }

    // -------------------------------------------------------------------------

    private function createApprovedStudent(User $user): Student
    {
        $hall = Hall::query()->create([
            'name'   => 'Test Hall',
            'cost'   => 0,
            'period' => 'per_year',
        ]);

        return Student::query()->forceCreate([
            'user_id'         => $user->id,
            'index_number'    => 'STU123',
            'admission_index' => 'ADM123',
            'lastname'        => 'Smith',
            'firstname'       => 'John',
            'date_of_birth'   => '2001-01-01',
            'gender'          => 'male',
            'nationality'     => 'GH',
            'contact_address' => 'Addr',
            'phone_number'    => '0249876543',
            'hall_id'         => $hall->id,
            'profile_pic'     => 'students/profiles/x.jpg',
            'approved'        => true,
            'is_new'          => false,
        ]);
    }
}
