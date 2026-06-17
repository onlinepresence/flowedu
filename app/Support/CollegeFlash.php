<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Flash helpers mirroring legacy {@code send_to_next_request} + {@code flush_session} behaviour.
 */
final class CollegeFlash
{
    /**
     * Flash a session value and keep it visible on the following request as well (then it expires).
     *
     * @param  non-empty-string  $key  e.g. status, backup_error
     */
    public static function forNextRequestToo(string $key, mixed $value): void
    {
        session()->flash($key, $value);
        session()->put('college.flash_extend', true);
    }
}
