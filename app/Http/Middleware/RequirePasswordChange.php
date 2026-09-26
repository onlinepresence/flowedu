<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordChange
{
    /**
     * Livewire endpoints a flagged user may still hit: logging out, and the
     * self-service password form on the profile page (which clears the flag).
     * Everything else over Livewire stays blocked until THEY change it.
     *
     * @var array<string, list<string>>
     */
    private const ALLOWED_LIVEWIRE_CALLS = [
        'layout.logout-button' => ['logout'],
        'profile.update-password-form' => ['updatePassword'],
    ];

    /**
     * Flagged accounts (seeded admin, invitees, admin resets) can reach the
     * change screen, profile, password update and logout — nothing else —
     * until they pick their own secret.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->requiresPasswordChange()) {
            return $next($request);
        }

        if ($request->routeIs('password.change', 'password.update', 'profile', 'logout') || $request->is('up')) {
            return $next($request);
        }

        if ($request->is('livewire/*') || $request->expectsJson()) {
            if ($this->isAllowedLivewireCall($request)) {
                return $next($request);
            }

            return response()->json(['message' => __('Password change required.')], 403);
        }

        return redirect()->route('password.change');
    }

    /**
     * Fail-closed sniff of Livewire update payloads: every component must be
     * allow-listed and every invoked method must be allow-listed. Anything
     * unrecognized (or missing) stays blocked.
     */
    private function isAllowedLivewireCall(Request $request): bool
    {
        $components = $request->input('components');
        if (! is_array($components) || $components === []) {
            return false;
        }

        foreach ($components as $component) {
            if (! is_array($component)) {
                return false;
            }

            $snapshot = json_decode((string) ($component['snapshot'] ?? ''), true);
            $name = is_array($snapshot) ? ($snapshot['memo']['name'] ?? null) : null;
            if (! is_string($name) || ! isset(self::ALLOWED_LIVEWIRE_CALLS[$name])) {
                return false;
            }

            $calls = $component['calls'] ?? [];
            if (! is_array($calls)) {
                return false;
            }

            foreach ($calls as $call) {
                if (! in_array($call['method'] ?? null, self::ALLOWED_LIVEWIRE_CALLS[$name], true)) {
                    return false;
                }
            }
        }

        return true;
    }
}
