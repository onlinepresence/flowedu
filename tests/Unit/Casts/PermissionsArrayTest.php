<?php

namespace Tests\Unit\Casts;

use App\Models\UserRole;
use Tests\TestCase;

class PermissionsArrayTest extends TestCase
{
    public function test_reads_legacy_php_serialized_base64(): void
    {
        $encoded = base64_encode(serialize(['student_management', 'approve_registrations']));

        $role = new UserRole;
        $role->setRawAttributes(['permissions' => $encoded]);

        $this->assertSame(['student_management', 'approve_registrations'], $role->permissions);
    }

    public function test_reads_json_array(): void
    {
        $json = json_encode(['view_profile']);

        $role = new UserRole;
        $role->setRawAttributes(['permissions' => $json]);

        $this->assertSame(['view_profile'], $role->permissions);
    }
}
