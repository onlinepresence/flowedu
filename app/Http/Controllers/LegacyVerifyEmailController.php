<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class LegacyVerifyEmailController extends Controller
{
    /**
     * Legacy used GET /verify-email/{token}. Breeze uses signed /verify-email/{id}/{hash}.
     * Old links should use "resend verification" from the account screen.
     */
    public function __invoke(string $token): RedirectResponse
    {
        return redirect()
            ->route('verification.notice')
            ->with('status', __('This verification link format is no longer used. Please request a new verification email from your account.'));
    }
}
