<?php

namespace Tests\Feature\College;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class CollegeAjaxPingTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    public function test_authenticated_user_can_ping_json_endpoint(): void
    {
        $this->createTestSchool();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('college.ajax.ping'))
            ->assertOk()
            ->assertJson(['ok' => true]);
    }
}
