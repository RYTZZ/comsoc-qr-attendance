<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        \Illuminate\Support\Facades\Mail::extend('gmail-api', function (array $config = []) {
            return new \App\Mail\Transport\GmailApiTransport(
                (string) ($config['client_id'] ?? env('GOOGLE_CLIENT_ID', '')),
                (string) ($config['client_secret'] ?? env('GOOGLE_CLIENT_SECRET', '')),
                (string) ($config['refresh_token'] ?? env('GOOGLE_REFRESH_TOKEN', ''))
            );
        });

        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            $configuredUrl = config('app.url', 'https://comsochub.tech');

            if (app()->environment('production')) {
                // In production, strictly enforce HTTPS and the canonical production domain.
                // Prevent falling back to localhost or untrusted HTTP Host headers.
                $baseUrl = preg_replace('/^http:\/\//i', 'https://', (string) $configuredUrl);
                if (empty($baseUrl) || str_contains($baseUrl, 'localhost')) {
                    $baseUrl = 'https://comsochub.tech';
                }
            } else {
                // In local / staging / testing environments:
                if (!app()->runningInConsole() && request()->getHost() && !str_contains(request()->getHost(), 'localhost')) {
                    $isHttps = request()->isSecure();
                    $scheme = $isHttps ? 'https' : request()->getScheme();
                    $host = request()->getHost();
                    $port = request()->getPort();
                    $portSuffix = ($port && !in_array($port, [80, 443]) && !str_contains($host, ':')) ? ':' . $port : '';
                    $baseUrl = $scheme . '://' . $host . $portSuffix;
                } else {
                    $baseUrl = $configuredUrl ?: 'http://localhost:8000';
                }
            }

            return rtrim($baseUrl, '/') . route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false);
        });

        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            if (ResetPassword::$createUrlCallback) {
                $url = call_user_func(ResetPassword::$createUrlCallback, $notifiable, $token);
            } else {
                $url = route('password.reset', [
                    'token' => $token,
                    'email' => $notifiable->getEmailForPasswordReset(),
                ]);
            }

            $count = (int) config('auth.passwords.'.config('auth.defaults.passwords', 'users').'.expire', 15);

            return (new MailMessage)
                ->subject('Reset Your Password — COMSOC HUB')
                ->view('emails.password-reset', [
                    'url' => $url,
                    'user' => $notifiable,
                    'count' => $count,
                ])
                ->text('emails.password-reset-text', [
                    'url' => $url,
                    'user' => $notifiable,
                    'count' => $count,
                ]);
        });
    }
}
