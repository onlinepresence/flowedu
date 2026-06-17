<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Students\PromotionIndexPage;
use App\Models\AcademicSession;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Hall;
use App\Models\Program;
use App\Models\Promotion;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\ActsAsOwnerAdmin;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class PromotionIndexPageTest extends TestCase
{
    use ActsAsOwnerAdmin;
    use CreatesTestSchool;
    use RefreshDatabase;

    /**
     * @return array{0: Program, 1: Hall, 2: list<Student>}
     */
    private function seedSessionAndStudents(): array
    {
        $session = new AcademicSession;
        $session->name = '2025/26';
        $session->start_date = '2025-09-01';
        $session->end_date = '2026-08-31';
        $session->is_current = true;
        $session->save();

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

        $students = [];
        foreach (['S1', 'S2'] as $idx) {
            $user = User::factory()->create(['type' => 'student']);
            $students[] = Student::query()->forceCreate([
                'user_id' => $user->id,
                'index_number' => $idx,
                'admission_index' => $idx,
                'lastname' => 'Test',
                'firstname' => $idx,
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
                'current_year' => '100',
            ]);
        }

        return [$program, $hall, $students];
    }

    public function test_guest_cannot_view_promotion_page(): void
    {
        $this->get(route('admin.students.promotion'))->assertRedirect();
    }

    public function test_admin_can_preview_and_confirm_manual_promotion(): void
    {
        $admin = $this->actingOwnerAdmin();
        [$program,, $students] = $this->seedSessionAndStudents();

        Livewire::actingAs($admin)
            ->test(PromotionIndexPage::class)
            ->set('promotionMode', 'manual')
            ->set('fromLevel', '100')
            ->set('toLevel', '200')
            ->set('programFilter', (string) $program->id)
            ->call('previewPromotion')
            ->assertSet('showPreview', true)
            ->set('previewStudentIds', [$students[0]->id])
            ->call('confirmPromotion')
            ->assertHasNoErrors();

        $students[0]->refresh();
        $this->assertSame('200', (string) $students[0]->current_year);

        $students[1]->refresh();
        $this->assertSame('100', (string) $students[1]->current_year);

        $this->assertSame(1, Promotion::query()->count());
    }
}
