<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SyncDemoModeSession
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->cookie('demo_mode') === '1') {
            if ($request->hasSession() && ! $request->session()->has('demo_mode')) {
                $request->session()->put('demo_mode', true);
            }
        } else {
            if ($request->hasSession() && $request->session()->has('demo_mode')) {
                $request->session()->forget('demo_mode');
            }
        }

        return $next($request);
    }
}
