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
     * - Production without a held key (APP_DEMO unset/false): pass through
     *   untouched — the gate never challenges paying installs.
     * - Production WITH a held demo key: honor it opportunistically (hydrate
     *   the session flag, run the throttled re-verify so revocation bites)
     *   but still never challenge: no key screen is ever rendered outside
     *   demo mode. A Desk-signed key needs no env flag set beforehand.
     * - Demo mode (APP_DEMO true): full gate as documented below.
     * - Demo key routes + /up always pass (otherwise the key screen itself
     *   would redirect).
     * - Boot + ~24h re-verify: when a stored DEMO_KEY exists, the verifier
     *   re-POSTs the bare code at most once per 24h (throttled via
     *   demo.last_online_check). Offline failures keep serving the cached
     *   document until expires_at; a definitive revoked answer sticks and
     *   the gate stops bypassing until a fresh key lands. Every online hit
     *   stamps the check so the desk's last_used_at stays fresh.
     * - Demo with a valid unlock (cached/stored document verifies, or an
     *   ops-trusted env bare code pending its first online verify):
     *   bypass and hydrate the session flag so the pass lasts with the
     *   browser session. Corrupt, expired, host-mismatched or revoked keys
     *   do not bypass — the key screen returns instead.
     * - Demo without env key: require session('demo_key_accepted') from a prior
     *   key entry; otherwise render the branded key-entry screen (403-style)
     *   and render nothing else.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('college.demo_mode', false)) {
            // Opportunistic honor only — never challenge production traffic.
            try {
                $this->verifier->reverifyIfDue($request->getHost());
            } catch (\Throwable) {
                // Telemetry/re-verify must never break navigation.
            }

            try {
                if ($this->verifier->isUnlocked($request->getHost())) {
                    if ($request->hasSession() && $request->session()->get('demo_key_accepted', false) !== true) {
                        $request->session()->put('demo_key_accepted', true);
                    }
                }
            } catch (\Throwable) {
                // Lookup failure means locked — pass through untouched.
            }

            return $next($request);
        }

        // Identical code path: demo forces mail to log driver per request as well
        // (AppServiceProvider also forces at boot for non-request contexts).
        config(['mail.default' => 'log']);

        if ($request->routeIs('demo.key.*') || $request->is('up')) {
            return $next($request);
        }

        // Boot + ~24h re-verify (throttled, never throws, never blocks on
        // network failure). Revocation sticks via isRevoked().
        try {
            $this->verifier->reverifyIfDue($request->getHost());
        } catch (\Throwable) {
            // Telemetry/re-verify must never break navigation.
        }

        try {
            if ($this->verifier->isUnlocked($request->getHost())) {
                if ($request->hasSession() && $request->session()->get('demo_key_accepted', false) !== true) {
                    $request->session()->put('demo_key_accepted', true);
                }

                return $next($request);
            }
        } catch (\Throwable) {
            // Lookup failure means locked — fall through to the key screen.
        }

        if ($request->hasSession() && $request->session()->get('demo_key_accepted', false) === true) {
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
