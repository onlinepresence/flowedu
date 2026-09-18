<?php

use App\Http\Middleware\EnsureAdminProfileComplete;
use App\Http\Middleware\EnsureAdminRole;
use App\Http\Middleware\EnsureAdmissionOpen;
use App\Http\Middleware\EnsureDepartmentsExist;
use App\Http\Middleware\EnsureSchoolBootstrap;
use App\Http\Middleware\EnsureSchoolLicence;
use App\Http\Middleware\EnsureSchoolReady;
use App\Http\Middleware\EnsureServerNotDown;
use App\Http\Middleware\EnsureStudentReady;
use App\Http\Middleware\EnsureTeacherOnboarded;
use App\Http\Middleware\EnsureTeacherSetupGate;
use App\Http\Middleware\EnsureUserActive;
use App\Http\Middleware\EnsureUserType;
use App\Http\Middleware\EnsureDemoKeyGate;
use App\Http\Middleware\ExtendUserFlash;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectUsersTo(fn () => route('post.login.redirect'));
        $middleware->alias([
            'college.bootstrap' => EnsureSchoolBootstrap::class,
            'college.admission' => EnsureAdmissionOpen::class,
            'college.user-type' => EnsureUserType::class,
            'college.licence' => EnsureSchoolLicence::class,
            'college.admin-role' => EnsureAdminRole::class,
            'college.user-active' => EnsureUserActive::class,
            'college.school-ready' => EnsureSchoolReady::class,
            'college.departments-exist' => EnsureDepartmentsExist::class,
            'college.valid-admin' => EnsureAdminProfileComplete::class,
            'college.valid-teacher' => EnsureTeacherOnboarded::class,
            'college.teacher-setup-gate' => EnsureTeacherSetupGate::class,
            'college.student-ready' => EnsureStudentReady::class,
            'college.teacher-permission' => \App\Http\Middleware\EnsureTeacherPermission::class,
        ]);

        $middleware->web(prepend: [
            EnsureServerNotDown::class,
        ]);

        $middleware->web(append: [
            EnsureDemoKeyGate::class,
            EnsureUserActive::class,
            ExtendUserFlash::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('livewire/*')) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->guest(route('login'));
        });
    })->create();
