<?php

namespace Tests\Feature\Demo;

use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_seeder_runs_clean(): void
    {
        // Engine compatibility only (no realism changes): must run without
        // exceptions on strict-mode-capable schema. Content assertions are
        // structural, not exact counts (seeder uses rand()).
        $this->seed(DemoDataSeeder::class);

        $this->assertDatabaseHas('schools', ['name' => 'Apex Polytechnic (Demo Sandbox)']);
        $this->assertDatabaseHas('users', ['email' => 'admin@demo.com']);
        $this->assertDatabaseHas('users', ['email' => 'teacher@demo.com']);
        $this->assertDatabaseHas('users', ['email' => 'student@demo.com']);

        $licence = \App\Models\SchoolLicence::query()->first();
        $this->assertNotNull($licence);
        $this->assertTrue((bool) $licence->module_finance);
        $this->assertTrue((bool) $licence->module_system_admin);
    }
}
