<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminImpersonationService;
use Illuminate\Http\RedirectResponse;

class AdminImpersonationStopController extends Controller
{
    public function __invoke(AdminImpersonationService $service): RedirectResponse
    {
        $service->stop();

        return redirect()->route('admin.dashboard');
    }
}
