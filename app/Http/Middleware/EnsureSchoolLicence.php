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
            return $next($request);
        }

        $message = $this->licenceService->upgradeMessage($feature);

        return redirect()
            ->route('licence.required', ['feature' => $feature])
            ->with('system_message', $message);
    }
}
