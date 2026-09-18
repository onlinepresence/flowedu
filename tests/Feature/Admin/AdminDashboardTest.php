<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\AdminDashboardPage;
use App\Models\Admin;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\AdminSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    private function actingUserWithRole(string $roleName, array $adminAttributes = []): User
    {
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();

        $roleId = UserRole::query()->where('name', $roleName)->value('id');
        if (! $roleId) {
            // Fallback for custom roles or new names
            $roleId = UserRole::query()->where('name', 'owner')->value('id');
        }

        $user = User::factory()->create([
            'type' => 'admin',
        ]);

        $admin = new Admin;
        $admin->user_id = $user->id;
        $admin->type = $roleId;
        foreach ($adminAttributes as $key => $value) {
            $admin->{$key} = $value;
        }
        $admin->save();

        return $user;
    }

    public function test_owner_resolves_to_executive_archetype(): void
    {
        $user = $this->actingUserWithRole('owner');

        Livewire::actingAs($user)
            ->test(AdminDashboardPage::class)
            ->assertViewHas('archetype', 'executive')
            ->assertStatus(200);
    }

    public function test_hod_resolves_to_academic_archetype(): void
    {
        $user = $this->actingUserWithRole('hod');

        Livewire::actingAs($user)
            ->test(AdminDashboardPage::class)
            ->assertViewHas('archetype', 'academic');
    }

    public function test_finance_officer_resolves_to_finance_archetype(): void
    {
        $user = $this->actingUserWithRole('finance_officer');

        Livewire::actingAs($user)
            ->test(AdminDashboardPage::class)
            ->assertViewHas('archetype', 'finance');
    }

    public function test_dean_resolves_to_welfare_archetype(): void
    {
        $user = $this->actingUserWithRole('dean_of_students');

        Livewire::actingAs($user)
            ->test(AdminDashboardPage::class)
            ->assertViewHas('archetype', 'welfare');
    }

    public function test_hr_manager_resolves_to_hr_archetype(): void
    {
        $user = $this->actingUserWithRole('human_resource_manager');

        Livewire::actingAs($user)
            ->test(AdminDashboardPage::class)
            ->assertViewHas('archetype', 'hr');
    }

    public function test_auditor_resolves_to_audit_archetype(): void
    {
        $user = $this->actingUserWithRole('internal_auditor');

        Livewire::actingAs($user)
            ->test(AdminDashboardPage::class)
            ->assertViewHas('archetype', 'audit');
    }

    public function test_hod_scopes_courses_count_to_their_department(): void
    {
        $faculty = \App\Models\Faculty::query()->create(['name' => 'Faculty 1']);

        // Create 2 departments
        $dept1 = \App\Models\Department::query()->forceCreate([
            'name' => 'Dept 1',
            'faculty_id' => $faculty->id,
        ]);
        $dept2 = \App\Models\Department::query()->forceCreate([
            'name' => 'Dept 2',
            'faculty_id' => $faculty->id,
        ]);

        // Create programs in both
        $prog1 = \App\Models\Program::query()->forceCreate([
            'name' => 'Prog 1',
            'department_id' => $dept1->id,
            'certificate' => 'Cert 1',
            'cost' => 0,
            'program_length' => 4,
        ]);
        $prog2 = \App\Models\Program::query()->forceCreate([
            'name' => 'Prog 2',
            'department_id' => $dept2->id,
            'certificate' => 'Cert 2',
            'cost' => 0,
            'program_length' => 4,
        ]);

        // Create courses in both
        \App\Models\Course::query()->forceCreate([
            'name' => 'Course 1',
            'code' => 'C1',
            'program_id' => $prog1->id,
            'course_semester' => '1',
            'year_level' => '1',
        ]);
        \App\Models\Course::query()->forceCreate([
            'name' => 'Course 2',
            'code' => 'C2',
            'program_id' => $prog2->id,
            'course_semester' => '1',
            'year_level' => '1',
        ]);

        // Act as HOD of dept1
        $user = $this->actingUserWithRole('hod', ['department_id' => $dept1->id]);

        Livewire::actingAs($user)
            ->test(AdminDashboardPage::class)
            ->assertViewHas('coursesCount', 1)
            ->assertViewHas('programsCount', 1);
    }

    public function test_finance_stats_sum_correct_schema_columns(): void
    {
        // Regression: AdminDashboardPage summed invoices.total_amount (InvoiceItem's
        // column) and payments.amount (no such column). SQLite silently sums a missing
        // column as 0 while MySQL strict throws 1054 — so assert VALUES, not just 200.
        $user = $this->actingUserWithRole('owner');

        \App\Models\Invoice::query()->create([
            'invoice_number' => 'INV-REG-001',
            'vendor_name' => 'Regression Vendor',
            'amount' => 500.00,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addMonth()->toDateString(),
        ]);

        $faculty = \App\Models\Faculty::query()->create(['name' => 'Finance Faculty']);
        $dept = \App\Models\Department::query()->forceCreate([
            'name' => 'Finance Dept',
            'faculty_id' => $faculty->id,
        ]);
        $program = \App\Models\Program::query()->forceCreate([
            'name' => 'Finance Prog',
            'department_id' => $dept->id,
            'certificate' => 'BSc',
            'cost' => 1000,
            'program_length' => 4,
        ]);
        $session = \App\Models\AcademicSession::query()->create([
            'name' => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-06-30',
            'is_current' => true,
        ]);
        $structure = \App\Models\FeeStructure::query()->create([
            'program_id' => $program->id,
            'level' => 100,
            'session_id' => $session->id,
            'tuition_fee' => 1000.00,
            'total_amount' => 1000.00,
            'created_by' => $user->id,
        ]);
        $studentUser = User::factory()->create(['type' => 'student']);
        $hall = \App\Models\Hall::query()->create([
            'name' => 'Finance Hall',
            'cost' => 0,
            'period' => 'per_year',
        ]);
        $student = \App\Models\Student::query()->forceCreate([
            'user_id' => $studentUser->id,
            'index_number' => 'FIN1',
            'admission_index' => 'FIN1',
            'lastname' => 'Pay',
            'firstname' => 'Pat',
            'date_of_birth' => '2001-01-01',
            'gender' => 'female',
            'nationality' => 'GH',
            'contact_address' => 'Addr',
            'phone_number' => '0240000009',
            'profile_pic' => 'p.png',
            'approved' => true,
            'department_id' => $dept->id,
            'hall_id' => $hall->id,
        ]);
        \App\Models\Payment::query()->create([
            'student_id' => $student->id,
            'fee_structure_id' => $structure->id,
            'amount_paid' => 200.00,
        ]);

        Livewire::actingAs($user)
            ->test(AdminDashboardPage::class)
            ->assertViewHas('totalInvoiced', 500)
            ->assertViewHas('totalCollected', 200)
            ->assertViewHas('totalOutstanding', 300)
            ->assertStatus(200);
    }

    public function test_dean_scopes_students_to_their_faculty(): void
    {
        // Create 2 faculties
        $faculty1 = \App\Models\Faculty::query()->create(['name' => 'Faculty 1']);
        $faculty2 = \App\Models\Faculty::query()->create(['name' => 'Faculty 2']);

        // Create departments
        $dept1 = \App\Models\Department::query()->forceCreate([
            'name' => 'Dept 1',
            'faculty_id' => $faculty1->id,
        ]);
        $dept2 = \App\Models\Department::query()->forceCreate([
            'name' => 'Dept 2',
            'faculty_id' => $faculty2->id,
        ]);

        $user1 = User::factory()->create(['type' => 'student']);
        $user2 = User::factory()->create(['type' => 'student']);

        $hall = \App\Models\Hall::query()->create([
            'name' => 'Hall 1',
            'cost' => 0,
            'period' => 'per_year',
        ]);

        // Create students in both
        \App\Models\Student::query()->forceCreate([
            'user_id' => $user1->id,
            'index_number' => 'S1',
            'admission_index' => 'S1',
            'lastname' => 'Doe',
            'firstname' => 'Jane',
            'date_of_birth' => '2001-01-01',
            'gender' => 'female',
            'nationality' => 'GH',
            'contact_address' => 'Addr',
            'phone_number' => '0240000001',
            'profile_pic' => 'p.png',
            'approved' => true,
            'department_id' => $dept1->id,
            'hall_id' => $hall->id,
        ]);
        \App\Models\Student::query()->forceCreate([
            'user_id' => $user2->id,
            'index_number' => 'S2',
            'admission_index' => 'S2',
            'lastname' => 'Smith',
            'firstname' => 'John',
            'date_of_birth' => '2001-01-01',
            'gender' => 'male',
            'nationality' => 'GH',
            'contact_address' => 'Addr',
            'phone_number' => '0240000002',
            'profile_pic' => 'p.png',
            'approved' => true,
            'department_id' => $dept2->id,
            'hall_id' => $hall->id,
        ]);

        // Act as Dean of faculty1
        $user = $this->actingUserWithRole('dean_of_students', ['faculty_id' => $faculty1->id]);

        Livewire::actingAs($user)
            ->test(AdminDashboardPage::class)
            ->assertViewHas('approvedCount', 1);
    }
}



