<?php

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmissionOpen
{
    /**
     * Legacy admission_is_open: block student signup when school exists and admissions are closed,
     * unless admin_register is set (first-time admin or school-not-ready flows).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $school = School::current();

        if ($school === null) {
            return $next($request);
        }

        if ($request->session()->get('admin_register')) {
            return $next($request);
        }

        if (! $school->is_admit) {
            return redirect()
                ->route('login')
                ->with('status', __('School is not receiving new students.'));
        }

        return $next($request);
    }
}
