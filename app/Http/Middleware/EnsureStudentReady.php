<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentReady
{
    /**
     * Legacy student_ready: admission approved and dashboard activated.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->type !== 'student') {
            return $next($request);
        }

        $user->loadMissing('student');
        $student = $user->student;

        if ($student === null) {
            return redirect()
                ->route('student.setup.personal')
                ->with('status', __('Admission form submission not completed.'));
        }

        if (! $student->approved) {
            return redirect()
                ->route('student.setup.personal')
                ->with('status', __('Your admission is yet to be approved.'));
        }

        if ($student->is_new && $student->approved) {
            return redirect()
                ->route('student.setup.status')
                ->with('status', __('Admission has been approved. Activate your dashboard to proceed.'));
        }

        return $next($request);
    }
}
