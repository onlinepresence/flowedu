<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSetupOwnerAccess
{
    /**
     * System setup wizard (school, licence, faculties, …) is owner-only.
     * First-time bootstrap (admin_register session, no system yet) always
     * passes. Non-owner admins are sent back to their personal setup page
     * (or dashboard once their profile is complete) — hiding the menu alone
     * is not enough, direct URLs must bounce too.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $bootstrap = $request->hasSession()
            && (bool) $request->session()->get('admin_register', false);
        if ($bootstrap) {
            return $next($request);
        }

        if ($user !== null && $user->type === 'admin' && $user->isAdminOwner()) {
            return $next($request);
        }

        if ($user !== null && trim((string) ($user->username ?? '')) !== '') {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('admin.setup.personal');
    }
}
