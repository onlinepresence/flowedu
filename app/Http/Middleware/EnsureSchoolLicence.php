<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\SchoolLicenceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolLicence
{
    public function __construct(
        protected SchoolLicenceService $licenceService
    ) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if ($request->user() === null) {
            return $next($request);
        }

        if ($this->licenceService->can($feature)) {
            // Demo telemetry (read-only): record feature keys the prospect
            // actually opened so the next online demo-verify carries
            // modules_in_use[] as sales intel. Never enforcement.
            $this->recordDemoUse($feature);

            return $next($request);
        }

        $message = $this->licenceService->upgradeMessage($feature);

        return redirect()
            ->route('licence.required', ['feature' => $feature])
            ->with('system_message', $message);
    }

    private function recordDemoUse(string $feature): void
    {
        try {
            if (! (bool) config('college.demo_mode', false)) {
                return;
            }

            app(\App\Services\DemoKeyVerifier::class)->recordModuleUse($feature);
        } catch (\Throwable) {
            // Telemetry must never break navigation.
        }
    }
}
