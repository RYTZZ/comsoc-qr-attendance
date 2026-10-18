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
    }
}
