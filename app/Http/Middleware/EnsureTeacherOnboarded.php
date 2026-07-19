<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeacherOnboarded
{
    /**
     * Legacy valid_teacher: onboarded teachers only for /teacher/* app.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || !$user->isTeacherActive()) {
            abort(403, 'Unauthorized. Please switch to Lecturer mode.');
        }

        $user->loadMissing('teacher');
        $teacher = $user->teacher;

        if ($teacher !== null && $teacher->is_onboarded) {
            return $next($request);
        }

        return redirect()
            ->route('teacher.setup')
            ->with('status', __('Complete your user profile to proceed.'));
    }
}
