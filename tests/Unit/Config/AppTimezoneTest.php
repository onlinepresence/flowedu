<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use Tests\TestCase;

class AppTimezoneTest extends TestCase
{
    public function test_default_timezone_matches_legacy_accra(): void
    {
        $this->assertSame('Africa/Accra', config('app.timezone'));
    }
}
