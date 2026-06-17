<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\CollegePlainMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Replaces legacy serialized “email” queue payloads with explicit, JSON-serializable data.
 */
final class SendCollegePlainMailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $toAddress,
        public string $subjectLine,
        public string $bodyText,
    ) {}

    public function handle(): void
    {
        Mail::to($this->toAddress)->send(new CollegePlainMail($this->subjectLine, $this->bodyText));
    }
}
