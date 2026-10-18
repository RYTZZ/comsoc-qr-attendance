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
        $exceptions->render(function (\Illuminate\Database\QueryException $e, \Illuminate\Http\Request $request) {
            \Illuminate\Support\Facades\Log::error('Database Query Error: ' . $e->getMessage(), [
                'sqlstate' => $e->getCode(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_id' => $request->user()?->id,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => config('app.debug')
                        ? 'A database query exception occurred: ' . $e->getMessage()
                        : 'A temporary database processing error occurred. Please try again later.',
                ], 500);
            }

            if (!config('app.debug')) {
                return response()->view('errors.500', [
                    'message' => 'A database error occurred while processing your request. Please try again shortly.',
                ], 500);
            }

            return null;
        });
    })->create();
