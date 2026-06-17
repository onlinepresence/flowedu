<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\EvaluationForm;
use App\Services\Maintenance\EvaluationFormStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EvaluationFormStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_auto_control_type_is_processed(): void
    {
        $user = User::factory()->create();

        // 1. Auto form that should be closed
        $autoClose = EvaluationForm::create([
            'title' => 'Auto Close',
            'academic_year' => '2025/2026',
            'unique_code' => 'AUTO1111',
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDay(),
            'control_type' => 'auto',
            'is_active' => 1,
            'created_by' => $user->id,
            'last_edited_by' => $user->id,
        ]);

        // 2. Manual form that should NOT be closed, even though end_time has passed
        $manualKeep = EvaluationForm::create([
            'title' => 'Manual Keep',
            'academic_year' => '2025/2026',
            'unique_code' => 'MANU2222',
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDay(),
            'control_type' => 'manual',
            'is_active' => 1,
            'created_by' => $user->id,
            'last_edited_by' => $user->id,
        ]);

        // 3. Auto form that should be opened
        $autoOpen = EvaluationForm::create([
            'title' => 'Auto Open',
            'academic_year' => '2025/2026',
            'unique_code' => 'AUTO3333',
            'start_time' => now()->subDay(),
            'end_time' => now()->addDays(2),
            'control_type' => 'auto',
            'is_active' => 0,
            'created_by' => $user->id,
            'last_edited_by' => $user->id,
        ]);

        $service = new EvaluationFormStatusService();
        $this5 = $service->run();
        $this->assertTrue($this5);

        // Assert $autoClose has been set to closed (-1)
        $autoClose->refresh();
        $this->assertEquals(-1, $autoClose->is_active);

        // Assert $manualKeep remains active (1)
        $manualKeep->refresh();
        $this->assertEquals(1, $manualKeep->is_active);

        // Assert $autoOpen has been opened (1)
        $autoOpen->refresh();
        $this->assertEquals(1, $autoOpen->is_active);
    }
}
