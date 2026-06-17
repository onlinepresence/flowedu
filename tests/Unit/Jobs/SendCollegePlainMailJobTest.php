<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SendCollegePlainMailJob;
use App\Mail\CollegePlainMail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendCollegePlainMailJobTest extends TestCase
{
    public function test_job_sends_plain_mailable(): void
    {
        Mail::fake();

        $job = new SendCollegePlainMailJob('user@example.com', 'Subject line', "Line one\nLine two");
        $job->handle();

        Mail::assertSent(CollegePlainMail::class, function (CollegePlainMail $mail): bool {
            return $mail->hasTo('user@example.com')
                && $mail->subjectLine === 'Subject line'
                && $mail->bodyText === "Line one\nLine two";
        });
    }
}
