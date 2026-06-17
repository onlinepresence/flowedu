<?php

use App\Http\Controllers\Admin\AdminImpersonationStopController;
use App\Http\Controllers\Auth\PostLoginRedirectController;
use App\Http\Controllers\FilepondController;
use App\Http\Controllers\LandingController;
use App\Services\SchoolLicenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->middleware(['college.bootstrap'])->name('home');
Route::post('/quote-request', [LandingController::class, 'quoteRequest'])->name('quote-request');
Route::get('/quote/download-pdf', [LandingController::class, 'downloadPdf'])->name('quote.download-pdf');


Route::get('dashboard', function () {
    return redirect()->route('post.login.redirect');
})->middleware(['auth'])->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth'])->group(function () {
    Route::get('auth/redirect', PostLoginRedirectController::class)
        ->name('post.login.redirect');

    Route::post('impersonation/stop', AdminImpersonationStopController::class)
        ->name('impersonation.stop');

    /** JSON ping for Livewire/JS migration (plan §7 Phase F / §9.3). */
    Route::post('__college/ajax/ping', fn () => response()->json(['ok' => true]))
        ->name('college.ajax.ping');

    Route::post('__college/filepond/process', [FilepondController::class, 'process'])
        ->name('college.filepond.process');
    Route::delete('__college/filepond/revert', [FilepondController::class, 'revert'])
        ->name('college.filepond.revert');
});

Route::get('/licence-required', function (Request $request, SchoolLicenceService $licenceService) {
    $feature = (string) $request->query('feature', '');
    $message = session('system_message', $licenceService->upgradeMessage($feature));

    return view('licence.required', [
        'feature' => $feature,
        'message' => $message,
        'showLicenceSettingsLink' => $request->user()?->canViewLicenceSubscriptionLink() ?? false,
    ]);
})->middleware(['auth', 'college.bootstrap'])->name('licence.required');

require __DIR__.'/legacy-public.php';
require __DIR__.'/admin.php';
require __DIR__.'/student.php';
require __DIR__.'/teacher.php';

if (app()->environment('testing', 'local')) {
    Route::get('/__testing/licence-finance', fn () => 'ok')
        ->middleware(['auth', 'college.licence:finance'])
        ->name('testing.licence.finance');

    Route::get('/__testing/admin-owner', fn () => 'owner-ok')
        ->middleware(['auth', 'college.admin-role:owner'])
        ->name('testing.admin.owner');

    Route::prefix('__testing/errors')->group(function () {
        Route::get('{code}', function ($code) {
            $views = ['401', '403', '404', '419', '429', '500', '503'];
            if (!in_array($code, $views)) {
                abort(404);
            }
            $message = match($code) {
                '401' => 'Your session has expired or you are not authenticated to view this resource.',
                '403' => 'You do not have administrative privileges to access the user configuration database.',
                '404' => 'The path "/admin/invalid-page" does not match any registered routes in this application.',
                '419' => 'CSRF token mismatch. The form security token has expired after a period of inactivity.',
                '429' => 'Rate limit exceeded. You have made more than 60 requests per minute from IP 127.0.0.1.',
                '500' => 'SQLSTATE[HY000]: General error: 1 no such table: non_existent_table (Connection: sqlite).',
                '503' => 'Application is running database migrations. Maintenance mode is active.',
                default => 'An unexpected error occurred.',
            };
            return response()->view("errors.{$code}", [
                'exception' => new \Symfony\Component\HttpKernel\Exception\HttpException($code, $message)
            ], $code);
        });
    });
}

require __DIR__.'/auth.php';

// Software Demo Mode routes
require __DIR__.'/demo-routes.php';
