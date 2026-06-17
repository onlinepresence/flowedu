<?php

namespace Tests\Feature\Licence;

use App\Actions\Students\AssertStudentApprovalAllowedByLicence;
use App\Models\Hall;
use App\Models\SchoolLicence;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentLicenceCapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class StudentLicenceCapTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    public function test_cap_message_null_when_under_limit(): void
    {
        config(['licence.enforce' => true, 'licence.student_cap_mode' => 'block']);

        $school = $this->createTestSchool();
        SchoolLicence::forceCreate([
            'school_id' => $school->id,
            'max_active_students' => 10,
            'licence_start' => now()->toDateString(),
            'licence_end' => null,
            'support_until' => now()->addYear()->toDateString(),
            'notes' => null,
            'external_ref' => null,
            'licence_key' => null,
        ]);

        $service = app(StudentLicenceCapService::class);
        $this->assertNull($service->messageIfCannotApproveAnotherStudent());
    }

    public function test_cap_message_when_at_limit_in_block_mode(): void
    {
        config(['licence.enforce' => true, 'licence.student_cap_mode' => 'block']);

        $school = $this->createTestSchool();
        SchoolLicence::forceCreate([
            'school_id' => $school->id,
            'max_active_students' => 1,
            'licence_start' => now()->toDateString(),
            'licence_end' => null,
            'support_until' => now()->addYear()->toDateString(),
            'notes' => null,
            'external_ref' => null,
            'licence_key' => null,
        ]);

        $hall = Hall::forceCreate([
            'name' => 'Main',
            'master' => null,
            'cost' => 0,
            'period' => 'per_year',
        ]);

        $user = User::factory()->create(['active' => true, 'type' => 'student']);
        Student::forceCreate([
            'user_id' => $user->id,
            'index_number' => 'IDX001',
            'admission_index' => 'ADM001',
            'lastname' => 'Doe',
            'date_of_birth' => '2000-01-01',
            'gender' => 'male',
            'nationality' => 'GH',
            'contact_address' => 'Addr',
            'phone_number' => '000',
            'hall_id' => $hall->id,
            'profile_pic' => 'n/a',
            'approved' => true,
            'graduated' => false,
        ]);

        $service = app(StudentLicenceCapService::class);
        $this->assertSame(1, $service->activeStudentsCount());
        $this->assertNotNull($service->messageIfCannotApproveAnotherStudent());
        $this->assertStringContainsString('Active student limit', (string) $service->messageIfCannotApproveAnotherStudent());
    }

    public function test_warn_mode_does_not_block_message(): void
    {
        config(['licence.enforce' => true, 'licence.student_cap_mode' => 'warn']);

        $school = $this->createTestSchool();
        SchoolLicence::forceCreate([
            'school_id' => $school->id,
            'max_active_students' => 1,
            'licence_start' => now()->toDateString(),
            'licence_end' => null,
            'support_until' => now()->addYear()->toDateString(),
            'notes' => null,
            'external_ref' => null,
            'licence_key' => null,
        ]);

        $hall = Hall::forceCreate([
            'name' => 'Main',
            'master' => null,
            'cost' => 0,
            'period' => 'per_year',
        ]);

        $user = User::factory()->create(['active' => true, 'type' => 'student']);
        Student::forceCreate([
            'user_id' => $user->id,
            'index_number' => 'IDX002',
            'admission_index' => 'ADM002',
            'lastname' => 'Doe',
            'date_of_birth' => '2000-01-01',
            'gender' => 'male',
            'nationality' => 'GH',
            'contact_address' => 'Addr',
            'phone_number' => '000',
            'hall_id' => $hall->id,
            'profile_pic' => 'n/a',
            'approved' => true,
            'graduated' => false,
        ]);

        $service = app(StudentLicenceCapService::class);
        $this->assertNull($service->messageIfCannotApproveAnotherStudent());
    }

    public function test_assert_action_throws_when_blocked(): void
    {
        config(['licence.enforce' => true, 'licence.student_cap_mode' => 'block']);

        $school = $this->createTestSchool();
        SchoolLicence::forceCreate([
            'school_id' => $school->id,
            'max_active_students' => 1,
            'licence_start' => now()->toDateString(),
            'licence_end' => null,
            'support_until' => now()->addYear()->toDateString(),
            'notes' => null,
            'external_ref' => null,
            'licence_key' => null,
        ]);

        $hall = Hall::forceCreate([
            'name' => 'Main',
            'master' => null,
            'cost' => 0,
            'period' => 'per_year',
        ]);

        $user = User::factory()->create(['active' => true, 'type' => 'student']);
        Student::forceCreate([
            'user_id' => $user->id,
            'index_number' => 'IDX003',
            'admission_index' => 'ADM003',
            'lastname' => 'Doe',
            'date_of_birth' => '2000-01-01',
            'gender' => 'male',
            'nationality' => 'GH',
            'contact_address' => 'Addr',
            'phone_number' => '000',
            'hall_id' => $hall->id,
            'profile_pic' => 'n/a',
            'approved' => true,
            'graduated' => false,
        ]);

        $this->expectException(ValidationException::class);
        app(AssertStudentApprovalAllowedByLicence::class)();
    }
}
