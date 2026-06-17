<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\CollegeNotificationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Queued user-facing HTML mail with explicit, JSON-serializable payload (subject + HTML body string).
 */
final class SendCollegeNotificationMailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $toAddress,
        public string $mailSubject,
        public string $htmlBody,
    ) {}

    public function handle(): void
    {
        Mail::to($this->toAddress)->send(new CollegeNotificationMail($this->mailSubject, $this->htmlBody));
    }
}
