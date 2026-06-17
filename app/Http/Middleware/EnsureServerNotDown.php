<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Legacy includes/session.php: when SERVER_DOWN=true, redirect all traffic to /shutdown.
 */
final class EnsureServerNotDown
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('college.server_down')) {
            return $next($request);
        }

        if ($request->routeIs('shutdown') || $request->is('shutdown', 'up')) {
            return $next($request);
        }

        return redirect()->route('shutdown');
    }
}
