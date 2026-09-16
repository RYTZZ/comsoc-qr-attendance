<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\RequirePasswordChange;
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
        $middleware->trustProxies(
            at: env('TRUSTED_PROXIES', null),
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
                     \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
                     \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
                     \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
        );

        $middleware->alias([
            'role' => EnsureRole::class,
            'require_password_change' => RequirePasswordChange::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'kiosk/*/scan',
            'kiosk/*/snack/scan',
            'kiosk/health',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
