<?php

declare(strict_types=1);

namespace Tests\Feature\Teacher;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestSchool;
use Tests\TestCase;

class TeacherDashboardTest extends TestCase
{
    use CreatesTestSchool;
    use RefreshDatabase;

    public function test_onboarded_teacher_gets_200_on_dashboard(): void
    {
        $this->createTestSchool();

        $user = User::factory()->create([
            'type' => 'teacher',
            'username' => 'tutor1',
        ]);

        Teacher::query()->forceCreate([
            'user_id' => $user->id,
            'lastname' => 'Smith',
            'othernames' => 'Alex',
            'password_reset_required' => false,
            'is_onboarded' => true,
        ]);

        $this->actingAs($user)
            ->get(route('teacher.dashboard'))
            ->assertOk();
    }
}
