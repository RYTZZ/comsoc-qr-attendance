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
                'nickname' => 'RootAdmin',
                'student_number' => 'ADM-0001',
                'password' => bcrypt('ChangeMe!2025'),
                'role' => 'super_admin',
                'is_active' => true,
                'is_eligible' => true,
                'can_collect_rewards' => true,
                'must_change_password' => false,
            ]
        );

        User::firstOrCreate(
            ['email' => 'comsocadmin@comsoc.local'],
            [
                'name' => 'COMSOC Administrator',
                'nickname' => 'COMSOC Lead',
                'student_number' => 'ADM-0002',
                'password' => bcrypt('ChangeMe!2025'),
                'role' => 'comsoc_admin',
                'is_active' => true,
                'is_eligible' => true,
                'can_collect_rewards' => true,
                'must_change_password' => false,
            ]
        );

        User::firstOrCreate(
            ['email' => 'organizer@comsoc.local'],
            [
                'name' => 'Event Organizer',
                'nickname' => 'Marshal One',
                'student_number' => 'ORG-0001',
                'password' => bcrypt('ChangeMe!2025'),
                'role' => 'event_organizer',
                'is_active' => true,
                'is_eligible' => true,
                'can_collect_rewards' => false,
                'must_change_password' => false,
            ]
        );

        User::firstOrCreate(
            ['email' => 'member@sorsu.edu.ph'],
            [
                'name' => 'Juan Dela Cruz',
                'nickname' => 'CyberJuan',
                'student_number' => '2023-01042',
                'password' => bcrypt('ChangeMe!2025'),
                'role' => 'member',
                'is_active' => true,
                'is_eligible' => true,
                'can_collect_rewards' => false,
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

        Setting::set('rankings_enabled', 'true', 'boolean');
        Setting::set('membership_fee', '100', 'float');
        Setting::set('app_version', '2.0.0', 'string');
        Setting::set('exp_large_correction_threshold', '500', 'integer');

        $ay = AcademicYear::where('is_active', true)->first();
        $admin = User::where('role', 'super_admin')->first();

        \App\Models\Event::firstOrCreate(
            ['name' => 'From Idea to App: Building a Real-World Product'],
            [
                'academic_year_id' => $ay?->id,
                'created_by' => $admin?->id,
                'description' => 'Flagship practical engineering seminar and workshop on building products.',
                'location' => 'Audio-Visual Hall / ICT Lab 3',
                'event_date' => now()->addDays(5),
                'status' => 'published',
                'is_published' => true,
                'max_participants' => 200,
            ]
        );

        $this->call([
            GamificationSeeder::class,
        ]);
    }
}
