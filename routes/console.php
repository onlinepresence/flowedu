<?php

use App\Services\Maintenance\AutoPromotionService;
use App\Services\Maintenance\EvaluationFormStatusService;
use App\Services\Maintenance\SemesterActiveStatusService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Legacy includes/auto-jobs.php + jobs/worker.php: evaluation + semester updates ran when the
| worker was invoked; auto-promotion was intended monthly on the 15th. We schedule explicitly.
*/
Schedule::call(fn () => app(EvaluationFormStatusService::class)->run())
    ->hourly()
    ->name('college-maintenance-evaluation');

Schedule::call(fn () => app(SemesterActiveStatusService::class)->run())
    ->hourly()
    ->name('college-maintenance-semesters');

Schedule::call(fn () => app(AutoPromotionService::class)->run())
    ->monthlyOn(15, '3:00')
    ->name('college-maintenance-auto-promotion');

Artisan::command('app:process-evaluations', function (EvaluationFormStatusService $service) {
    $this->info('Starting evaluations status updates processing...');
    if ($service->run()) {
        $this->info('Evaluations status updates processed successfully.');
    } else {
        $this->error('Failed to process evaluations status updates.');
    }
})->purpose('Process evaluation status updates based on start and end times');
