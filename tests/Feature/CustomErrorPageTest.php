<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomErrorPageTest extends TestCase
{
    /**
     * Test that all custom error preview pages render correctly.
     */
    public function test_custom_error_pages_render_successfully(): void
    {
        $errorCodes = ['401', '403', '404', '419', '429', '500', '503'];

        foreach ($errorCodes as $code) {
            $response = $this->get("/__testing/errors/{$code}");

            $response->assertStatus((int) $code);
            $response->assertSeeText("Error {$code}");
            $response->assertSeeText("Troubleshooting Information");
            $response->assertSeeText("Timestamp");
            $response->assertSeeText("Request Path");
        }
    }
}
