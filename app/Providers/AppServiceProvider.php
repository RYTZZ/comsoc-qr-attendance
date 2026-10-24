<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;
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
        // Admin-level accounts (existing isAdmin(): super_admin/admin/comsoc_admin/president/executive_secretary)
        // or an organizer explicitly assigned to the event may export its attendance sheet.
        Gate::define('export-attendance-sheet', function (User $user, Event $event) {
            if ($user->isAdmin()) {
                return true;
            }

            return $user->isEventOrganizer()
                && $event->organizers()->where('users.id', $user->id)->exists();
        });

        \Illuminate\Support\Facades\Blade::component('layouts.admin', 'admin-layout');
        \Illuminate\Support\Facades\Blade::component('layouts.public-website', 'public-website-layout');

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
