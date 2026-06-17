<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeacherPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        if ($user === null || $user->type !== 'teacher') {
            abort(403);
        }

        if (!$user->hasTeacherPermission($permission)) {
            abort(403);
        }

        return $next($request);
    }
}
