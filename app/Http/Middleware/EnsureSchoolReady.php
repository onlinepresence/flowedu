<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\School;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolReady
{
    /**
     * Legacy check_school_status: school must be "ready" except for owner/admin completing setup.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $school = School::current();

        if ($school === null || $school->ready) {
            return $next($request);
        }

        $user = $request->user();

        if ($this->canAccessSchoolSetup($user)) {
            $request->session()->put('admin_register', true);

            return redirect()
                ->route('admin.setup.school')
                ->with('status', __('School is not ready for use.'));
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', __('School is not ready for use.'));
    }

    private function canAccessSchoolSetup(?User $user): bool
    {
        if ($user === null || $user->type !== 'admin') {
            return false;
        }

        $slug = $user->adminRoleSlug();

        return in_array($slug, ['owner', 'admin'], true) || $slug === null;
    }
}
