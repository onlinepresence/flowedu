<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Students\DisciplineRecordsPage;
use App\Models\Department;
use App\Models\DisciplinaryRecord;
use App\Models\Faculty;
use App\Models\Hall;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\ActsAsOwnerAdmin;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class DisciplineRecordsPageTest extends TestCase
{
    use ActsAsOwnerAdmin;
    use CreatesTestSchool;
    use RefreshDatabase;

    private function seedStudentWithProgram(): Student
    {
        $faculty = Faculty::query()->create(['name' => 'F']);
        $department = Department::query()->forceCreate([
            'name' => 'Dept',
            'faculty_id' => $faculty->id,
        ]);
        $program = Program::query()->create([
            'name' => 'Prog',
            'department_id' => $department->id,
            'certificate' => 'Cert',
            'cost' => 0,
            'program_length' => 4,
        ]);
        $hall = Hall::query()->create([
            'name' => 'Hall',
            'cost' => 0,
            'period' => 'per_year',
        ]);
        $user = User::factory()->create(['type' => 'student']);

        return Student::query()->forceCreate([
            'user_id' => $user->id,
            'index_number' => 'DISC01',
            'admission_index' => 'DISC01',
            'lastname' => 'Doe',
            'firstname' => 'Jane',
            'date_of_birth' => '2001-01-01',
            'gender' => 'female',
            'nationality' => 'GH',
            'contact_address' => 'Addr',
            'phone_number' => '0240000000',
            'hall_id' => $hall->id,
            'profile_pic' => 'p.png',
            'approved' => true,
            'department_id' => $department->id,
            'program_id' => $program->id,
        ]);
    }

    public function test_guest_cannot_view_discipline_page(): void
    {
        $this->get(route('admin.students.discipline'))->assertRedirect();
    }

    public function test_admin_can_add_disciplinary_record_and_close_case(): void
    {
        $admin = $this->actingOwnerAdmin();
        $student = $this->seedStudentWithProgram();

        Livewire::actingAs($admin)
            ->test(DisciplineRecordsPage::class)
            ->set('disciplineStudentId', $student->id)
            ->set('offense', 'Late submission')
            ->set('action_taken', 'Warning')
            ->set('date_of_action', '2026-04-01')
            ->call('addRecord')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('disciplinary_records', [
            'index_number' => 'DISC01',
            'offense' => 'Late submission',
            'return_status' => 0,
        ]);

        $id = DisciplinaryRecord::query()->firstOrFail()->id;

        Livewire::actingAs($admin)
            ->test(DisciplineRecordsPage::class)
            ->call('confirmCloseRecord', $id)
            ->call('closeRecord');

        $this->assertDatabaseHas('disciplinary_records', [
            'id' => $id,
            'return_status' => 1,
        ]);
    }

    public function test_discipline_filters_narrow_results(): void
    {
        $admin = $this->actingOwnerAdmin();
        $student = $this->seedStudentWithProgram();

        DisciplinaryRecord::query()->create([
            'index_number' => $student->index_number,
            'fullname' => 'Doe Jane',
            'program_id' => $student->program_id,
            'offense' => 'Unique offense xyz',
            'action_taken' => 'Verbal',
            'comments' => null,
            'date_of_action' => '2026-04-02',
            'return_date' => null,
            'return_status' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(DisciplineRecordsPage::class)
            ->set('search', 'Unique offense xyz')
            ->assertSee('Unique offense xyz');

        Livewire::actingAs($admin)
            ->test(DisciplineRecordsPage::class)
            ->set('search', 'does-not-exist-zzz')
            ->assertSee('No disciplinary incidents recorded.');
    }
}
