<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\Models\User;

/**
 * Legacy delete-account: remove user row (cascades student + guardians).
 */
final class DeleteStudentRegistrationAction
{
    public function execute(User $user): void
    {
        $user->delete();
    }
}
