<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\ControlPlane\ControlPlaneClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Posts a landing quote lead to ControlDesk. Scalar-only payload (repo
 * queue norm). Retried on network/5xx; 404 (unknown/inactive product slug)
 * is logged once and never retried. Final failure is silent (logged) — the
 * admin notification email remains the fallback either way.
 */
final class PostLeadToControlDeskJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * @param array<string, mixed> $lead
     */
    public function __construct(
        public array $lead,
    ) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(ControlPlaneClient $client): void
    {
        $result = $client->postLead($this->lead);

        if (($result['status'] ?? null) === 'notFound') {
            Log::warning('controlplane.lead-unknown-product', [
                'product_slug' => $this->lead['product_slug'] ?? null,
            ]);

            return;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('controlplane.lead-failed', [
            'message' => $e->getMessage(),
            'product_slug' => $this->lead['product_slug'] ?? null,
        ]);
    }
}
