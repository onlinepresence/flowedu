<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeacherSetupGate
{
    /**
     * Legacy valid_teacher_check: onboarded teachers skip /teacher/setup.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->type !== 'teacher') {
            return $next($request);
        }

        $user->loadMissing('teacher');
        $teacher = $user->teacher;

        if ($teacher !== null && $teacher->is_onboarded) {
            return redirect()->route('teacher.dashboard');
        }

        return $next($request);
    }
}
