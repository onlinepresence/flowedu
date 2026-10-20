<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Jobs\SendCollegeNotificationMailJob;
use App\Livewire\Admin\Students\PendingStudentReviewModal;
use App\Models\Admin;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Hall;
use App\Models\ParentGuardian;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\AdminSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class PendingStudentReviewModalTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    private function seedPendingStudent(
        bool $withGuardian = true,
        bool $approved = false,
        bool $rejected = false,
        ?string $rejectionReason = null,
    ): Student {
        $faculty = Faculty::query()->create(['name' => 'Applied Sciences']);
        $department = Department::query()->forceCreate([
            'name' => 'Computer Science',
            'faculty_id' => $faculty->id,
        ]);
        $program = Program::query()->forceCreate([
            'name' => 'Computer Science',
            'department_id' => $department->id,
            'certificate' => 'HND',
            'cost' => 1000,
            'program_length' => 4,
        ]);
        $hall = Hall::query()->create([
            'name' => 'Main Hall',
            'cost' => 0,
            'period' => 'per_year',
        ]);

        $user = User::factory()->create(['type' => 'student']);

        $student = Student::query()->forceCreate([
            'user_id' => $user->id,
            'index_number' => 'ADM-2026-0001',
            'admission_index' => 'ADM-2026-0001',
            'lastname' => 'Mensah',
            'firstname' => 'Dzifa',
            'othernames' => 'Akosua',
            'date_of_birth' => '2005-05-05',
            'gender' => 'female',
            'nationality' => 'Ghanaian',
            'ghana_card' => 'GHA-123456789-0',
            'contact_address' => 'House 1, Accra',
            'phone_number' => '0240000000',
            'program_id' => $program->id,
            'department_id' => null,
            'hall_id' => $hall->id,
            'profile_pic' => 'placeholder.png',
            'approved' => $approved,
            'rejected' => $rejected,
            'rejection_reason' => $rejectionReason,
            'rejected_at' => $rejected ? now() : null,
            'is_new' => true,
            'current_year' => '100',
            'admission_date' => now()->toDateString(),
        ]);

        if ($withGuardian) {
            ParentGuardian::query()->create([
                'student_id' => $student->id,
                'name' => 'Kofi Mensah',
                'relationship' => 'Father',
                'address' => 'House 1, Accra',
                'phone_number' => '0241111111',
                'email' => 'guardian@example.com',
            ]);
        }

        return $student->fresh();
    }

    private function actingOwnerAdmin(): User
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();

        $roleId = UserRole::query()->where('name', 'owner')->value('id');
        $user = User::factory()->create([
            'type' => 'admin',
            'username' => 'owneradmin',
        ]);

        $admin = new Admin;
        $admin->user_id = $user->id;
        $admin->type = $roleId;
        $admin->save();

        return $user;
    }

    public function test_review_modal_opens_with_student_details(): void
    {
        $student = $this->seedPendingStudent();
        $admin = $this->actingOwnerAdmin();

        Livewire::actingAs($admin)
            ->test(PendingStudentReviewModal::class)
            ->call('open', $student->id)
            ->assertSet('studentId', $student->id)
            ->assertDispatched('open-modal', 'pending-student-review-modal')
            ->assertDispatched('pending-student-review-loaded', studentId: $student->id)
            ->assertSee('ADM-2026-0001')
            ->assertSee('Dzifa')
            ->assertSee('Kofi Mensah')
            ->assertSee('GHA-123456789-0');
    }

    public function test_review_modal_shows_profile_link_without_approve_action_for_approved_student(): void
    {
        $student = $this->seedPendingStudent(approved: true);
        $admin = $this->actingOwnerAdmin();

        Livewire::actingAs($admin)
            ->test(PendingStudentReviewModal::class)
            ->call('open', $student->id)
            ->assertSee('View Full Profile')
            ->assertDontSee('Approve Student')
            ->assertSeeHtml(route('admin.students.show', ['index_number' => $student->index_number]));
    }

    public function test_review_modal_approves_pending_student(): void
    {
        Queue::fake();

        $student = $this->seedPendingStudent();
        $admin = $this->actingOwnerAdmin();

        Livewire::actingAs($admin)
            ->test(PendingStudentReviewModal::class)
            ->call('open', $student->id)
            ->call('approve')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', 'pending-student-review-modal')
            ->assertDispatched('pending-student-approved');

        $student->refresh();

        $this->assertTrue($student->approved);
        $this->assertSame($student->index_number, $student->admission_index);
        $this->assertNotNull($student->department_id);

        Queue::assertPushed(SendCollegeNotificationMailJob::class, 2);
        Queue::assertPushed(
            SendCollegeNotificationMailJob::class,
            fn (SendCollegeNotificationMailJob $job): bool => $job->toAddress === 'guardian@example.com'
                && str_contains($job->mailSubject, 'approved'),
        );
    }

    public function test_review_modal_rejects_pending_student_with_reason_and_queues_emails(): void
    {
        Queue::fake();

        $student = $this->seedPendingStudent();
        $admin = $this->actingOwnerAdmin();

        Livewire::actingAs($admin)
            ->test(PendingStudentReviewModal::class)
            ->call('open', $student->id)
            ->set('rejectionReason', 'Supporting documents were incomplete.')
            ->call('reject')
            ->assertHasNoErrors()
            ->assertSet('studentId', null)
            ->assertDispatched('close-modal', 'pending-student-review-modal')
            ->assertDispatched('pending-student-rejected', studentId: $student->id);

        $student->refresh();

        $this->assertTrue($student->rejected);
        $this->assertFalse($student->approved);
        $this->assertSame('Supporting documents were incomplete.', $student->rejection_reason);
        $this->assertSame($admin->id, $student->rejected_by);
        $this->assertNotNull($student->rejected_at);

        Queue::assertPushed(SendCollegeNotificationMailJob::class, 2);
        Queue::assertPushed(
            SendCollegeNotificationMailJob::class,
            fn (SendCollegeNotificationMailJob $job): bool => $job->toAddress === 'guardian@example.com'
                && str_contains($job->mailSubject, 'update')
                && str_contains($job->htmlBody, 'Supporting documents were incomplete.'),
        );
    }

    public function test_review_modal_requires_rejection_reason(): void
    {
        Queue::fake();

        $student = $this->seedPendingStudent();
        $admin = $this->actingOwnerAdmin();

        Livewire::actingAs($admin)
            ->test(PendingStudentReviewModal::class)
            ->call('open', $student->id)
            ->set('rejectionReason', '')
            ->call('reject')
            ->assertHasErrors(['rejectionReason']);

        $this->assertFalse($student->fresh()->rejected);
        Queue::assertNothingPushed();
    }

    public function test_rejected_student_modal_is_read_only_and_shows_reason(): void
    {
        $student = $this->seedPendingStudent(
            rejected: true,
            rejectionReason: 'Entry requirements were not met.',
        );
        $admin = $this->actingOwnerAdmin();

        Livewire::actingAs($admin)
            ->test(PendingStudentReviewModal::class)
            ->call('open', $student->id)
            ->assertSee('Rejected Application')
            ->assertSee('Entry requirements were not met.')
            ->assertDontSee('Approve Student')
            ->assertDontSee('Reject Student');
    }

    public function test_review_modal_blocks_approval_without_guardian(): void
    {
        $student = $this->seedPendingStudent(withGuardian: false);
        $admin = $this->actingOwnerAdmin();

        Livewire::actingAs($admin)
            ->test(PendingStudentReviewModal::class)
            ->call('open', $student->id)
            ->call('approve')
            ->assertHasErrors(['approve'])
            ->assertSee('No guardian information has been provided');

        $this->assertFalse($student->fresh()->approved);
    }
}
