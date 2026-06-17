<?php

declare(strict_types=1);

namespace App\Mail;

use App\Jobs\SendCollegePlainMailJob;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * JSON-safe queued mail path: pair with {@see SendCollegePlainMailJob}.
 */
final class CollegePlainMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $bodyText,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.college-plain-text',
            with: [
                'bodyText' => $this->bodyText,
            ],
        );
    }
}
