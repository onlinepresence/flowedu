<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Students\MedicalRecordsPage;
use App\Models\Hall;
use App\Models\MedicalHistory;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\ActsAsOwnerAdmin;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class MedicalRecordsPageTest extends TestCase
{
    use ActsAsOwnerAdmin;
    use CreatesTestSchool;
    use RefreshDatabase;

    private function seedStudent(): Student
    {
        $hall = Hall::query()->create([
            'name' => 'Hall',
            'cost' => 0,
            'period' => 'per_year',
        ]);
        $user = User::factory()->create(['type' => 'student']);

        return Student::query()->forceCreate([
            'user_id' => $user->id,
            'index_number' => 'MED01',
            'admission_index' => 'MED01',
            'lastname' => 'Patient',
            'firstname' => 'Pat',
            'date_of_birth' => '2001-01-01',
            'gender' => 'female',
            'nationality' => 'GH',
            'contact_address' => 'Addr',
            'phone_number' => '0240000000',
            'hall_id' => $hall->id,
            'profile_pic' => 'p.png',
            'approved' => true,
        ]);
    }

    public function test_guest_cannot_view_medical_admin_page(): void
    {
        $this->get(route('admin.students.medical'))->assertRedirect();
    }

    public function test_admin_can_save_medical_for_student(): void
    {
        $admin = $this->actingOwnerAdmin();
        $student = $this->seedStudent();

        Livewire::actingAs($admin)
            ->test(MedicalRecordsPage::class)
            ->call('selectMedicalStudent', $student->id)
            ->set('allergies', 'Peanuts')
            ->set('insurance_number', 'INS-99')
            ->set('medical_conditions', 'Asthma')
            ->set('medications', 'Inhaler')
            ->set('immunization_records', 'Covid 2024')
            ->set('emergency_contacts', 'Mom — 024')
            ->call('saveMedical')
            ->assertHasNoErrors();

        $student->refresh();
        $this->assertSame('Peanuts', $student->allergy);
        $this->assertSame('INS-99', $student->insurance_number);

        $hist = MedicalHistory::query()->where('student_id', $student->id)->first();
        $this->assertNotNull($hist);
        $this->assertSame('Asthma', $hist->medical_conditions);
        $this->assertSame('Peanuts', $hist->allergies);
        $this->assertSame('Inhaler', $hist->medications);
    }
}
