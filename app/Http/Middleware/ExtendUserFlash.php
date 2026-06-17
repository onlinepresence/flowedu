<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * When {@see \App\Support\CollegeFlash::forNextRequestToo()} sets the extend flag,
 * keep one-flash keys alive for an additional request (legacy send_to_next_request parity).
 */
final class ExtendUserFlash
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->session()->pull('college.flash_extend', false)) {
            $request->session()->reflash();
        }

        return $response;
    }
}
