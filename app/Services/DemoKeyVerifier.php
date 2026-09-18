<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;

final class DemoKeyVerifier
{
    /**
     * Validate a demo key-entry code.
     *
     * FOR NOW: accepts any non-empty code of sane format (8-64 chars,
     * alphanumerics plus dash/underscore) and logs the acceptance.
     *
     * TODO: replace the acceptance branch with a ControlDesk verify call
     * (claim-code validation) without restructuring callers — keep the
     * verify(string $code, ?string $host): bool seam.
     */
    public function verify(string $code, ?string $host = null): bool
    {
        $candidate = trim($code);

        if ($candidate === '') {
            return false;
        }

        if (! preg_match('/^[A-Za-z0-9\-_]{8,64}$/', $candidate)) {
            return false;
        }

        Log::info('demo.key.accepted', ['host' => $host]);

        return true;
    }
}
