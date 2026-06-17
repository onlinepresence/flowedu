<?php

use App\Http\Controllers\LegacyVerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::view('shutdown', 'legacy.shutdown')
    ->middleware('college.bootstrap')
    ->name('shutdown');

Route::middleware(['auth', 'college.bootstrap'])->group(function () {
    Route::get('send-verification', function () {
        return redirect()
            ->route('verification.notice')
            ->with('status', __('Use the verification options on this page to resend your email.'));
    })->name('legacy.send-verification');
});

Route::get('verify-email/{token}', LegacyVerifyEmailController::class)
    ->middleware(['college.bootstrap'])
    ->name('legacy.verification.verify');


