<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Teacher;
use App\Models\TeacherRole;
use App\Models\TeacherPortalRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TeacherPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_fallback_permissions_when_no_assigned_roles(): void
    {
        $user = User::factory()->create(['type' => 'teacher']);
        $teacher = Teacher::create([
            'user_id' => $user->id,
            'lastname' => 'Smith',
            'othernames' => 'John',
            'staff_id' => 'T001',
            'is_onboarded' => true,
        ]);

        $this->assertEquals(
            ['courses', 'students', 'assessments', 'communication'],
            $user->teacherPermissions()
        );
        $this->assertTrue($user->hasTeacherPermission('courses'));
        $this->assertTrue($user->hasTeacherPermission('students'));
        $this->assertTrue($user->hasTeacherPermission('assessments'));
        $this->assertTrue($user->hasTeacherPermission('communication'));
        $this->assertFalse($user->hasTeacherPermission('admin'));
    }

    public function test_permissions_with_assigned_active_role(): void
    {
        // Custom unique role
        TeacherPortalRole::create([
            'name' => 'custom_tutor',
            'display_name' => 'Custom Tutor',
            'permissions' => ['courses', 'students'],
        ]);

        $user = User::factory()->create(['type' => 'teacher']);
        $teacher = Teacher::create([
            'user_id' => $user->id,
            'lastname' => 'Smith',
            'othernames' => 'John',
            'staff_id' => 'T001',
            'is_onboarded' => true,
        ]);

        TeacherRole::create([
            'teacher_id' => $teacher->id,
            'role' => 'custom_tutor',
            'status' => 'active',
            'assigned_date' => now(),
        ]);

        $this->assertEquals(
            ['courses', 'students'],
            $user->teacherPermissions()
        );
        $this->assertTrue($user->hasTeacherPermission('courses'));
        $this->assertTrue($user->hasTeacherPermission('students'));
        $this->assertFalse($user->hasTeacherPermission('assessments'));
        $this->assertFalse($user->hasTeacherPermission('communication'));
    }

    public function test_inactive_roles_are_ignored(): void
    {
        TeacherPortalRole::create([
            'name' => 'custom_tutor',
            'display_name' => 'Custom Tutor',
            'permissions' => ['courses', 'students'],
        ]);

        $user = User::factory()->create(['type' => 'teacher']);
        $teacher = Teacher::create([
            'user_id' => $user->id,
            'lastname' => 'Smith',
            'othernames' => 'John',
            'staff_id' => 'T001',
            'is_onboarded' => true,
        ]);

        TeacherRole::create([
            'teacher_id' => $teacher->id,
            'role' => 'custom_tutor',
            'status' => 'inactive',
            'assigned_date' => now(),
        ]);

        // Since it's inactive, they fallback to full permissions
        $this->assertEquals(
            ['courses', 'students', 'assessments', 'communication'],
            $user->teacherPermissions()
        );
    }
}
