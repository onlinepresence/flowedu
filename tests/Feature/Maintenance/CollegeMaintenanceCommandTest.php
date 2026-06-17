<?php

namespace Tests\Feature\Maintenance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollegeMaintenanceCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_college_maintenance_all_exits_successfully(): void
    {
        $this->artisan('college:maintenance', ['task' => 'all'])
            ->assertExitCode(0);
    }

    public function test_college_maintenance_rejects_unknown_task(): void
    {
        $this->artisan('college:maintenance', ['task' => 'nope'])
            ->assertExitCode(1);
    }
}
