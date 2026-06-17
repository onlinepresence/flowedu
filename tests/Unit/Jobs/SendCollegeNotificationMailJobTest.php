<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SendCollegeNotificationMailJob;
use App\Mail\CollegeNotificationMail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendCollegeNotificationMailJobTest extends TestCase
{
    public function test_job_sends_html_mailable(): void
    {
        Mail::fake();

        $job = new SendCollegeNotificationMailJob('user@example.com', 'Subject line', '<p>Hello</p>');
        $job->handle();

        Mail::assertSent(CollegeNotificationMail::class, function (CollegeNotificationMail $mail): bool {
            return $mail->hasTo('user@example.com')
                && $mail->mailSubject === 'Subject line'
                && $mail->htmlBody === '<p>Hello</p>';
        });
    }
}
