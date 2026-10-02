<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Whether outbound email is actually set up: the strictest reading of the
 * mail config (a real mailer + sender + SMTP credentials where relevant).
 * Demo mode forces the log driver, so delivery is never "set up" there.
 */
final class MailSetup
{
    public static function isConfigured(): bool
    {
        if ((bool) config('college.demo_mode', false)) {
            return false;
        }

        $mailer = (string) config('mail.default', 'log');
        if ($mailer === '' || in_array($mailer, ['log', 'array'], true)) {
            return false;
        }

        if (! filled(config('mail.from.address'))) {
            return false;
        }

        if ($mailer === 'smtp') {
            return filled(config('mail.mailers.smtp.host'))
                && filled(config('mail.mailers.smtp.username'))
                && filled(config('mail.mailers.smtp.password'));
        }

        return true;
    }
}
