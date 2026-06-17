<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Department;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDepartmentsExist
{
    /**
     * Legacy check_departments: at least one department before programs setup.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Department::query()->exists()) {
            return $next($request);
        }

        return redirect()
            ->route('admin.setup.departments')
            ->with('status', __('No active departments created. Programs cannot be added.'));
    }
}
