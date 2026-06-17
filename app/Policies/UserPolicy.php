<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Services\SchoolLicenceService;

class UserPolicy
{
    public function __construct(
        private readonly SchoolLicenceService $licenceService
    ) {}

    /**
     * Start an impersonation session as the given actor viewing $target.
     */
    public function impersonate(User $actor, User $target): bool
    {
        if (! $this->licenceService->can('impersonation')) {
            return false;
        }

        if (! $actor->canStartImpersonation()) {
            return false;
        }

        if ($actor->is($target)) {
            return false;
        }

        if (! $target->active) {
            return false;
        }

        if ($target->canStartImpersonation()) {
            return false;
        }

        return true;
    }

    /**
     * Edit user fields from System Settings → User Accounts (licence-gated surface).
     */
    public function updateForUserSettings(User $actor, User $target): bool
    {
        return $this->mayManageTargetInUserSettings($actor, $target);
    }

    /**
     * Send a password reset link to the user's email from User Accounts.
     */
    public function sendPasswordResetForUserSettings(User $actor, User $target): bool
    {
        return $this->mayManageTargetInUserSettings($actor, $target);
    }

    /**
     * Lock or unlock (toggle active) from User Accounts.
     */
    public function toggleActiveForUserSettings(User $actor, User $target): bool
    {
        if (! $this->mayManageTargetInUserSettings($actor, $target)) {
            return false;
        }

        if ($actor->is($target) && $target->active) {
            return false;
        }

        return true;
    }

    private function mayManageTargetInUserSettings(User $actor, User $target): bool
    {
        if ($actor->type !== 'admin') {
            return false;
        }

        if (! $this->licenceService->can('system_admin')) {
            return false;
        }

        if ($target->isAdminOwner() && ! $actor->isAdminOwner()) {
            return false;
        }

        return true;
    }
}
