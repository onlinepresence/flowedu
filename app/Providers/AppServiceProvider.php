<?php

namespace App\Providers;

use App\Models\User;
use App\Services\AdminNavPermissionService;
use App\Policies\UserPolicy;
use App\Services\NavigationLicenceService;
use App\Services\SchoolLicenceService;
use App\Services\SpreadsheetImportService;
use App\Services\StudentLicenceCapService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SchoolLicenceService::class);
        $this->app->singleton(StudentLicenceCapService::class);
        $this->app->singleton(NavigationLicenceService::class);
        $this->app->singleton(AdminNavPermissionService::class);
        $this->app->singleton(SpreadsheetImportService::class);

        // Load global helper functions
        require_once app_path('Helpers/helpers.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Gate::policy(User::class, UserPolicy::class);

        Gate::before(function (?User $user, string $ability): ?bool {
            if ($user === null || ! str_starts_with($ability, 'admin.')) {
                return null;
            }
            if ($user->isAdminOwner()) {
                return true;
            }
            if ($user->type === 'admin' && $user->adminRoleSlug() === 'system_admin') {
                return true;
            }

            return null;
        });

        foreach (array_keys(config('college.admin_permissions', [])) as $slug) {
            Gate::define('admin.'.$slug, function (User $user) use ($slug): bool {
                if ($user->type !== 'admin' && $user->type !== 'staff') {
                    return false;
                }

                return in_array($slug, $user->adminPermissionSlugs(), true);
            });
        }

        View::composer('layouts.guest', function ($view): void {
            $name = Route::currentRouteName();
            $byRoute = [
                'register' => [
                    'light' => 'images/auth/create-account-office.jpeg',
                    'dark' => 'images/auth/create-account-office-dark.jpeg',
                ],
                'password.request' => [
                    'light' => 'images/auth/forgot-password-office.jpeg',
                    'dark' => 'images/auth/forgot-password-office-dark.jpeg',
                ],
                'password.reset' => [
                    'light' => 'images/auth/forgot-password-office.jpeg',
                    'dark' => 'images/auth/forgot-password-office-dark.jpeg',
                ],
            ];
            $pair = $byRoute[$name] ?? [
                'light' => 'images/auth/login-office.jpeg',
                'dark' => 'images/auth/login-office-dark.jpeg',
            ];
            $view->with([
                'authHeroLight' => $pair['light'],
                'authHeroDark' => $pair['dark'],
            ]);
        });
    }
}
