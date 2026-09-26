<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordChangeController extends Controller
{
    /**
     * Forced-change screen: the one page a flagged user may use.
     */
    public function show(): View
    {
        return view('auth.password-change');
    }

    /**
     * Self-initiated change only (own record, current password proven): sets
     * the new secret and clears the flag. Admin edits elsewhere never clear it.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ])->save();

        return redirect(route('dashboard'))->with('status', __('Password changed.'));
    }
}
