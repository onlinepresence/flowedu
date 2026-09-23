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

/*
| Licence redemption retry: silent daily attempt for installs parked with a
| pending enrollment code (offline setup exit). No pending code (or no
| school) = quiet no-op without touching the network.
*/
Schedule::call(fn () => app(\App\Services\ControlPlane\LicenceEnrollmentService::class)->retryPending())
    ->dailyAt('04:00')
    ->name('licence-retry-pending');

/*
| Daily heartbeat sync: linked installs only (UUID+token). POSTs the heartbeat
| via the existing ping path and merges central truth with GRANT∧PREFERENCE
| (central false wins; local core-off survives; modules frozen). Runs after
| the retry job; silent no-op when unlinked, provisional, or file-managed,
| and silent info-logged deferral when offline.
*/
Schedule::call(fn () => app(\App\Services\ControlPlane\LicenceEnrollmentService::class)->syncHeartbeat())
    ->dailyAt('04:30')
    ->name('licence-heartbeat-sync');

/*
| Single-connection demo refresh: registered monthly ONLY when APP_DEMO is true
| at schedule-registration time (never unconditional). The command itself
| re-checks the same env flag before touching the database.
*/
if ((bool) config('college.demo_mode', false)) {
    Schedule::command('demo:refresh')
        ->monthlyOn(1, '03:00')
        ->name('demo-refresh-monthly');
}

Artisan::command('app:process-evaluations', function (EvaluationFormStatusService $service) {
    $this->info('Starting evaluations status updates processing...');
    if ($service->run()) {
        $this->info('Evaluations status updates processed successfully.');
    } else {
        $this->error('Failed to process evaluations status updates.');
    }
})->purpose('Process evaluation status updates based on start and end times');
