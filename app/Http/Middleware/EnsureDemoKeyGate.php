<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\DemoKeyVerifier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureDemoKeyGate
{
    public function __construct(private readonly DemoKeyVerifier $verifier) {}

    /**
     * Single-connection demo key gate.
     *
     * - Production (APP_DEMO unset/false): pass through untouched.
     * - Demo with a decodable DEMO_KEY env value (opaque encoded single
     *   token, or legacy plaintext): bypass and hydrate the session flag so
     *   the pass lasts with the browser session. Corrupt encoded values do
     *   not bypass — the key screen returns instead.
     * - Demo without env key: require session('demo_key_accepted') from a prior
     *   key entry; otherwise render the branded key-entry screen (403-style)
     *   and render nothing else.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('college.demo_mode', false)) {
            return $next($request);
        }

        // Identical code path: demo forces mail to log driver per request as well
        // (AppServiceProvider also forces at boot for non-request contexts).
        config(['mail.default' => 'log']);

        if (DemoKeyVerifier::hasStoredKey(config('college.demo_key'))) {
            if ($request->hasSession() && $request->session()->get('demo_key_accepted', false) !== true) {
                $request->session()->put('demo_key_accepted', true);
            }

            return $next($request);
        }

        if ($request->hasSession() && $request->session()->get('demo_key_accepted', false) === true) {
            return $next($request);
        }

        if ($request->routeIs('demo.key.*') || $request->is('up')) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('livewire/*')) {
            return response()->json(['message' => 'Demo key required.'], 403);
        }

        return response()->view('demo.key', [
            'error' => session('demo_key_error'),
        ], 403);
    }
}
