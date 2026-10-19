<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queued password-reset sender: carries only the user id and token (JSON-safe)
 * and no-ops if the account is gone by the time it runs.
 */
final class SendPasswordResetEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $userId,
        public string $token,
    ) {}

    public function handle(): void
    {
        $user = User::query()->find($this->userId);
        if ($user === null) {
            return;
        }

        $user->notify(new ResetPassword($this->token));
    }
}
