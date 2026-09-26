<?php

/**
 * Email verification: Breeze uses signed URLs (`verification.verify`). Legacy used
 * `GET /verify-email/{token}`; see `legacy.verification.verify` in routes/legacy-public.php
 * which redirects users to resend verification from the account screen.
 */
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['guest', 'college.bootstrap'])->group(function () {
    Volt::route('register', 'pages.auth.register')
        ->middleware('college.admission')
        ->name('register');

    Volt::route('login', 'pages.auth.login')
        ->name('login');

    Volt::route('forgot-password', 'pages.auth.forgot-password')
        ->name('password.request');

    Volt::route('reset-password/{token}', 'pages.auth.reset-password')
        ->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Volt::route('verify-email', 'pages.auth.verify-email')
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Volt::route('confirm-password', 'pages.auth.confirm-password')
        ->name('password.confirm');

    Route::get('password/change', [PasswordChangeController::class, 'show'])
        ->name('password.change');

    Route::put('password/change', [PasswordChangeController::class, 'update'])
        ->name('password.update');

    Route::post('logout', function (\Illuminate\Http\Request $request) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    })->name('logout');
});
