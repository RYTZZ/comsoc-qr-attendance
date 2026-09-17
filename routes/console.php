<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('incidents:archive')->daily();

Artisan::command('make:superadmin {email=admin@comsoc.local} {password=Admin@123456}', function (string $email, string $password) {
    $user = \App\Models\User::updateOrCreate(
        ['email' => $email],
        [
            'name' => 'Super Administrator',
            'password' => \Illuminate\Support\Facades\Hash::make($password),
            'role' => 'super_admin',
            'is_active' => true,
            'must_change_password' => false,
        ]
    );

    \App\Models\Setting::set('rankings_enabled', 'false', 'boolean');
    \App\Models\Setting::set('membership_fee', '100', 'float');
    \App\Models\Setting::set('app_version', '1.0.0', 'string');

    if (!\App\Models\AcademicYear::where('is_active', true)->exists()) {
        \App\Models\AcademicYear::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'label' => 'AY 2025–2026',
            'year_start' => 2025,
            'year_end' => 2026,
            'is_active' => true,
        ]);
    }

    $this->info("Super Admin created successfully!");
    $this->line("Email: {$email}");
    $this->line("Password: {$password}");
})->purpose('Create or update super admin account');
