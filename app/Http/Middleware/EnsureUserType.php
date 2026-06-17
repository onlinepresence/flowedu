<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserType
{
    /**
     * @param  string  ...$types  Legacy users.type values: admin, teacher, student
     */
    public function handle(Request $request, Closure $next, string ...$types): Response
    {
        $user = $request->user();

        if ($user === null || ! in_array($user->type, $types, true)) {
            abort(403);
        }

        return $next($request);
    }
}
