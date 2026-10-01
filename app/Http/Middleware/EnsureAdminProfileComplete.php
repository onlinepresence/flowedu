<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Models\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminProfileComplete
{
    /**
     * Legacy valid_admin: username required before admin areas.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ($user->type !== 'admin' && $user->type !== 'staff')) {
            return $next($request);
        }

        if ($user->type === 'admin') {
            UserRole::ensureSystemRoles();
            $user->loadMissing('admin');
            // Bootstrap self-healing only: the first account becomes owner
            // when the system has none yet. Once an owner exists, a null
            // role stays null (owner assigns roles explicitly) — otherwise
            // every role-less admin would silently become owner.
            if ($user->admin !== null && $user->admin->type === null) {
                $ownerId = UserRole::query()->where('name', 'owner')->value('id');
                if ($ownerId !== null && ! Admin::query()->where('type', $ownerId)->exists()) {
                    $user->admin->update(['type' => $ownerId]);
                }
            }
        }

        $username = $user->username;

        if ($username !== null && $username !== '') {
            return $next($request);
        }

        return redirect()
            ->route('admin.setup.personal')
            ->with('status', __('Complete your user profile to proceed.'));
    }
}
