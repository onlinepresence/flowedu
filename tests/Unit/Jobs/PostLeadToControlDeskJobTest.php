<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\PostLeadToControlDeskJob;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PostLeadToControlDeskJobTest extends TestCase
{
    private function lead(): array
    {
        return [
            'product_slug' => 'flowedu',
            'contact' => [
                'name' => 'Ebenezer Boateng',
                'role' => 'Registrar',
                'phone' => '0249100268',
                'email' => 'ebenezer@accra.edu.gh',
                'college' => 'Accra College of Education',
            ],
            'band' => '1-500',
            'modules' => ['finance'],
            'quote' => ['upfront' => 100.0, 'renewal' => 50.0, 'lines' => []],
        ];
    }

    public function test_handle_succeeds_on_200(): void
    {
        config(['controlplane.url' => 'https://control.test']);
        Http::fake(['*/api/v1/leads' => Http::response(['id' => 7], 201)]);

        (new PostLeadToControlDeskJob($this->lead()))->handle(app(\App\Services\ControlPlane\ControlPlaneClient::class));

        Http::assertSent(function ($request): bool {
            return str_ends_with($request->url(), '/api/v1/leads')
                && $request['product_slug'] === 'flowedu';
        });

        $this->assertTrue(true);
    }

    public function test_handle_ignores_404_without_throwing(): void
    {
        config(['controlplane.url' => 'https://control.test']);
        Http::fake(['*/api/v1/leads' => Http::response([], 404)]);

        // Must not throw: unknown/inactive slugs never retry.
        (new PostLeadToControlDeskJob($this->lead()))->handle(app(\App\Services\ControlPlane\ControlPlaneClient::class));

        $this->assertTrue(true);
    }

    public function test_handle_throws_for_retry_on_500(): void
    {
        config(['controlplane.url' => 'https://control.test']);
        Http::fake(['*/api/v1/leads' => Http::response([], 500)]);

        $this->expectException(\RuntimeException::class);

        (new PostLeadToControlDeskJob($this->lead()))->handle(app(\App\Services\ControlPlane\ControlPlaneClient::class));
    }

    public function test_retry_policy(): void
    {
        $job = new PostLeadToControlDeskJob($this->lead());

        $this->assertSame(3, $job->tries);
        $this->assertSame([60, 300, 900], $job->backoff());
    }
}
