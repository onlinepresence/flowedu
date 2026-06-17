<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    /**
     * @param  string  $roles  Comma-separated user_roles.name values (e.g. college.admin-role:owner,registrar)
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = $request->user();
        if ($user === null || $user->type !== 'admin') {
            abort(403);
        }

        $allowed = array_values(array_filter(array_map('trim', explode(',', $roles))));
        $slug = $user->adminRoleSlug();
        if ($slug === null || ! in_array($slug, $allowed, true)) {
            abort(403);
        }

        return $next($request);
    }
}
