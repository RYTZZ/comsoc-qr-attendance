<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'superadmin@comsoc.local'],
            [
                'name' => 'Super Administrator',
                'password' => bcrypt('ChangeMe!2025'),
                'role' => 'super_admin',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        if (!AcademicYear::where('is_active', true)->exists()) {
            AcademicYear::create([
                'id' => \Illuminate\Support\Str::uuid(),
                'label' => 'AY 2025–2026',
                'year_start' => 2025,
                'year_end' => 2026,
                'is_active' => true,
            ]);
        }

        Setting::set('rankings_enabled', 'false', 'boolean');
        Setting::set('membership_fee', '100', 'float');
        Setting::set('app_version', '1.0.0', 'string');
    }
}
