<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Students\ApproveStudentPage;
use App\Models\Admin;
use App\Models\Hall;
use App\Models\ParentGuardian;
use App\Models\Student;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\AdminSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class ApproveStudentPageTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    private function seedPendingStudent(): Student
    {
        $hall = Hall::query()->create([
            'name' => 'Main Hall',
            'cost' => 0,
            'period' => 'per_year',
        ]);

        $user = User::factory()->create(['type' => 'student']);

        $student = Student::query()->forceCreate([
            'user_id' => $user->id,
            'index_number' => 'STU001',
            'admission_index' => 'STU001',
            'lastname' => 'Test',
            'date_of_birth' => '2001-05-05',
            'gender' => 'male',
            'nationality' => 'GH',
            'contact_address' => 'Addr',
            'phone_number' => '0240000000',
            'hall_id' => $hall->id,
            'profile_pic' => 'placeholder.png',
            'approved' => false,
            'is_new' => true,
        ]);

        ParentGuardian::query()->create([
            'student_id' => $student->id,
            'name' => 'Guardian One',
            'relationship' => 'Parent',
            'phone_number' => '0241111111',
        ]);

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

    public function test_admin_can_approve_student_when_guardian_complete_and_cap_allows(): void
    {
        $student = $this->seedPendingStudent();
        $admin = $this->actingOwnerAdmin();

        Livewire::actingAs($admin)
            ->test(ApproveStudentPage::class, [
                'index_number' => $student->index_number,
                'guardian' => '1',
                'id' => (string) $student->user_id,
            ])
            ->call('approve')
            ->assertHasNoErrors();

        $this->assertTrue($student->fresh()->approved);
    }

    public function test_approve_fails_when_guardian_flag_zero(): void
    {
        $student = $this->seedPendingStudent();
        $admin = $this->actingOwnerAdmin();

        Livewire::actingAs($admin)
            ->test(ApproveStudentPage::class, [
                'index_number' => $student->index_number,
                'guardian' => '0',
                'id' => (string) $student->user_id,
            ])
            ->call('approve')
            ->assertHasErrors(['approve']);
    }
}
