<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Setup-wizard ordering gate: after the school step, later setup steps
 * require a licence choice (redeemed/imported UUID or provisional flag).
 * Ready schools pass untouched, so existing installs see zero change.
 */
class EnsureLicenceEnrolled
{
    public function handle(Request $request, Closure $next): Response
    {
        $school = School::current();

        if ($school === null || $school->ready) {
            return $next($request);
        }

        $row = $school->licence()->first();

        if ($row !== null && ((bool) $row->provisional || trim((string) $row->external_ref) !== '')) {
            return $next($request);
        }

        return redirect()
            ->route('admin.setup.licence')
            ->with('status', __('Choose how to licence this install before continuing setup.'));
    }
}
