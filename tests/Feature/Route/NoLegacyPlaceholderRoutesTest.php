<?php

declare(strict_types=1);

namespace Tests\Feature\Route;

use Tests\TestCase;

class NoLegacyPlaceholderRoutesTest extends TestCase
{
    public function test_route_php_files_do_not_use_legacy_placeholder_view(): void
    {
        $dir = base_path('routes');
        $files = glob($dir.DIRECTORY_SEPARATOR.'*.php') ?: [];

        foreach ($files as $file) {
            $content = (string) file_get_contents($file);
            $this->assertStringNotContainsString(
                'legacy.placeholder',
                $content,
                basename($file).' must not register the removed placeholder view.'
            );
        }
    }
}
