<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\Season;
use Illuminate\Database\Seeder;

class GamificationSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['level' => 1, 'title' => 'Novice Explorer', 'min_xp' => 0, 'badge_icon' => 'sparkles'],
            ['level' => 2, 'title' => 'Tech Apprentice', 'min_xp' => 250, 'badge_icon' => 'code'],
            ['level' => 3, 'title' => 'Code Vanguard', 'min_xp' => 600, 'badge_icon' => 'cpu'],
            ['level' => 4, 'title' => 'System Architect', 'min_xp' => 1200, 'badge_icon' => 'terminal'],
            ['level' => 5, 'title' => 'Master of Algorithms', 'min_xp' => 2000, 'badge_icon' => 'award'],
        ];

        foreach ($levels as $lvl) {
            Level::firstOrCreate(['level' => $lvl['level']], $lvl);
        }

        $activeAy = AcademicYear::active() ?? AcademicYear::first();
        if ($activeAy && !Season::active()) {
            Season::firstOrCreate(
                ['academic_year_label' => $activeAy->label],
                [
                    'name' => "Season " . $activeAy->label,
                    'academic_year_label' => $activeAy->label,
                    'start_date' => $activeAy->year_start ? $activeAy->year_start . '-08-01' : now()->toDateString(),
                    'end_date' => $activeAy->year_end ? $activeAy->year_end . '-07-31' : now()->addYear()->toDateString(),
                    'is_active' => true,
                    'status' => 'active',
                    'settings' => [
                        'allow_point_rollover' => false,
                    ],
                ]
            );
        }

        // Standardized Attendance and Organization Badges with Lucide Icons
        $achievements = [
            [
                'slug' => 'perfect-attendance',
                'title' => 'Perfect Attendance',
                'description' => 'Attended 100% of eligible events and activities in the semester.',
                'icon' => 'calendar-check',
                'category' => 'Honor',
                'criteria_type' => 'event_attendance',
                'criteria_threshold' => 10,
                'xp_bonus' => 500,
                'points_bonus' => 500,
                'is_active' => true,
            ],
            [
                'slug' => 'event-enthusiast',
                'title' => 'Event Enthusiast',
                'description' => 'Attended 5 or more organization events.',
                'icon' => 'calendar-check-2',
                'category' => 'Participation',
                'criteria_type' => 'event_attendance',
                'criteria_threshold' => 5,
                'xp_bonus' => 250,
                'points_bonus' => 250,
                'is_active' => true,
            ],
            [
                'slug' => 'active-member',
                'title' => 'Active Member',
                'description' => 'Attended your first verified COMSOC activity.',
                'icon' => 'users',
                'category' => 'Participation',
                'criteria_type' => 'event_attendance',
                'criteria_threshold' => 1,
                'xp_bonus' => 100,
                'points_bonus' => 100,
                'is_active' => true,
            ],
            [
                'slug' => 'comsoc-champion',
                'title' => 'COMSOC Champion',
                'description' => 'Earned top tier honors through exemplary event attendance and active engagement.',
                'icon' => 'trophy',
                'category' => 'Honor',
                'criteria_type' => 'xp_earned',
                'criteria_threshold' => 1000,
                'xp_bonus' => 1000,
                'points_bonus' => 1000,
                'is_active' => true,
            ],
            [
                'slug' => 'consistency-award',
                'title' => 'Consistency Award',
                'description' => 'Maintained consistent engagement across consecutive campus workshops and assemblies.',
                'icon' => 'trending-up',
                'category' => 'Honor',
                'criteria_type' => 'event_attendance',
                'criteria_threshold' => 3,
                'xp_bonus' => 150,
                'points_bonus' => 150,
                'is_active' => true,
            ],
        ];

        foreach ($achievements as $ach) {
            \App\Models\Achievement::firstOrCreate(['slug' => $ach['slug']], $ach);
        }
    }
}
