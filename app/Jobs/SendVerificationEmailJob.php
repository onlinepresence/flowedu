<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queued email-verification sender: carries only the user id (JSON-safe) and
 * no-ops if the account is gone or already verified by the time it runs.
 */
final class SendVerificationEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $userId,
    ) {}

    public function handle(): void
    {
        $user = User::query()->find($this->userId);
        if ($user === null || $user->hasVerifiedEmail()) {
            return;
        }

        // Notify directly: User::sendEmailVerificationNotification() dispatches
        // this job, so calling it here would re-queue instead of sending.
        $user->notify(new VerifyEmail);
    }
}
