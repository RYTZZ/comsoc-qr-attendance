<?php

namespace Tests\Feature;

use App\Models\Level;
use App\Models\User;
use App\Models\XpTransaction;
use Database\Seeders\GamificationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LevelBadgesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(GamificationSeeder::class);
    }

    public function test_all_20_levels_and_thresholds_match_specification(): void
    {
        $collection = Level::getCollection();
        $this->assertCount(20, $collection);

        $expected = [
            1 => ['title' => 'Code Initiate', 'min_xp' => 0, 'badge_name' => 'First Commit', 'icon' => 'code', 'tier' => 'Bronze'],
            2 => ['title' => 'Logic Apprentice', 'min_xp' => 100, 'badge_name' => 'Logic Seeker', 'icon' => 'brain', 'tier' => 'Bronze'],
            3 => ['title' => 'Code Vanguard', 'min_xp' => 250, 'badge_name' => 'Vanguard', 'icon' => 'shield-check', 'tier' => 'Bronze'],
            4 => ['title' => 'Byte Explorer', 'min_xp' => 450, 'badge_name' => 'Byte Explorer', 'icon' => 'compass', 'tier' => 'Silver'],
            5 => ['title' => 'Syntax Specialist', 'min_xp' => 700, 'badge_name' => 'Syntax Master', 'icon' => 'terminal', 'tier' => 'Silver'],
            6 => ['title' => 'Digital Engineer', 'min_xp' => 1000, 'badge_name' => 'Digital Builder', 'icon' => 'cpu', 'tier' => 'Silver'],
            7 => ['title' => 'Algorithm Strategist', 'min_xp' => 1400, 'badge_name' => 'Algorithm Ace', 'icon' => 'binary', 'tier' => 'Blue'],
            8 => ['title' => 'Systems Innovator', 'min_xp' => 1900, 'badge_name' => 'System Architect', 'icon' => 'network', 'tier' => 'Blue'],
            9 => ['title' => 'Cybernetic Expert', 'min_xp' => 2500, 'badge_name' => 'Cyber Expert', 'icon' => 'fingerprint', 'tier' => 'Blue'],
            10 => ['title' => 'Code Legend', 'min_xp' => 3200, 'badge_name' => 'Code Legend', 'icon' => 'trophy', 'tier' => 'Purple'],
            11 => ['title' => 'Tech Virtuoso', 'min_xp' => 4000, 'badge_name' => 'Tech Virtuoso', 'icon' => 'gem', 'tier' => 'Purple'],
            12 => ['title' => 'Innovation Architect', 'min_xp' => 5000, 'badge_name' => 'Innovation Master', 'icon' => 'lightbulb', 'tier' => 'Purple'],
            13 => ['title' => 'Digital Pioneer', 'min_xp' => 6200, 'badge_name' => 'Digital Pioneer', 'icon' => 'rocket', 'tier' => 'Gold'],
            14 => ['title' => 'Quantum Vanguard', 'min_xp' => 7600, 'badge_name' => 'Quantum Vanguard', 'icon' => 'orbit', 'tier' => 'Gold'],
            15 => ['title' => 'Grandmaster Developer', 'min_xp' => 9200, 'badge_name' => 'Grandmaster', 'icon' => 'crown', 'tier' => 'Gold'],
            16 => ['title' => 'Cyber Sovereign', 'min_xp' => 11000, 'badge_name' => 'Cyber Sovereign', 'icon' => 'shield', 'tier' => 'Legendary'],
            17 => ['title' => 'Legendary Architect', 'min_xp' => 13000, 'badge_name' => 'Legendary Architect', 'icon' => 'medal', 'tier' => 'Legendary'],
            18 => ['title' => 'Digital Overlord', 'min_xp' => 15500, 'badge_name' => 'Digital Overlord', 'icon' => 'zap', 'tier' => 'Legendary'],
            19 => ['title' => 'Technology Titan', 'min_xp' => 18500, 'badge_name' => 'Technology Titan', 'icon' => 'flame', 'tier' => 'Elite'],
            20 => ['title' => 'Computing Legend', 'min_xp' => 22000, 'badge_name' => 'Computing Legend', 'icon' => 'award', 'tier' => 'Elite'],
        ];

        foreach ($expected as $lvl => $spec) {
            $item = $collection[$lvl];
            $this->assertSame($spec['title'], $item['title']);
            $this->assertSame($spec['min_xp'], $item['min_xp']);
            $this->assertSame($spec['badge_name'], $item['badge_name']);
            $this->assertSame($spec['icon'], $item['badge_icon']);
            $this->assertSame($spec['tier'], $item['tier']);
        }
    }

    public function test_exp_threshold_scenario_resolution(): void
    {
        $scenarios = [
            0     => [1, 'Code Initiate'],
            100   => [2, 'Logic Apprentice'],
            250   => [3, 'Code Vanguard'],
            700   => [5, 'Syntax Specialist'],
            999   => [5, 'Syntax Specialist'],
            1000  => [6, 'Digital Engineer'],
            1100  => [6, 'Digital Engineer'],
            1399  => [6, 'Digital Engineer'],
            1400  => [7, 'Algorithm Strategist'],
            3200  => [10, 'Code Legend'],
            9200  => [15, 'Grandmaster Developer'],
            22000 => [20, 'Computing Legend'],
            50000 => [20, 'Computing Legend'],
        ];

        foreach ($scenarios as $xp => [$expectedLevel, $expectedTitle]) {
            $level = Level::forXp($xp);
            $this->assertSame($expectedLevel, $level->level, "EXP {$xp} expected level {$expectedLevel}");
            $this->assertSame($expectedTitle, $level->title, "EXP {$xp} expected title {$expectedTitle}");
        }
    }

    public function test_member_profile_and_dashboard_display_current_badge_and_collection(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
        ]);

        // Award 1,100 EXP (Level 6: Digital Engineer)
        XpTransaction::create([
            'user_id' => $user->id,
            'amount' => 1100,
            'lifetime_delta' => 1100,
            'activity_type' => 'manual_distribution',
            'description' => 'Test Award',
        ]);

        $this->assertSame(1100, $user->getLifetimeExp());

        // Test Profile integration
        $profileResponse = $this->actingAs($user)->get('/profile');
        $profileResponse->assertOk();
        $profileResponse->assertSee('Digital Engineer');
        $profileResponse->assertSee('Lvl 6');
        $profileResponse->assertSee('Level Badges');
        $profileResponse->assertSee('First Commit');
        $profileResponse->assertSee('Digital Builder');
        $profileResponse->assertSee('Algorithm Ace');

        // Test Dashboard integration
        $dashboardResponse = $this->actingAs($user)->get('/dashboard');
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('Lvl 6');
        $dashboardResponse->assertSee('Digital Engineer');
    }

    public function test_leaderboard_displays_member_level_badge_and_rank(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'username' => 'testbadgeuser',
        ]);

        XpTransaction::create([
            'user_id' => $user->id,
            'amount' => 1400,
            'lifetime_delta' => 1400,
            'activity_type' => 'event_attendance',
            'description' => 'Attendance Award',
        ]);

        $response = $this->actingAs($user)->get(route('leaderboard.index', ['tab' => 'lifetime']));
        $response->assertOk();
        $response->assertSee('testbadgeuser');
        $response->assertSee('Algorithm Strategist');
        $response->assertSee('Lvl 7');
    }
}
