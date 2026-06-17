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
            ->set('search', 'IDX100')
            ->assertSee('IDX100')
            ->assertDontSee('IDX200');
    }
}
