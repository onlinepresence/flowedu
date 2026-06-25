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
}
