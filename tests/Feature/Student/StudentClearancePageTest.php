<?php

namespace Tests\Feature\Student;

use App\Models\Faculty;
use App\Models\Hall;
use App\Models\SchoolLicence;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class StudentClearancePageTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    public function test_final_year_student_sees_clearance_status_table(): void
    {
        config(['licence.enforce' => true]);

        $school = $this->createTestSchool();
        SchoolLicence::forceCreate([
            'school_id' => $school->id,
            'module_student_welfare' => true,
            'max_active_students' => null,
            'licence_start' => now()->toDateString(),
            'licence_end' => null,
            'support_until' => now()->addYear()->toDateString(),
            'notes' => null,
            'external_ref' => null,
            'licence_key' => null,
        ]);

        $hall = Hall::query()->create([
            'name' => 'Main Hall',
            'master' => null,
            'cost' => 0,
            'period' => 'per_year',
        ]);

        $faculty = Faculty::query()->create(['name' => 'Science']);

        $departmentId = DB::table('departments')->insertGetId([
            'name' => 'CS',
            'faculty_id' => $faculty->id,
            'hod' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $programId = DB::table('programs')->insertGetId([
            'name' => 'BSc CS',
            'department_id' => $departmentId,
            'certificate' => 'BSc',
            'cost' => 0,
            'program_length' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::factory()->create(['type' => 'student']);

        Student::unguarded(function () use ($user, $departmentId, $programId, $hall) {
            Student::query()->create([
                'user_id' => $user->id,
                'index_number' => 'STU001',
                'admission_index' => 'ADM001',
                'lastname' => 'Test',
                'department_id' => $departmentId,
                'program_id' => $programId,
                'date_of_birth' => '2000-01-01',
                'gender' => 'other',
                'nationality' => 'GH',
                'current_year' => '400',
                'contact_address' => 'Addr',
                'phone_number' => '0240000000',
                'hall_id' => $hall->id,
                'profile_pic' => 'default.png',
                'is_new' => false,
                'approved' => true,
            ]);
        });

        $this->actingAs($user)
            ->get(route('student.clearance'))
            ->assertOk()
            ->assertSee('Clearance Checklist', false)
            ->assertSee('Library', false);
    }

    public function test_non_final_year_student_sees_locked_message(): void
    {
        config(['licence.enforce' => true]);

        $school = $this->createTestSchool();
        SchoolLicence::forceCreate([
            'school_id' => $school->id,
            'module_student_welfare' => true,
            'max_active_students' => null,
            'licence_start' => now()->toDateString(),
            'licence_end' => null,
            'support_until' => now()->addYear()->toDateString(),
            'notes' => null,
            'external_ref' => null,
            'licence_key' => null,
        ]);

        $hall = Hall::query()->create([
            'name' => 'Main Hall',
            'master' => null,
            'cost' => 0,
            'period' => 'per_year',
        ]);

        $faculty = Faculty::query()->create(['name' => 'Science']);

        $departmentId = DB::table('departments')->insertGetId([
            'name' => 'CS',
            'faculty_id' => $faculty->id,
            'hod' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $programId = DB::table('programs')->insertGetId([
            'name' => 'BSc CS',
            'department_id' => $departmentId,
            'certificate' => 'BSc',
            'cost' => 0,
            'program_length' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::factory()->create(['type' => 'student']);

        Student::unguarded(function () use ($user, $departmentId, $programId, $hall) {
            Student::query()->create([
                'user_id' => $user->id,
                'index_number' => 'STU002',
                'admission_index' => 'ADM002',
                'lastname' => 'Test',
                'department_id' => $departmentId,
                'program_id' => $programId,
                'date_of_birth' => '2000-01-01',
                'gender' => 'other',
                'nationality' => 'GH',
                'current_year' => '100',
                'contact_address' => 'Addr',
                'phone_number' => '0240000000',
                'hall_id' => $hall->id,
                'profile_pic' => 'default.png',
                'is_new' => false,
                'approved' => true,
            ]);
        });

        $this->actingAs($user)
            ->get(route('student.clearance'))
            ->assertOk()
            ->assertSee('Clearance not available', false)
            ->assertSee('Level 400', false);
    }
}
