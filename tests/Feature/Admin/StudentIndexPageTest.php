<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Students\StudentIndex;
use App\Models\Hall;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\ActsAsOwnerAdmin;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class StudentIndexPageTest extends TestCase
{
    use ActsAsOwnerAdmin;
    use CreatesTestSchool;
    use RefreshDatabase;

    public function test_guest_cannot_view_student_index(): void
    {
        $this->get(route('admin.students.index'))->assertRedirect();
    }

    public function test_admin_can_view_student_index_and_search(): void
    {
        $admin = $this->actingOwnerAdmin();

        $hall = Hall::query()->create([
            'name' => 'Hall A',
            'cost' => 0,
            'period' => 'per_year',
        ]);

        $u1 = User::factory()->create(['type' => 'student']);
        Student::query()->forceCreate([
            'user_id' => $u1->id,
            'index_number' => 'IDX100',
            'admission_index' => 'IDX100',
            'lastname' => 'Zebra',
            'firstname' => 'Zoe',
            'date_of_birth' => '2001-01-01',
            'gender' => 'female',
            'nationality' => 'GH',
            'contact_address' => 'Addr',
            'phone_number' => '0240000001',
            'hall_id' => $hall->id,
            'profile_pic' => 'p.png',
            'approved' => true,
        ]);

        $u2 = User::factory()->create(['type' => 'student']);
        Student::query()->forceCreate([
            'user_id' => $u2->id,
            'index_number' => 'IDX200',
            'admission_index' => 'IDX200',
            'lastname' => 'Alpha',
            'firstname' => 'Amy',
            'date_of_birth' => '2001-02-02',
            'gender' => 'male',
            'nationality' => 'GH',
            'contact_address' => 'Addr',
            'phone_number' => '0240000002',
            'hall_id' => $hall->id,
            'profile_pic' => 'p.png',
            'approved' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(StudentIndex::class)
            ->assertSee('Import Students')
            ->assertSeeHtml('aria-disabled="true"')
            ->assertSee('IDX100')
            ->assertSee('IDX200')
            ->assertSee('Quick View')
            ->assertSee('Print Record')
            ->assertSee('View Profile')
            ->assertSee('Edit Student')
            ->assertSee('Delete Student')
            ->set('search', 'IDX100')
            ->assertSee('IDX100')
            ->assertDontSee('IDX200');
    }

    public function test_student_index_filters_rejected_students(): void
    {
        $admin = $this->actingOwnerAdmin();

        $hall = Hall::query()->create([
            'name' => 'Hall A',
            'cost' => 0,
            'period' => 'per_year',
        ]);

        $rejectedUser = User::factory()->create(['type' => 'student']);
        Student::query()->forceCreate([
            'user_id' => $rejectedUser->id,
            'index_number' => 'IDX401',
            'admission_index' => 'IDX401',
            'lastname' => 'Rejected',
            'firstname' => 'Rita',
            'date_of_birth' => '2001-04-04',
            'gender' => 'female',
            'nationality' => 'GH',
            'contact_address' => 'Addr',
            'phone_number' => '0240000004',
            'hall_id' => $hall->id,
            'profile_pic' => 'p.png',
            'approved' => false,
            'rejected' => true,
            'rejection_reason' => 'Documents incomplete.',
            'rejected_at' => now(),
        ]);

        $pendingUser = User::factory()->create(['type' => 'student']);
        Student::query()->forceCreate([
            'user_id' => $pendingUser->id,
            'index_number' => 'IDX402',
            'admission_index' => 'IDX402',
            'lastname' => 'Pending',
            'firstname' => 'Paul',
            'date_of_birth' => '2001-05-05',
            'gender' => 'male',
            'nationality' => 'GH',
            'contact_address' => 'Addr',
            'phone_number' => '0240000005',
            'hall_id' => $hall->id,
            'profile_pic' => 'p.png',
            'approved' => false,
            'rejected' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(StudentIndex::class)
            ->set('approval', 'rejected')
            ->assertSee('IDX401')
            ->assertDontSee('IDX402')
            ->assertSee('Rejected');
    }

    public function test_edit_modal_prefills_and_preselects_stored_gender(): void
    {
        $admin = $this->actingOwnerAdmin();

        $hall = Hall::query()->create([
            'name' => 'Hall A',
            'cost' => 0,
            'period' => 'per_year',
        ]);

        $user = User::factory()->create(['type' => 'student']);
        $student = Student::query()->forceCreate([
            'user_id' => $user->id,
            'index_number' => 'IDX300',
            'admission_index' => 'IDX300',
            'lastname' => 'Female',
            'firstname' => 'Fiona',
            'date_of_birth' => '2001-03-03',
            'gender' => 'female',
            'nationality' => 'GH',
            'contact_address' => 'Addr',
            'phone_number' => '0240000003',
            'hall_id' => $hall->id,
            'profile_pic' => 'p.png',
            'approved' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(StudentIndex::class)
            ->call('editStudent', $student->id)
            ->assertSet('editGender', 'female')
            ->assertSeeHtml('<option value="female">')
            ->assertDontSeeHtml('<option value="Female">');
    }
}
