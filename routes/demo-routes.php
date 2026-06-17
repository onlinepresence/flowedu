<?php

use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::post('/demo/toggle', function (Request $request) {
    $wasDemo = $request->session()->get('demo_mode', false);

    if ($wasDemo) {
        // Switch back to Live mode
        $liveUserId = $request->session()->get('live_user_id');
        $request->session()->forget('demo_mode');
        $request->session()->forget('live_user_id');

        Auth::logout();

        // Restore default connection
        config(['database.default' => env('DB_CONNECTION', 'mysql')]);
        DB::purge();

        if ($liveUserId) {
            $liveUser = User::find($liveUserId);
            if ($liveUser) {
                Auth::login($liveUser);

                return redirect('/')
                    ->with('success', 'Switched back to Live Mode. Your session has been restored.')
                    ->withoutCookie('demo_mode');
            }
        }

        return redirect('/login')
            ->with('success', 'Switched to Live Mode.')
            ->withoutCookie('demo_mode');
    } else {
        // Switch to Demo Mode
        $user = Auth::user();
        if (! $user || $user->type !== 'admin' || (! $user->isAdminOwner() && $user->adminRoleSlug() !== 'system_admin')) {
            abort(403, 'Unauthorized.');
        }

        $liveUserId = $user->id;
        $request->session()->put('live_user_id', $liveUserId);

        $request->session()->put('demo_mode', true);
        Auth::logout();

        // Boot and seed SQLite sandbox database immediately if not exists or if school is missing
        $dbPath = storage_path('demo.sqlite');
        $dir = dirname($dbPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $needsSeeding = false;
        if (! file_exists($dbPath)) {
            touch($dbPath);
            $needsSeeding = true;
        }

        config(['database.connections.demo.database' => $dbPath]);
        config(['database.default' => 'demo']);
        DB::purge();

        if (! $needsSeeding) {
            try {
                if (School::count() === 0) {
                    $needsSeeding = true;
                }
            } catch (Exception $e) {
                $needsSeeding = true;
            }
        }

        if ($needsSeeding) {
            Artisan::call('migrate', ['--database' => 'demo', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DemoDataSeeder', '--database' => 'demo', '--force' => true]);
        }

        // Log in the demo admin automatically
        $demoAdmin = User::where('email', 'admin@demo.com')->first();
        if ($demoAdmin) {
            Auth::login($demoAdmin);

            return redirect('/')
                ->with('success', 'Switched to Demo Mode. Logged in as Demo Admin.')
                ->withCookie(cookie()->forever('demo_mode', '1'));
        }

        return redirect('/login')
            ->with('success', 'Switched to Demo Mode.')
            ->withCookie(cookie()->forever('demo_mode', '1'));
    }
})->name('demo.toggle');

Route::post('/demo/reset', function (Request $request) {
    if (! $request->session()->get('demo_mode', false) && ! config('college.demo_mode')) {
        abort(403, 'Not running in Demo Mode.');
    }

    Auth::logout();

    $dbPath = storage_path('demo.sqlite');
    if (file_exists($dbPath)) {
        // Close SQLite connections by purging database managers
        DB::disconnect('demo');
        DB::purge();

        // Wait briefly for lock release, then delete the file
        usleep(100000);
        @unlink($dbPath);
    }

    return redirect('/login')->with('success', 'Demo database reset successfully. A new sandbox database will be generated on your next request.');
})->name('demo.reset');

Route::post('/demo/toggle-global', function (Request $request) {
    $envPath = base_path('.env');
    if (file_exists($envPath)) {
        $content = file_get_contents($envPath);
        $enabled = $request->input('enabled') === 'true' || $request->input('enabled') === '1';

        if (str_contains($content, 'APP_DEMO=')) {
            $content = preg_replace('/APP_DEMO=\w*/', 'APP_DEMO='.($enabled ? 'true' : 'false'), $content);
        } else {
            $content .= "\nAPP_DEMO=".($enabled ? 'true' : 'false')."\n";
        }
        file_put_contents($envPath, $content);
    }

    return back()->with('success', 'Global forced Demo Mode state updated in environment file.');
})->name('demo.toggle-global')->middleware(['auth']);

Route::get('/demo/setup', function () {
    $dbPath = storage_path('demo.sqlite');

    if (! file_exists($dbPath)) {
        touch($dbPath);
    }

    Artisan::call('migrate', ['--database' => 'demo', '--force' => true]);
    Artisan::call('db:seed', [
        '--class' => 'Database\\Seeders\\DemoDataSeeder',
        '--database' => 'demo',
        '--force' => true,
    ]);

    return redirect()->route('login')->with('status', 'Demo ready!');
})->name('demo.setup');
