<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Staff\StaffLeavesPage;
use App\Models\LeaveRequest;
use App\Models\StaffLeaveType;
use App\Models\SystemAudit;
use App\Models\AcademicSession;
use App\Models\Department;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\AdminSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class StaffLeaveModuleTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    private StaffLeaveType $leaveType;
    private AcademicSession $session;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();
        UserRole::ensureSystemRoles();

        $this->leaveType = StaffLeaveType::create([
            'name' => 'Annual Leave',
            'max_leave_days' => 20,
        ]);

        $this->session = AcademicSession::create([
            'name' => '2026/2027 Academic Year',
            'start_date' => now()->subDays(10)->format('Y-m-d'),
            'end_date' => now()->addDays(300)->format('Y-m-d'),
            'is_current' => true,
        ]);
    }

    private function createStaffMember(string $roleName, array $permissions = []): User
    {
        $role = UserRole::query()->where('name', $roleName)->first();
        if (!$role) {
            $role = UserRole::query()->create([
                'name' => $roleName,
                'display_name' => ucfirst($roleName),
                'role_name' => 'other',
                'permissions' => $permissions,
            ]);
        } else {
            $role->update(['permissions' => array_merge($role->permissions ?? [], $permissions)]);
        }

        $user = User::factory()->create([
            'type' => 'admin',
        ]);

        $admin = new \App\Models\Admin;
        $admin->user_id = $user->id;
        $admin->type = $role->id;
        $admin->save();

        return $user;
    }

    public function test_staff_can_submit_leave_request_and_calculates_days(): void
    {
        $staff = $this->createStaffMember('teacher', ['nav_staff_leaves']);

        // Set strategy to standard
        \App\Models\Setting::updateOrCreate(
            ['setting_key' => 'leave_settings.approval_workflow'],
            ['setting_value' => 'standard', 'category' => 'leave_settings', 'data_type' => 'string']
        );

        Livewire::actingAs($staff)
            ->test(StaffLeavesPage::class)
            ->call('openCreateModal')
            ->set('staff_leave_type_id', $this->leaveType->id)
            ->set('start_date', now()->addDays(1)->format('Y-m-d'))
            ->set('end_date', now()->addDays(5)->format('Y-m-d')) // 5 days
            ->set('reason', 'Need a vacation')
            ->call('submitLeaveRequest');

        $request = LeaveRequest::query()->first();
        $this->assertNotNull($request);
        $this->assertEquals($staff->id, $request->user_id);
        $this->assertEquals(5, $request->requested_days);
        $this->assertEquals('pending', $request->status);
        $this->assertEquals('pending_registrar', $request->current_stage);

        // Verify SystemAudit log exists
        $audit = SystemAudit::query()->where('action', 'leave_created')->first();
        $this->assertNotNull($audit);
        $this->assertEquals($staff->id, $audit->user_id);
    }

    public function test_leave_approval_workflow_departmental_progression(): void
    {
        // Setup departmental strategy
        \App\Models\Setting::updateOrCreate(
            ['setting_key' => 'leave_settings.approval_workflow'],
            ['setting_value' => 'departmental', 'category' => 'leave_settings', 'data_type' => 'string']
        );

        $faculty = \App\Models\Faculty::create(['name' => 'Sciences']);
        $dept = Department::create(['name' => 'CS', 'faculty_id' => $faculty->id]);

        $staff = $this->createStaffMember('teacher', ['nav_staff_leaves']);
        $staff->admin->update(['department_id' => $dept->id]);

        $hod = $this->createStaffMember('hod', ['nav_staff_leaves']);
        $hod->admin->update(['department_id' => $dept->id]);
        $dept->update(['hod' => $hod->id]);

        $principal = $this->createStaffMember('principal', ['nav_staff_leaves']);

        // Create leave request for staff
        $request = LeaveRequest::create([
            'user_id' => $staff->id,
            'staff_leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(5),
            'requested_days' => 5,
            'status' => 'pending',
            'current_stage' => 'pending_hod',
            'academic_session_id' => $this->session->id,
        ]);

        // Non-HOD cannot approve
        $otherStaff = $this->createStaffMember('teacher', ['nav_staff_leaves']);
        Livewire::actingAs($otherStaff)
            ->test(StaffLeavesPage::class)
            ->call('openReviewModal', $request->id)
            ->call('approveRequest')
            ->assertHasErrors(['rejection_reason']);

        // HOD approves -> advances to pending_principal
        Livewire::actingAs($hod)
            ->test(StaffLeavesPage::class)
            ->call('openReviewModal', $request->id)
            ->call('approveRequest')
            ->assertHasNoErrors();

        $request->refresh();
        $this->assertEquals('pending', $request->status);
        $this->assertEquals('pending_principal', $request->current_stage);

        // Principal approves -> final approval
        Livewire::actingAs($principal)
            ->test(StaffLeavesPage::class)
            ->call('openReviewModal', $request->id)
            ->call('approveRequest')
            ->assertHasNoErrors();

        $request->refresh();
        $this->assertEquals('approved', $request->status);
        $this->assertEquals('approved', $request->current_stage);
        $this->assertEquals($principal->id, $request->reviewer_id);

        // Verify final audit
        $audit = SystemAudit::query()->where('action', 'leave_approved')->first();
        $this->assertNotNull($audit);
        $this->assertEquals($principal->id, $audit->user_id);
    }

    public function test_leave_approval_workflow_direct_principal_progression(): void
    {
        // Setup direct_principal strategy
        \App\Models\Setting::updateOrCreate(
            ['setting_key' => 'leave_settings.approval_workflow'],
            ['setting_value' => 'direct_principal', 'category' => 'leave_settings', 'data_type' => 'string']
        );

        $staff = $this->createStaffMember('teacher', ['nav_staff_leaves']);
        $principal = $this->createStaffMember('principal', ['nav_staff_leaves']);

        Livewire::actingAs($staff)
            ->test(StaffLeavesPage::class)
            ->call('openCreateModal')
            ->set('staff_leave_type_id', $this->leaveType->id)
            ->set('start_date', now()->addDays(1)->format('Y-m-d'))
            ->set('end_date', now()->addDays(5)->format('Y-m-d'))
            ->set('reason', 'Personal reasons')
            ->call('submitLeaveRequest');

        $request = LeaveRequest::query()->first();
        $this->assertNotNull($request);
        $this->assertEquals('pending_principal', $request->current_stage);

        // Principal rejects request
        Livewire::actingAs($principal)
            ->test(StaffLeavesPage::class)
            ->call('openReviewModal', $request->id)
            ->set('rejection_reason', 'Too busy right now')
            ->call('rejectRequest')
            ->assertHasNoErrors();

        $request->refresh();
        $this->assertEquals('rejected', $request->status);
        $this->assertEquals('rejected', $request->current_stage);
        $this->assertEquals('Too busy right now', $request->rejection_reason);

        // Verify reject audit
        $audit = SystemAudit::query()->where('action', 'leave_rejected')->first();
        $this->assertNotNull($audit);
        $this->assertEquals($principal->id, $audit->user_id);
    }

    public function test_staff_leave_entitlements_calculation(): void
    {
        $staff = $this->createStaffMember('teacher', ['nav_staff_leaves']);
        
        // Assign the user to the leave type
        $staff->update(['staff_leave_type_id' => $this->leaveType->id]); // 20 days max

        // Create 1 approved request (5 days)
        LeaveRequest::create([
            'user_id' => $staff->id,
            'staff_leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(5),
            'requested_days' => 5,
            'status' => 'approved',
            'current_stage' => 'approved',
            'academic_session_id' => $this->session->id,
        ]);

        // Create 1 pending request (3 days)
        LeaveRequest::create([
            'user_id' => $staff->id,
            'staff_leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(12),
            'requested_days' => 3,
            'status' => 'pending',
            'current_stage' => 'pending_registrar',
            'academic_session_id' => $this->session->id,
        ]);

        // Access the component and assert entitlements
        Livewire::actingAs($staff)
            ->test(StaffLeavesPage::class)
            ->assertSet('activeTab', 'my_leaves')
            ->assertSee('Total Entitlement')
            ->assertSee('20 Days')
            ->assertSee('5 Days') // Approved
            ->assertSee('3 Days') // Pending
            ->assertSee('15 Days'); // Remaining (20 - 5)
    }

    public function test_submission_window_constraints_and_emergency_bypass(): void
    {
        $staff = $this->createStaffMember('teacher', ['nav_staff_leaves']);

        // Set up submission window: today to +2 days
        \App\Models\Setting::updateOrCreate(
            ['setting_key' => 'leave_settings.submission_start_date'],
            ['setting_value' => now()->format('Y-m-d'), 'category' => 'leave_settings', 'data_type' => 'string']
        );
        \App\Models\Setting::updateOrCreate(
            ['setting_key' => 'leave_settings.submission_end_date'],
            ['setting_value' => now()->addDays(2)->format('Y-m-d'), 'category' => 'leave_settings', 'data_type' => 'string']
        );
        // Disable emergency bypass first
        \App\Models\Setting::updateOrCreate(
            ['setting_key' => 'leave_settings.emergency_leave_enabled'],
            ['setting_value' => '0', 'category' => 'leave_settings', 'data_type' => 'boolean']
        );

        // Submit outside window: starts +5 days (fails)
        Livewire::actingAs($staff)
            ->test(StaffLeavesPage::class)
            ->call('openCreateModal')
            ->set('staff_leave_type_id', $this->leaveType->id)
            ->set('start_date', now()->addDays(5)->format('Y-m-d'))
            ->set('end_date', now()->addDays(7)->format('Y-m-d'))
            ->set('reason', 'Vacation outside window')
            ->call('submitLeaveRequest')
            ->assertHasErrors(['end_date']);

        // Now toggle emergency leave bypass on
        \App\Models\Setting::updateOrCreate(
            ['setting_key' => 'leave_settings.emergency_leave_enabled'],
            ['setting_value' => '1', 'category' => 'leave_settings', 'data_type' => 'boolean']
        );

        // Submit as emergency: starts +5 days (succeeds)
        Livewire::actingAs($staff)
            ->test(StaffLeavesPage::class)
            ->call('openCreateModal')
            ->set('staff_leave_type_id', $this->leaveType->id)
            ->set('start_date', now()->addDays(5)->format('Y-m-d'))
            ->set('end_date', now()->addDays(7)->format('Y-m-d'))
            ->set('reason', 'Emergency bypass validation')
            ->set('is_emergency', true)
            ->call('submitLeaveRequest')
            ->assertHasNoErrors();
    }

    public function test_leave_configurations_crud_and_staff_assignment(): void
    {
        $admin = $this->createStaffMember('system_admin', ['nav_staff_leaves']);

        // Test configuration updates
        Livewire::actingAs($admin)
            ->test(StaffLeavesPage::class)
            ->set('submission_start_date', '2026-07-01')
            ->set('submission_end_date', '2026-07-15')
            ->set('emergency_leave_enabled', true)
            ->call('saveConfigurations');

        $this->assertEquals('2026-07-01', \App\Models\Setting::query()->where('setting_key', 'leave_settings.submission_start_date')->value('setting_value'));
        $this->assertEquals('2026-07-15', \App\Models\Setting::query()->where('setting_key', 'leave_settings.submission_end_date')->value('setting_value'));
        $this->assertEquals('1', \App\Models\Setting::query()->where('setting_key', 'leave_settings.emergency_leave_enabled')->value('setting_value'));

        // Test Leave Type CRUD
        Livewire::actingAs($admin)
            ->test(StaffLeavesPage::class)
            ->call('openTypeModal')
            ->set('type_name', 'Maternity Leave')
            ->set('type_max_days', 90)
            ->call('saveLeaveType');

        $type = StaffLeaveType::query()->where('name', 'Maternity Leave')->first();
        $this->assertNotNull($type);
        $this->assertEquals(90, $type->max_leave_days);

        // Test Staff Assignment
        $staff = $this->createStaffMember('teacher', ['nav_staff_leaves']);
        Livewire::actingAs($admin)
            ->test(StaffLeavesPage::class)
            ->call('assignLeaveType', $staff->id, $type->id);

        $staff->refresh();
        $this->assertEquals($type->id, $staff->staff_leave_type_id);
    }

    public function test_staff_assignments_filters_and_departmental_scoping_for_hod_and_hr(): void
    {
        $faculty = \App\Models\Faculty::create(['name' => 'Humanities']);
        $deptCS = Department::create(['name' => 'CS', 'faculty_id' => $faculty->id]);
        $deptMath = Department::create(['name' => 'Math', 'faculty_id' => $faculty->id]);

        // Create HOD for CS
        $hod = $this->createStaffMember('hod', ['nav_staff_leaves']);
        $hod->admin->update(['department_id' => $deptCS->id]);
        $deptCS->update(['hod' => $hod->id]);

        // Create HR/Admin
        $hr = $this->createStaffMember('system_admin', ['nav_staff_leaves']);

        // Create Staff Members:
        // 1. Teacher in CS
        $teacherCS = User::factory()->create(['type' => 'teacher', 'name' => 'CS Teacher']);
        $teacherProfileCS = new \App\Models\Teacher;
        $teacherProfileCS->user_id = $teacherCS->id;
        $teacherProfileCS->department_id = $deptCS->id;
        $teacherProfileCS->phone_number = '123';
        $teacherProfileCS->save();

        // 2. Teacher in Math
        $teacherMath = User::factory()->create(['type' => 'teacher', 'name' => 'Math Teacher']);
        $teacherProfileMath = new \App\Models\Teacher;
        $teacherProfileMath->user_id = $teacherMath->id;
        $teacherProfileMath->department_id = $deptMath->id;
        $teacherProfileMath->phone_number = '456';
        $teacherProfileMath->save();

        // 3. Non-teaching staff in CS
        $staffCS = User::factory()->create(['type' => 'staff', 'name' => 'CS Staff']);
        $staffProfileCS = new \App\Models\NonTeachingStaff;
        $staffProfileCS->user_id = $staffCS->id;
        $staffProfileCS->department_id = $deptCS->id;
        $staffProfileCS->position = 'Janitor';
        $staffProfileCS->phone_number = '789';
        $staffProfileCS->save();

        // 4. Non-teaching staff with no department
        $staffNoDept = User::factory()->create(['type' => 'staff', 'name' => 'Admin Staff']);
        $staffProfileNoDept = new \App\Models\NonTeachingStaff;
        $staffProfileNoDept->user_id = $staffNoDept->id;
        $staffProfileNoDept->department_id = null;
        $staffProfileNoDept->position = 'Secretary';
        $staffProfileNoDept->phone_number = '000';
        $staffProfileNoDept->save();

        // --- Test HOD Scoping ---
        Livewire::actingAs($hod)
            ->test(StaffLeavesPage::class)
            ->set('activeTab', 'staff_assignments')
            ->assertViewHas('staffMembers', function ($staffMembers) use ($teacherCS, $staffCS, $teacherMath, $staffNoDept) {
                $ids = $staffMembers->pluck('id')->all();
                return in_array($teacherCS->id, $ids) &&
                       in_array($staffCS->id, $ids) &&
                       !in_array($teacherMath->id, $ids) &&
                       !in_array($staffNoDept->id, $ids);
            })
            // Filter HOD to teaching staff
            ->set('filterStaffType', 'teaching')
            ->assertViewHas('staffMembers', function ($staffMembers) use ($teacherCS, $staffCS) {
                $ids = $staffMembers->pluck('id')->all();
                return in_array($teacherCS->id, $ids) && !in_array($staffCS->id, $ids);
            })
            // Filter HOD to non-teaching staff
            ->set('filterStaffType', 'non_teaching')
            ->assertViewHas('staffMembers', function ($staffMembers) use ($teacherCS, $staffCS) {
                $ids = $staffMembers->pluck('id')->all();
                return !in_array($teacherCS->id, $ids) && in_array($staffCS->id, $ids);
            });

        // --- Test HR Scoping ---
        Livewire::actingAs($hr)
            ->test(StaffLeavesPage::class)
            ->set('activeTab', 'staff_assignments')
            // All departments by default should see all 4
            ->assertViewHas('staffMembers', function ($staffMembers) use ($teacherCS, $staffCS, $teacherMath, $staffNoDept) {
                $ids = $staffMembers->pluck('id')->all();
                return in_array($teacherCS->id, $ids) &&
                       in_array($staffCS->id, $ids) &&
                       in_array($teacherMath->id, $ids) &&
                       in_array($staffNoDept->id, $ids);
            })
            // Filter to Math department
            ->set('filterStaffDepartment', $deptMath->id)
            ->assertViewHas('staffMembers', function ($staffMembers) use ($teacherMath, $teacherCS) {
                $ids = $staffMembers->pluck('id')->all();
                return in_array($teacherMath->id, $ids) && !in_array($teacherCS->id, $ids);
            })
            // Filter to No Department
            ->set('filterStaffDepartment', 'none')
            ->assertViewHas('staffMembers', function ($staffMembers) use ($staffNoDept, $teacherCS) {
                $ids = $staffMembers->pluck('id')->all();
                return in_array($staffNoDept->id, $ids) && !in_array($teacherCS->id, $ids);
            });
    }
}
