<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Finance\FeesIndex;
use App\Livewire\Admin\Finance\FinancePaymentsPage;
use App\Models\AcademicSession;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\FeeComponent;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\Hall;
use App\Models\Payment;
use App\Models\Program;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\ActsAsOwnerAdmin;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class FeesIndexPageTest extends TestCase
{
    use ActsAsOwnerAdmin;
    use CreatesTestSchool;
    use RefreshDatabase;

    public function test_admin_can_view_fees_index(): void
    {
        $admin = $this->actingOwnerAdmin();

        $faculty = Faculty::query()->create(['name' => 'F']);
        $department = Department::query()->forceCreate([
            'name' => 'Dept',
            'faculty_id' => $faculty->id,
        ]);

        $hall = Hall::query()->create([
            'name' => 'Hall',
            'cost' => 0,
            'period' => 'per_year',
        ]);

        $user = User::factory()->create(['type' => 'student']);
        $student = Student::query()->forceCreate([
            'user_id' => $user->id,
            'index_number' => 'FEE01',
            'admission_index' => 'FEE01',
            'lastname' => 'Payer',
            'othernames' => 'Pat',
            'date_of_birth' => '2001-01-01',
            'gender' => 'male',
            'nationality' => 'GH',
            'contact_address' => 'Addr',
            'phone_number' => '0240000111',
            'hall_id' => $hall->id,
            'profile_pic' => 'p.png',
            'approved' => true,
        ]);

        $academicSession = AcademicSession::query()->create([
            'name' => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-06-30',
            'is_current' => true,
        ]);

        $program = Program::query()->forceCreate([
            'name' => 'BSc Computer Science',
            'department_id' => $department->id,
            'certificate' => 'BSc',
            'cost' => 1500.00,
            'program_length' => 4,
        ]);

        $feeStructure = FeeStructure::query()->create([
            'program_id' => $program->id,
            'level' => 100,
            'session_id' => $academicSession->id,
            'tuition_fee' => 1000.00,
            'total_amount' => 1000.00,
            'created_by' => $admin->user_id,
        ]);

        $student->program_id = $program->id;
        $student->save();

        Payment::query()->create([
            'student_id' => $student->id,
            'fee_structure_id' => $feeStructure->id,
            'amount_paid' => 100.5,
            'payment_method' => 'Cash',
            'payment_date' => '2026-05-26',
            'reference_number' => 'REF-1234',
            'status' => 'completed',
            'received_by' => $admin->user_id,
        ]);

        FeePayment::query()->forceCreate([
            'lastname' => 'Payer',
            'othernames' => 'Pat',
            'class_level' => '100',
            'amount_paid' => 100.5,
            'balance' => 50,
            'student_id' => $student->id,
            'department_id' => $department->id,
        ]);

        Livewire::actingAs($admin)
            ->test(FeesIndex::class)
            ->assertSee('100.50')
            ->assertSee('50.00')
            ->assertSee('FEE01');
    }

    public function test_admin_can_save_receipt_settings(): void
    {
        $admin = $this->actingOwnerAdmin();

        Livewire::actingAs($admin)
            ->test(FeesIndex::class)
            ->set('receiptHeaderTitle', 'St. John College')
            ->set('receiptHeaderSubtitle', 'Fees Collection Receipt')
            ->set('receiptContactInfo', 'Tel: +233-111-222')
            ->set('receiptFooterNote', 'Please keep it safe.')
            ->set('receiptShowSignature', false)
            ->set('receiptShowStamp', false)
            ->call('saveReceiptSettings')
            ->assertHasNoErrors()
            ->assertDispatched('college-toast');

        $this->assertDatabaseHas('settings', [
            'setting_key' => 'finance.receipt_settings',
            'category' => 'finance',
        ]);

        $settingVal = json_decode(Setting::where('setting_key', 'finance.receipt_settings')->value('setting_value'), true);
        $this->assertEquals('St. John College', $settingVal['header_title']);
        $this->assertEquals('Fees Collection Receipt', $settingVal['header_subtitle']);
        $this->assertEquals('Tel: +233-111-222', $settingVal['contact_info']);
        $this->assertEquals('Please keep it safe.', $settingVal['footer_note']);
        $this->assertFalse($settingVal['show_signature']);
        $this->assertFalse($settingVal['show_stamp']);
    }

    public function test_payment_receipt_shows_custom_settings(): void
    {
        $admin = $this->actingOwnerAdmin();

        $faculty = Faculty::query()->create(['name' => 'F']);
        $department = Department::query()->forceCreate([
            'name' => 'Dept',
            'faculty_id' => $faculty->id,
        ]);

        $hall = Hall::query()->create([
            'name' => 'Hall',
            'cost' => 0,
            'period' => 'per_year',
        ]);

        $user = User::factory()->create(['type' => 'student']);
        $student = Student::query()->forceCreate([
            'user_id' => $user->id,
            'index_number' => 'FEE02',
            'admission_index' => 'FEE02',
            'lastname' => 'Payer',
            'othernames' => 'Pat',
            'date_of_birth' => '2001-01-01',
            'gender' => 'male',
            'nationality' => 'GH',
            'contact_address' => 'Addr',
            'phone_number' => '0240000111',
            'hall_id' => $hall->id,
            'profile_pic' => 'p.png',
            'approved' => true,
        ]);

        $academicSession = AcademicSession::query()->create([
            'name' => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-06-30',
            'is_current' => true,
        ]);

        $program = Program::query()->forceCreate([
            'name' => 'BSc Computer Science',
            'department_id' => $department->id,
            'certificate' => 'BSc',
            'cost' => 1500.00,
            'program_length' => 4,
        ]);

        $feeStructure = FeeStructure::query()->create([
            'program_id' => $program->id,
            'level' => 100,
            'session_id' => $academicSession->id,
            'tuition_fee' => 1000.00,
            'total_amount' => 1000.00,
            'created_by' => $admin->user_id,
        ]);

        $student->program_id = $program->id;
        $student->save();

        $payment = Payment::query()->create([
            'student_id' => $student->id,
            'fee_structure_id' => $feeStructure->id,
            'amount_paid' => 100.5,
            'payment_method' => 'Cash',
            'payment_date' => '2026-05-26',
            'reference_number' => 'REF-1234',
            'status' => 'completed',
            'received_by' => $admin->user_id,
        ]);

        // Save custom settings first
        Setting::query()->create([
            'setting_key' => 'finance.receipt_settings',
            'category' => 'finance',
            'setting_value' => json_encode([
                'header_title' => 'St. John College Test',
                'header_subtitle' => 'Custom Subtitle Test',
                'contact_info' => 'Contact Info Test',
                'footer_note' => 'Footer Note Test',
                'show_signature' => true,
                'show_stamp' => true,
            ]),
            'data_type' => 'json',
            'description' => 'Custom receipt layout settings',
            'updated_by' => $admin->user_id,
        ]);

        Livewire::actingAs($admin)
            ->test(FinancePaymentsPage::class)
            ->set('receiptPaymentId', $payment->id)
            ->assertSee('St. John College Test')
            ->assertSee('Custom Subtitle Test')
            ->assertSee('Contact Info Test')
            ->assertSee('Footer Note Test')
            ->assertSee('School Stamp')
            ->assertSee('Authorized Sign');
    }

    public function test_admin_can_add_custom_fee_component(): void
    {
        $admin = $this->actingOwnerAdmin();

        Livewire::actingAs($admin)
            ->test(FeesIndex::class)
            ->set('newComponentName', 'Practical Training Fee')
            ->set('newComponentPercentage', '3.50')
            ->call('addFeeComponent')
            ->assertHasNoErrors()
            ->assertSet('newComponentName', '');

        $this->assertDatabaseHas('fee_components', [
            'name' => 'Practical Training Fee',
            'field' => null,
            'default_percentage' => 3.50,
            'is_system' => false,
            'is_active' => true,
        ]);
    }

    public function test_autofill_uses_selected_components_and_removed_amount_rolls_into_tuition(): void
    {
        $admin = $this->actingOwnerAdmin();
        ['program' => $program] = $this->createFeeGraph();

        $custom = FeeComponent::query()->create([
            'name' => 'Practical Training Fee',
            'field' => null,
            'default_percentage' => 5.00,
            'is_active' => true,
            'is_system' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(FeesIndex::class)
            ->call('openStructureModal')
            ->set('program_id', (string) $program->id)
            ->set('selectedComponents', [[
                'id' => $custom->id,
                'name' => $custom->name,
                'field' => null,
                'amount' => '',
                'is_system' => false,
            ]])
            ->call('fillFromProgramCost')
            ->assertSet('tuition_fee', '700.00')
            ->assertSet('selectedComponents.0.amount', '50.00')
            ->call('removeComponent', 0)
            ->assertSet('tuition_fee', '750.00')
            ->assertSet('selectedComponents', []);
    }

    public function test_structure_saves_only_selected_components_and_syncs_items(): void
    {
        $admin = $this->actingOwnerAdmin();
        ['program' => $program, 'session' => $session] = $this->createFeeGraph();

        $library = FeeComponent::query()->where('field', 'library_fee')->firstOrFail();
        $custom = FeeComponent::query()->create([
            'name' => 'Practical Training Fee',
            'field' => null,
            'default_percentage' => 5.00,
            'is_active' => true,
            'is_system' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(FeesIndex::class)
            ->call('openStructureModal')
            ->set('program_id', (string) $program->id)
            ->set('level', '100')
            ->set('session_id', (string) $session->id)
            ->set('tuition_fee', '1000.00')
            ->set('selectedComponents', [
                [
                    'id' => $library->id,
                    'name' => $library->name,
                    'field' => 'library_fee',
                    'amount' => '50.00',
                    'is_system' => true,
                ],
                [
                    'id' => $custom->id,
                    'name' => $custom->name,
                    'field' => null,
                    'amount' => '100.00',
                    'is_system' => false,
                ],
            ])
            ->call('createStructureFromProgram')
            ->assertHasNoErrors();

        $structure = FeeStructure::query()
            ->where('program_id', $program->id)
            ->where('level', 100)
            ->where('session_id', $session->id)
            ->firstOrFail();

        $this->assertEquals(1150.00, (float) $structure->total_amount);
        $this->assertEquals(50.00, (float) $structure->library_fee);
        $this->assertEquals(0.00, (float) $structure->medical_fee);

        $this->assertDatabaseHas('fee_structure_items', [
            'fee_structure_id' => $structure->id,
            'fee_component_id' => $library->id,
            'amount' => 50.00,
        ]);
        $this->assertDatabaseHas('fee_structure_items', [
            'fee_structure_id' => $structure->id,
            'fee_component_id' => $custom->id,
            'amount' => 100.00,
        ]);
    }

    public function test_edit_structure_loads_only_configured_components(): void
    {
        $admin = $this->actingOwnerAdmin();
        ['program' => $program, 'session' => $session] = $this->createFeeGraph();

        $library = FeeComponent::query()->where('field', 'library_fee')->firstOrFail();
        $medical = FeeComponent::query()->where('field', 'medical_fee')->firstOrFail();

        $structure = FeeStructure::query()->create([
            'program_id' => $program->id,
            'level' => 100,
            'session_id' => $session->id,
            'tuition_fee' => 1000.00,
            'library_fee' => 50.00,
            'medical_fee' => 40.00,
            'total_amount' => 1090.00,
            'created_by' => $admin->user_id,
        ]);

        $structure->items()->createMany([
            ['fee_component_id' => $library->id, 'amount' => 50.00],
        ]);

        Livewire::actingAs($admin)
            ->test(FeesIndex::class)
            ->call('editStructure', $structure->id)
            ->assertSet('tuition_fee', '1000.00')
            ->assertSet('selectedComponents', fn (array $components): bool => count($components) === 1
                && $components[0]['id'] === $library->id
                && $components[0]['amount'] === '50.00');
    }

    /**
     * @return array{program: Program, session: AcademicSession}
     */
    private function createFeeGraph(): array
    {
        $faculty = Faculty::query()->create(['name' => 'Faculty of Fees']);
        $department = Department::query()->forceCreate([
            'name' => 'Department of Fees',
            'faculty_id' => $faculty->id,
        ]);

        $program = Program::query()->forceCreate([
            'name' => 'Fee Test Program',
            'department_id' => $department->id,
            'certificate' => 'BSc',
            'cost' => 1000.00,
            'program_length' => 4,
        ]);

        $session = AcademicSession::query()->create([
            'name' => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-06-30',
            'is_current' => true,
        ]);

        return compact('program', 'session');
    }
}
