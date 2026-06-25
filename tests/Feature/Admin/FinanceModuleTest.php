<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\AcademicSession;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\Hall;
use App\Models\Payment;
use App\Models\Program;
use App\Models\Scholarship;
use App\Models\ScholarshipRecipient;
use App\Models\Student;
use App\Models\User;
use App\Services\Finance\FeeCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private AcademicSession $session;
    private Program $program;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Database\Eloquent\Model::unguard();

        $this->adminUser = User::factory()->create(['type' => 'admin']);
        
        $this->session = AcademicSession::create([
            'name' => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-06-30',
            'is_current' => true,
        ]);

        $faculty = Faculty::create(['name' => 'Engineering']);
        $department = Department::create(['name' => 'IT', 'faculty_id' => $faculty->id]);
        
        $this->program = Program::create([
            'name' => 'BSc Computer Science',
            'department_id' => $department->id,
            'certificate' => 'BSc',
            'cost' => 1000.00,
            'program_length' => 4,
        ]);

        $hall = Hall::create(['name' => 'Republic Hall', 'cost' => 500.00, 'period' => 'per_semester']);

        $studentUser = User::factory()->create(['type' => 'student']);
        $this->student = Student::create([
            'user_id' => $studentUser->id,
            'index_number' => 'STD001',
            'admission_index' => 'ADM-1001',
            'lastname' => 'Doe',
            'firstname' => 'John',
            'department_id' => $department->id,
            'program_id' => $this->program->id,
            'date_of_birth' => '2000-01-01',
            'gender' => 'male',
            'current_year' => '100',
            'admission_date' => '2025-09-01',
            'hall_id' => $hall->id,
            'approved' => true,
            'nationality' => 'Ghanaian',
            'religion' => 'Christian',
            'is_new' => false,
            'contact_address' => '10 Accra Road',
            'phone_number' => '+233 24 000 1111',
            'profile_pic' => 'images/auth/login-office.jpeg',
        ]);
    }

    public function test_fee_structure_calculation_and_ledger_sync_without_scholarship(): void
    {
        $structure = FeeStructure::create([
            'program_id' => $this->program->id,
            'level' => 100,
            'session_id' => $this->session->id,
            'tuition_fee' => 1000.00,
            'library_fee' => 50.00,
            'lab_fee' => 100.00,
            'total_amount' => 1150.00,
            'created_by' => $this->adminUser->id,
        ]);

        $service = new FeeCalculationService();
        $service->syncFeePaymentLedger($this->student, $this->session);

        $ledger = FeePayment::where('student_id', $this->student->id)->first();
        
        $this->assertNotNull($ledger);
        $this->assertEquals(0.00, $ledger->amount_paid);
        $this->assertEquals(1650.00, $ledger->balance);
    }

    public function test_ledger_sync_with_full_scholarship(): void
    {
        $structure = FeeStructure::create([
            'program_id' => $this->program->id,
            'level' => 100,
            'session_id' => $this->session->id,
            'tuition_fee' => 1000.00,
            'library_fee' => 50.00,
            'total_amount' => 1050.00,
            'created_by' => $this->adminUser->id,
        ]);

        $scholarship = Scholarship::create([
            'name' => 'STEM Excellence Scholarship',
            'type' => 'scholarship',
            'amount' => 2000.00, // exceeds total billed
            'duration_semesters' => 8,
            'coverage_type' => 'full',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        ScholarshipRecipient::create([
            'student_id' => $this->student->id,
            'scholarship_id' => $scholarship->id,
            'amount_awarded' => 2000.00,
            'status' => 'approved',
            'award_date' => now()->toDateString(),
        ]);

        $service = new FeeCalculationService();
        $service->syncFeePaymentLedger($this->student, $this->session);

        $ledger = FeePayment::where('student_id', $this->student->id)->first();
        $this->assertNotNull($ledger);
        
        $this->assertEquals(0.00, $ledger->amount_paid);
        $this->assertEquals(0.00, $ledger->balance);
    }

    public function test_ledger_sync_with_tuition_only_scholarship(): void
    {
        $structure = FeeStructure::create([
            'program_id' => $this->program->id,
            'level' => 100,
            'session_id' => $this->session->id,
            'tuition_fee' => 1000.00,
            'library_fee' => 50.00,
            'total_amount' => 1050.00,
            'created_by' => $this->adminUser->id,
        ]);

        $scholarship = Scholarship::create([
            'name' => 'Tuition Waiver Scholarship',
            'type' => 'scholarship',
            'amount' => 1000.00,
            'duration_semesters' => 8,
            'coverage_type' => 'tuition_only',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        ScholarshipRecipient::create([
            'student_id' => $this->student->id,
            'scholarship_id' => $scholarship->id,
            'amount_awarded' => 1000.00,
            'status' => 'approved',
            'award_date' => now()->toDateString(),
        ]);

        $service = new FeeCalculationService();
        $service->syncFeePaymentLedger($this->student, $this->session);

        $ledger = FeePayment::where('student_id', $this->student->id)->first();
        $this->assertNotNull($ledger);

        $this->assertEquals(0.00, $ledger->amount_paid);
        $this->assertEquals(550.00, $ledger->balance);
    }

    public function test_ledger_sync_with_hostel_only_scholarship(): void
    {
        $structure = FeeStructure::create([
            'program_id' => $this->program->id,
            'level' => 100,
            'session_id' => $this->session->id,
            'tuition_fee' => 1000.00,
            'library_fee' => 50.00,
            'total_amount' => 1050.00,
            'created_by' => $this->adminUser->id,
        ]);

        $scholarship = Scholarship::create([
            'name' => 'Hostel Relief',
            'type' => 'scholarship',
            'amount' => 600.00,
            'duration_semesters' => 2,
            'coverage_type' => 'hostel_only',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        ScholarshipRecipient::create([
            'student_id' => $this->student->id,
            'scholarship_id' => $scholarship->id,
            'amount_awarded' => 600.00,
            'status' => 'approved',
            'award_date' => now()->toDateString(),
        ]);

        $service = new FeeCalculationService();
        $service->syncFeePaymentLedger($this->student, $this->session);

        $ledger = FeePayment::where('student_id', $this->student->id)->first();
        $this->assertNotNull($ledger);

        $this->assertEquals(1050.00, $ledger->balance);
    }

    public function test_payments_recording_reduces_outstanding_balance(): void
    {
        $structure = FeeStructure::create([
            'program_id' => $this->program->id,
            'level' => 100,
            'session_id' => $this->session->id,
            'tuition_fee' => 1000.00,
            'total_amount' => 1000.00,
            'created_by' => $this->adminUser->id,
        ]);

        $service = new FeeCalculationService();
        $service->syncFeePaymentLedger($this->student, $this->session);

        Payment::create([
            'student_id' => $this->student->id,
            'fee_structure_id' => $structure->id,
            'amount_paid' => 400.00,
            'payment_method' => 'Cash',
            'payment_date' => now()->toDateString(),
            'status' => 'completed',
        ]);

        $service->syncFeePaymentLedger($this->student, $this->session);

        $ledger = FeePayment::where('student_id', $this->student->id)->first();
        $this->assertEquals(400.00, $ledger->amount_paid);
        $this->assertEquals(1100.00, $ledger->balance);
    }

    public function test_semester_billing_cycle_halves_fees(): void
    {
        // Set billing cycle to semester
        \App\Models\Setting::create([
            'setting_key' => 'finance_settings.billing_cycle',
            'setting_value' => 'semester',
            'category' => 'finance_settings',
            'data_type' => 'string',
            'updated_by' => $this->adminUser->id,
        ]);

        $structure = FeeStructure::create([
            'program_id' => $this->program->id,
            'level' => 100,
            'session_id' => $this->session->id,
            'tuition_fee' => 1000.00,
            'library_fee' => 50.00,
            'total_amount' => 1050.00,
            'created_by' => $this->adminUser->id,
        ]);

        $service = new FeeCalculationService();
        $calcs = $service->calculateStudentFees($this->student, $this->session);

        // Expected gross fees: structure total (1050 / 2) + hostel cost (500 / 2) = 525 + 250 = 775.00
        $this->assertEquals(500.00, $calcs['tuition']);
        $this->assertEquals(525.00, $calcs['structure_total']);
        $this->assertEquals(250.00, $calcs['hostel_cost']);
        $this->assertEquals(775.00, $calcs['gross_fees']);
        $this->assertEquals(775.00, $calcs['balance']);
    }
}
