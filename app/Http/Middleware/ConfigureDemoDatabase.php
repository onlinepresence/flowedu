<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

final class ConfigureDemoDatabase
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isDemo = config('college.demo_mode') ||
                ($request->hasSession() && $request->session()->get('demo_mode', false)) ||
                $request->cookie('demo_mode') === '1';

        if ($isDemo) {
            $dbPath = storage_path('demo.sqlite');

            Config::set('database.connections.demo.database', $dbPath);
            Config::set('database.default', 'demo');
            DB::reconnect('demo');
            Config::set('licence.enforce', false);

            // If DB not ready and we're not already on the setup route, redirect there
            $isReady = file_exists($dbPath) && $this->isDatabaseReady();

            if (! $isReady && ! $request->routeIs('demo.setup')) {
                return redirect()->route('demo.setup');
            }
        }

        return $next($request);
    }

    private function isDatabaseReady(): bool
    {
        try {
            return School::count() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}
