<?php

use App\Http\Middleware\EnsureEmailOtpVerified;
use App\Http\Middleware\EnsureOnboardingCompleted;
use App\Http\Middleware\EnsureUserIsSubscribed;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'email.otp' => EnsureEmailOtpVerified::class,
            'subscribed' => EnsureUserIsSubscribed::class,
            'onboarded' => EnsureOnboardingCompleted::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
