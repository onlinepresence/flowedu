<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AdminImpersonationLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class AdminImpersonationService
{
    public function start(User $actor, User $target, ?Request $request = null): void
    {
        Gate::forUser($actor)->authorize('impersonate', $target);

        if (Session::has('college_impersonator_id')) {
            throw new AccessDeniedHttpException(__('Nested impersonation is not allowed.'));
        }

        $request ??= request();

        DB::transaction(function () use ($actor, $target, $request): void {
            $log = AdminImpersonationLog::query()->create([
                'impersonator_user_id' => $actor->id,
                'impersonated_user_id' => $target->id,
                'started_at' => now(),
                'ended_at' => null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            Auth::login($target, remember: false);
            Session::put('college_impersonator_id', $actor->id);
            Session::put('college_impersonation_log_id', $log->id);
        });
    }

    public function stop(?Request $request = null): void
    {
        $impersonatorId = Session::get('college_impersonator_id');
        $logId = Session::get('college_impersonation_log_id');

        if (! is_int($impersonatorId) && ! is_numeric($impersonatorId)) {
            throw new AccessDeniedHttpException(__('Not impersonating.'));
        }

        if (! is_int($logId) && ! is_numeric($logId)) {
            throw new AccessDeniedHttpException(__('Not impersonating.'));
        }

        $impersonatorId = (int) $impersonatorId;
        $logId = (int) $logId;

        $log = AdminImpersonationLog::query()->find($logId);
        if ($log === null || $log->ended_at !== null) {
            Session::forget(['college_impersonator_id', 'college_impersonation_log_id']);
            throw new AccessDeniedHttpException(__('Invalid impersonation session.'));
        }

        $currentId = Auth::id();
        if ($currentId === null || $log->impersonated_user_id !== (int) $currentId) {
            throw new AccessDeniedHttpException(__('Invalid impersonation session.'));
        }

        if ($log->impersonator_user_id !== $impersonatorId) {
            throw new AccessDeniedHttpException(__('Invalid impersonation session.'));
        }

        $impersonator = User::query()->find($impersonatorId);
        if ($impersonator === null || ! $impersonator->active) {
            Session::forget(['college_impersonator_id', 'college_impersonation_log_id']);
            $log->update(['ended_at' => now()]);

            throw new AccessDeniedHttpException(__('Original administrator account is unavailable.'));
        }

        $log->update(['ended_at' => now()]);
        Session::forget(['college_impersonator_id', 'college_impersonation_log_id']);

        Auth::login($impersonator, remember: false);
        Session::regenerate();
    }
}
