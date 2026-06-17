<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Students\GraduationIndexPage;
use App\Models\AcademicSession;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Graduation;
use App\Models\Hall;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\ActsAsOwnerAdmin;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class GraduationIndexPageTest extends TestCase
{
    use ActsAsOwnerAdmin;
    use CreatesTestSchool;
    use RefreshDatabase;

    private function seedGraduationScenario(): AcademicSession
    {
        $session = new AcademicSession;
        $session->name = '2025/26';
        $session->start_date = '2025-09-01';
        $session->end_date = '2026-08-31';
        $session->is_current = true;
        $session->save();

        return $session;
    }

    private function seedFinalYearStudent(Program $program, Hall $hall, Department $department): Student
    {
        $user = User::factory()->create(['type' => 'student']);

        return Student::query()->forceCreate([
            'user_id' => $user->id,
            'index_number' => 'GRAD1',
            'admission_index' => 'GRAD1',
            'lastname' => 'Grad',
            'firstname' => 'Guy',
            'date_of_birth' => '2001-01-01',
            'gender' => 'male',
            'nationality' => 'GH',
            'contact_address' => 'Addr',
            'phone_number' => '0240000000',
            'hall_id' => $hall->id,
            'profile_pic' => 'p.png',
            'approved' => true,
            'department_id' => $department->id,
            'program_id' => $program->id,
            'current_year' => '400',
        ]);
    }

    public function test_guest_cannot_view_graduation_page(): void
    {
        $this->get(route('admin.students.graduation'))->assertRedirect();
    }

    public function test_admin_can_process_graduation(): void
    {
        $admin = $this->actingOwnerAdmin();
        $session = $this->seedGraduationScenario();

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

        $student = $this->seedFinalYearStudent($program, $hall, $department);

        Livewire::actingAs($admin)
            ->test(GraduationIndexPage::class)
            ->set('processLevel', '400')
            ->set('processSessionId', (string) $session->id)
            ->set('graduationDate', '2026-06-01')
            ->call('processGraduation')
            ->assertHasNoErrors();

        $student->refresh();
        $this->assertTrue($student->graduated);
        $this->assertSame(1, Graduation::query()->count());
    }
}
