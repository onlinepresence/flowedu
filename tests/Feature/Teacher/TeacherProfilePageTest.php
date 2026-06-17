<?php

declare(strict_types=1);

namespace Tests\Feature\Teacher;

use App\Livewire\Teacher\TeacherProfilePage;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class TeacherProfilePageTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    public function test_onboarded_teacher_can_view_profile(): void
    {
        $this->createTestSchool();

        $user = User::factory()->create([
            'type' => 'teacher',
            'username' => 'staff01',
        ]);

        $this->createFullTeacher($user->id);

        $this->actingAs($user)
            ->get(route('teacher.profile'))
            ->assertOk();
    }

    public function test_teacher_can_save_profile_changes(): void
    {
        $this->createTestSchool();

        $user = User::factory()->create([
            'type' => 'teacher',
            'username' => 'staff01',
        ]);

        $this->createFullTeacher($user->id);

        Livewire::actingAs($user)
            ->test(TeacherProfilePage::class)
            ->set('lastname', 'Jones')
            ->set('research_interests', 'AI in education')
            ->call('save')
            ->assertHasNoErrors();

        $teacher = Teacher::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($teacher);
        $this->assertSame('Jones', $teacher->lastname);
        $this->assertSame('AI in education', $teacher->research_interests);
    }

    public function test_sets_username_from_staff_id_when_both_were_empty(): void
    {
        $this->createTestSchool();

        $user = User::factory()->create([
            'type' => 'teacher',
            'username' => null,
        ]);

        Teacher::query()->forceCreate([
            'user_id' => $user->id,
            'lastname' => 'Smith',
            'othernames' => 'Alex',
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'nationality' => 'Ghanaian',
            'ghana_card' => 'GHA-123456789-1',
            'contact_address' => 'Box 1',
            'phone_number' => '0241234567',
            'staff_id' => null,
            'specialization' => 'Mathematics',
            'years_experience' => 3,
            'date_of_appointment' => '2021-06-01',
            'employment_type' => 'Full-time',
            'password_reset_required' => false,
            'is_onboarded' => true,
        ]);

        Livewire::actingAs($user)
            ->test(TeacherProfilePage::class)
            ->set('staff_id', 'NEWSTAFF')
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertSame('NEWSTAFF', $user->username);
    }

    public function test_profile_photo_route_streams_when_file_exists(): void
    {
        $this->createTestSchool();
        Storage::fake('college_uploads');

        $user = User::factory()->create([
            'type' => 'teacher',
            'username' => 'staff01',
        ]);

        $path = 'teachers/profiles/test.jpg';
        Storage::disk('college_uploads')->put($path, 'fake-image');

        Teacher::query()->forceCreate([
            'user_id' => $user->id,
            'lastname' => 'Smith',
            'othernames' => 'Alex',
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'nationality' => 'Ghanaian',
            'ghana_card' => 'GHA-123456789-1',
            'contact_address' => 'Box 1',
            'phone_number' => '0241234567',
            'staff_id' => 'ST01',
            'specialization' => 'Mathematics',
            'years_experience' => 3,
            'date_of_appointment' => '2021-06-01',
            'employment_type' => 'Full-time',
            'profile_pic' => $path,
            'password_reset_required' => false,
            'is_onboarded' => true,
        ]);

        $this->actingAs($user)
            ->get(route('teacher.profile.photo'))
            ->assertOk();
    }

    public function test_document_route_downloads_cv_when_present(): void
    {
        $this->createTestSchool();
        Storage::fake('college_uploads');

        $user = User::factory()->create([
            'type' => 'teacher',
            'username' => 'staff01',
        ]);

        $path = 'teachers/cv/doc.pdf';
        Storage::disk('college_uploads')->put($path, '%PDF-1.4 fake');

        Teacher::query()->forceCreate([
            'user_id' => $user->id,
            'lastname' => 'Smith',
            'othernames' => 'Alex',
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'nationality' => 'Ghanaian',
            'ghana_card' => 'GHA-123456789-1',
            'contact_address' => 'Box 1',
            'phone_number' => '0241234567',
            'staff_id' => 'ST01',
            'specialization' => 'Mathematics',
            'years_experience' => 3,
            'date_of_appointment' => '2021-06-01',
            'employment_type' => 'Full-time',
            'cv' => $path,
            'password_reset_required' => false,
            'is_onboarded' => true,
        ]);

        $this->actingAs($user)
            ->get(route('teacher.profile.document', ['type' => 'cv']))
            ->assertOk();
    }

    private function createFullTeacher(int $userId): Teacher
    {
        return Teacher::query()->forceCreate([
            'user_id' => $userId,
            'lastname' => 'Smith',
            'othernames' => 'Alex',
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'nationality' => 'Ghanaian',
            'ghana_card' => 'GHA-123456789-1',
            'contact_address' => 'Box 1',
            'phone_number' => '0241234567',
            'staff_id' => 'STAFF01',
            'specialization' => 'Mathematics',
            'years_experience' => 3,
            'date_of_appointment' => '2021-06-01',
            'employment_type' => 'Full-time',
            'password_reset_required' => false,
            'is_onboarded' => true,
        ]);
    }
}
