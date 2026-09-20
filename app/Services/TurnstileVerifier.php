<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cloudflare Turnstile token verification for the public landing form.
 *
 * Fail-open when unconfigured (no keys = local/dev/tests): verification is
 * skipped so the form keeps working. When configured, a missing or invalid
 * token rejects the submission.
 */
final class TurnstileVerifier
{
    public static function configured(): bool
    {
        return trim((string) config('captcha.turnstile_secret_key')) !== ''
            && trim((string) config('captcha.turnstile_site_key')) !== '';
    }

    public static function verify(?string $token, ?string $ip = null): bool
    {
        $secret = trim((string) config('captcha.turnstile_secret_key'));

        if ($secret === '') {
            return true;
        }

        if ($token === null || trim($token) === '') {
            return false;
        }

        try {
            $response = Http::timeout(8)
                ->asForm()
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', array_filter([
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $ip,
                ]));

            return (bool) $response->json('success', false);
        } catch (\Throwable $e) {
            Log::warning('turnstile.verify-failed', ['message' => $e->getMessage()]);

            return false;
        }
    }
}
