<?php

namespace App\Http\Middleware;

use App\Models\School;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolBootstrap
{
    /**
     * When no school exists, set admin_register and optionally purge users (legacy check_school).
     * Redirect guests away from login/home to register unless they are already registering.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (School::current() !== null) {
            return $next($request);
        }

        if (config('college.bootstrap_purge_users', true)) {
            User::query()->delete();
        }

        $request->session()->put('admin_register', true);

        if ($request->routeIs('register')) {
            return $next($request);
        }

        return redirect()->route('register');
    }
}
