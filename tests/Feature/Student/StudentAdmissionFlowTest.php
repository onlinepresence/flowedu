<?php

declare(strict_types=1);

namespace Tests\Feature\Student;

use App\Livewire\Student\StudentProfilePage;
use App\Livewire\Student\StudentSetupGuardianPage;
use App\Livewire\Student\StudentSetupPersonalPage;
use App\Livewire\Student\StudentSetupStatusPage;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Hall;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class StudentAdmissionFlowTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    private function seedGraph(): array
    {
        $this->createTestSchool();

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

        return compact('program', 'hall');
    }

    public function test_student_can_submit_admission_with_upload(): void
    {
        ['program' => $program, 'hall' => $hall] = $this->seedGraph();

        $user = User::factory()->create([
            'type' => 'student',
            'username' => null,
        ]);

        $tempPath = 'filepond-tmp/'.$user->id.'/passport.jpg';
        \Illuminate\Support\Facades\Storage::disk('local')->put($tempPath, UploadedFile::fake()->image('passport.jpg', 400, 500)->size(100)->get());

        Livewire::actingAs($user)
            ->test(StudentSetupPersonalPage::class)
            ->set('index_number', 'APP001')
            ->set('lastname', 'Doe')
            ->set('firstname', 'Jane')
            ->set('date_of_birth', '2000-05-05')
            ->set('nationality', 'GH')
            ->set('ghana_card', 'GHA-123456789-1')
            ->set('contact_address', 'Accra')
            ->set('phone_number', '0241234567')
            ->set('program_id', $program->id)
            ->set('hall_id', $hall->id)
            ->set('username', 'janedoe')
            ->set('gender', 'female')
            ->set('profilePicPond', $tempPath)
            ->call('save')
            ->assertHasNoErrors();

        $student = Student::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($student);
        $this->assertSame('APP001', $student->index_number);
        $this->assertStringStartsWith('students/profiles/', $student->profile_pic);
        $this->assertSame('janedoe', $user->fresh()->username);
    }

    public function test_guardian_save_and_activation_flow(): void
    {
        ['program' => $program, 'hall' => $hall] = $this->seedGraph();

        $user = User::factory()->create(['type' => 'student', 'username' => 'stu1']);
        $student = Student::query()->forceCreate([
            'user_id' => $user->id,
            'index_number' => 'PRE1',
            'admission_index' => 'PRE1',
            'lastname' => 'X',
            'firstname' => 'Y',
            'date_of_birth' => '2001-01-01',
            'gender' => 'male',
            'nationality' => 'GH',
            'contact_address' => 'Addr',
            'phone_number' => '0249876543',
            'hall_id' => $hall->id,
            'program_id' => $program->id,
            'profile_pic' => 'students/profiles/x.jpg',
            'ghana_card' => 'GHA-987654321-2',
            'approved' => true,
            'is_new' => true,
            'department_id' => $program->department_id,
        ]);

        Livewire::actingAs($user)
            ->test(StudentSetupGuardianPage::class)
            ->set('name', 'Guardian One')
            ->set('relationship', 'Parent')
            ->set('phone_number', '0241111111')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($student->parentGuardians()->exists());

        Livewire::actingAs($user)
            ->test(StudentSetupStatusPage::class)
            ->call('activate')
            ->assertHasNoErrors();

        $student->refresh();
        $this->assertFalse($student->is_new);
        $this->assertNotSame('PRE1', $student->index_number);
    }

    public function test_activation_fails_without_guardian(): void
    {
        ['program' => $program, 'hall' => $hall] = $this->seedGraph();

        $user = User::factory()->create(['type' => 'student']);
        Student::query()->forceCreate([
            'user_id' => $user->id,
            'index_number' => 'PRE2',
            'admission_index' => 'PRE2',
            'lastname' => 'X',
            'firstname' => 'Y',
            'date_of_birth' => '2001-01-01',
            'gender' => 'male',
            'nationality' => 'GH',
            'contact_address' => 'Addr',
            'phone_number' => '0249876544',
            'hall_id' => $hall->id,
            'program_id' => $program->id,
            'profile_pic' => 'students/profiles/x.jpg',
            'ghana_card' => 'GHA-987654322-2',
            'approved' => true,
            'is_new' => true,
            'department_id' => $program->department_id,
        ]);

        Livewire::actingAs($user)
            ->test(StudentSetupStatusPage::class)
            ->call('activate')
            ->assertHasErrors(['activate']);
    }

    public function test_profile_page_updates_student(): void
    {
        ['program' => $program, 'hall' => $hall] = $this->seedGraph();

        $user = User::factory()->create(['type' => 'student', 'username' => 'official']);
        Student::query()->forceCreate([
            'user_id' => $user->id,
            'index_number' => 'OFF1',
            'admission_index' => 'OFF1',
            'lastname' => 'Zed',
            'firstname' => 'Z',
            'date_of_birth' => '2001-02-02',
            'gender' => 'female',
            'nationality' => 'GH',
            'contact_address' => 'Addr',
            'phone_number' => '0242222222',
            'hall_id' => $hall->id,
            'program_id' => $program->id,
            'profile_pic' => 'students/profiles/old.jpg',
            'ghana_card' => 'GHA-111111111-1',
            'approved' => true,
            'is_new' => false,
            'department_id' => $program->department_id,
        ]);

        Livewire::actingAs($user)
            ->test(StudentProfilePage::class)
            ->set('lastname', 'ZedUpdated')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('ZedUpdated', Student::query()->where('user_id', $user->id)->value('lastname'));
    }
}
