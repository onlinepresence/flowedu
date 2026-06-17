<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Grading\TranscriptIndexPage;
use App\Livewire\Student\StudentTranscriptPage;
use App\Models\AcademicSession;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Hall;
use App\Models\Program;
use App\Models\Student;
use App\Models\TranscriptRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Livewire\Livewire;
use Tests\Concerns\ActsAsOwnerAdmin;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class TranscriptRequestTest extends TestCase
{
    use ActsAsOwnerAdmin;
    use CreatesTestSchool;
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestSchool();
    }

    private function seedStudent(string $level = '400', bool $graduated = false): Student
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
            'index_number' => 'STU-TR1',
            'admission_index' => 'STU-TR1',
            'lastname' => 'Student',
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
            'current_year' => $level,
            'graduated' => $graduated,
        ]);
    }

    public function test_guest_cannot_access_transcript_routes(): void
    {
        $this->get(route('student.transcript'))->assertRedirect();
        $this->get(route('admin.grading.transcripts.index'))->assertRedirect();
    }

    public function test_ineligible_student_cannot_request_transcript(): void
    {
        $student = $this->seedStudent('200', false);

        Livewire::actingAs($student->user)
            ->test(StudentTranscriptPage::class)
            ->set('purpose', 'Job App')
            ->call('requestTranscript');

        $this->assertSame(0, TranscriptRequest::query()->count());
    }

    public function test_eligible_final_year_student_can_request_transcript(): void
    {
        $student = $this->seedStudent('400', false);

        Livewire::actingAs($student->user)
            ->test(StudentTranscriptPage::class)
            ->set('purpose', 'Grad School')
            ->call('requestTranscript')
            ->assertHasNoErrors()
            ->assertSet('purpose', '')
            ->assertDispatched('close-modal', 'request-transcript-modal');

        $this->assertSame(1, TranscriptRequest::query()->count());
        
        $req = TranscriptRequest::query()->first();
        $this->assertSame('pending', $req->status);
        $this->assertSame('Grad School', $req->purpose);
    }

    public function test_eligible_graduated_student_can_request_transcript(): void
    {
        $student = $this->seedStudent('400', true);

        Livewire::actingAs($student->user)
            ->test(StudentTranscriptPage::class)
            ->set('purpose', 'Job')
            ->call('requestTranscript')
            ->assertHasNoErrors();

        $this->assertSame(1, TranscriptRequest::query()->count());
    }

    public function test_admin_can_process_and_reject_requests(): void
    {
        $admin = $this->actingOwnerAdmin();
        $student = $this->seedStudent('400', false);

        $request = TranscriptRequest::query()->create([
            'student_id' => $student->id,
            'status' => 'pending',
            'purpose' => 'Employment',
        ]);

        // Process request
        Livewire::actingAs($admin)
            ->test(TranscriptIndexPage::class)
            ->call('markAsProcessed', $request->id)
            ->assertHasNoErrors();

        $request->refresh();
        $this->assertSame('processed', $request->status);
        $this->assertSame($admin->id, $request->processed_by);

        // Reject request
        $request2 = TranscriptRequest::query()->create([
            'student_id' => $student->id,
            'status' => 'pending',
            'purpose' => 'Personal Use',
        ]);

        Livewire::actingAs($admin)
            ->test(TranscriptIndexPage::class)
            ->set('rejectingRequestId', $request2->id)
            ->set('rejectionRemarks', 'Must settle library fees first.')
            ->call('submitRejection')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', 'reject-request-modal');

        $request2->refresh();
        $this->assertSame('rejected', $request2->status);
        $this->assertSame('Must settle library fees first.', $request2->remarks);
        $this->assertSame($admin->id, $request2->processed_by);
    }
}
