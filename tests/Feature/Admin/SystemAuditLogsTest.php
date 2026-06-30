<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Audit\SystemAuditLogsPage;
use App\Helpers\AuditHelper;
use App\Models\SystemAudit;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\AdminSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class SystemAuditLogsTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['licence.enforce' => false]);
        $this->seed(AdminSystemSeeder::class);
        $this->createTestSchool();
        UserRole::ensureSystemRoles();
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
            $role->update(['permissions' => array_unique(array_merge($role->permissions ?? [], $permissions))]);
        }

        $user = User::factory()->create([
            'type' => 'admin',
            'username' => 'staff_username_' . uniqid(),
        ]);
        $admin = new \App\Models\Admin;
        $admin->user_id = $user->id;
        $admin->type = $role->id;
        $admin->save();

        return $user;
    }

    public function test_access_gating_for_audit_logs(): void
    {
        $staff = $this->createStaffMember('teacher', ['nav_staff_leaves']);
        $admin = $this->createStaffMember('system_admin', ['nav_staff_leaves']);
        $owner = $this->createStaffMember('owner', ['nav_staff_leaves']);

        // Non-admin/non-owner is blocked (403)
        $this->actingAs($staff)
            ->get(route('admin.audit-logs'))
            ->assertStatus(403);

        // System Admin can access
        $this->actingAs($admin)
            ->get(route('admin.audit-logs'))
            ->assertStatus(200);

        // Owner can access
        $this->actingAs($owner)
            ->get(route('admin.audit-logs'))
            ->assertStatus(200);
    }

    public function test_audit_helper_creates_log_entries(): void
    {
        $staff = $this->createStaffMember('teacher');

        $this->actingAs($staff);

        $log = AuditHelper::log(
            action: 'custom_action',
            description: 'This is a test audit entry',
            metadata: ['foo' => 'bar'],
            isFlagged: true
        );

        $this->assertNotNull($log);
        $this->assertEquals($staff->id, $log->user_id);
        $this->assertEquals('custom_action', $log->action);
        $this->assertEquals('This is a test audit entry', $log->description);
        $this->assertEquals(['foo' => 'bar'], $log->metadata);
        $this->assertTrue($log->is_flagged);
        $this->assertEquals('127.0.0.1', $log->ip_address);
    }

    public function test_audit_dashboard_filtering_and_flag_toggling(): void
    {
        $admin = $this->createStaffMember('system_admin');
        $staff = $this->createStaffMember('teacher');

        // Create log entry 1
        $log1 = SystemAudit::create([
            'user_id' => $staff->id,
            'action' => 'leave_created',
            'description' => 'Staff submitted a leave request',
            'is_flagged' => false,
            'ip_address' => '127.0.0.1',
            'created_at' => now()->subDays(2),
        ]);

        // Create log entry 2
        $log2 = SystemAudit::create([
            'user_id' => $admin->id,
            'action' => 'security_alert',
            'description' => 'Failed login attempt',
            'is_flagged' => true,
            'ip_address' => '10.0.0.1',
            'created_at' => now(),
        ]);

        // Test list display and initial state
        Livewire::actingAs($admin)
            ->test(SystemAuditLogsPage::class)
            ->assertSee('Staff submitted a leave request')
            ->assertSee('Failed login attempt')
            // Filter by Action
            ->set('selectedAction', 'security_alert')
            ->assertSee('Failed login attempt')
            ->assertDontSee('Staff submitted a leave request')
            // Reset action and filter by User
            ->set('selectedAction', '')
            ->set('searchUser', $staff->name)
            ->assertSee('Staff submitted a leave request')
            ->assertDontSee('Failed login attempt')
            // Reset user and filter by Date
            ->set('searchUser', '')
            ->set('startDate', now()->subDay()->format('Y-m-d'))
            ->assertSee('Failed login attempt')
            ->assertDontSee('Staff submitted a leave request');

        // Test flag toggling
        Livewire::actingAs($admin)
            ->test(SystemAuditLogsPage::class)
            ->call('toggleFlag', $log1->id);

        $log1->refresh();
        $this->assertTrue($log1->is_flagged);

        // Verify Tab Counter and Flagged View filtering
        Livewire::actingAs($admin)
            ->test(SystemAuditLogsPage::class)
            ->set('activeTab', 'flagged')
            ->assertSee('Failed login attempt')
            ->assertSee('Staff submitted a leave request');
    }
}
