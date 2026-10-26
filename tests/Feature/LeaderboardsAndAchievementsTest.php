<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\AttendanceRecord;
use App\Models\Event;
use App\Models\MemberSeasonStat;
use App\Models\Season;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AchievementService;
use App\Services\LeaderboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardsAndAchievementsTest extends TestCase
{
    use RefreshDatabase;

    protected Season $season;

    protected function setUp(): void
    {
        parent::setUp();

        $this->season = Season::create([
            'name' => 'Season 2026-2027',
            'academic_year_label' => 'AY 2026-2027',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(5),
            'is_active' => true,
        ]);
    }

    public function test_member_can_view_leaderboard_and_see_rankings(): void
    {
        $member1 = User::factory()->create(['name' => 'Alice TopRank', 'role' => 'student']);
        UserProfile::create(['user_id' => $member1->id, 'is_public' => true]);

        $member2 = User::factory()->create(['name' => 'Bob Second', 'role' => 'student']);
        UserProfile::create(['user_id' => $member2->id, 'is_public' => true]);

        MemberSeasonStat::create([
            'user_id' => $member1->id,
            'season_id' => $this->season->id,
            'season_xp' => 500,
            'redeemable_points' => 200,
            'current_level' => 3,
        ]);

        MemberSeasonStat::create([
            'user_id' => $member2->id,
            'season_id' => $this->season->id,
            'season_xp' => 300,
            'redeemable_points' => 150,
            'current_level' => 2,
        ]);

        $response = $this->actingAs($member1)->get(route('leaderboard.index'));

        $response->assertStatus(200);
        $response->assertSee('Leaderboards');
        $response->assertSee('Alice TopRank');
        $response->assertSee('500 XP');
        $response->assertSee('Bob Second');
        $response->assertSee('300 XP');
    }

    public function test_leaderboard_respects_private_profile_visibility(): void
    {
        $privateMember = User::factory()->create(['name' => 'Secret Member', 'role' => 'student']);
        UserProfile::create(['user_id' => $privateMember->id, 'is_public' => false]);

        MemberSeasonStat::create([
            'user_id' => $privateMember->id,
            'season_id' => $this->season->id,
            'season_xp' => 999,
            'redeemable_points' => 999,
            'current_level' => 5,
        ]);

        $publicViewer = User::factory()->create(['role' => 'student']);
        UserProfile::create(['user_id' => $publicViewer->id, 'is_public' => true]);

        $response = $this->actingAs($publicViewer)->get(route('leaderboard.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Secret Member');
    }

    public function test_leaderboard_service_calculates_ties_and_ranks(): void
    {
        $memberA = User::factory()->create(['role' => 'student']);
        UserProfile::create(['user_id' => $memberA->id, 'is_public' => true]);

        $memberB = User::factory()->create(['role' => 'student']);
        UserProfile::create(['user_id' => $memberB->id, 'is_public' => true]);

        $statA = MemberSeasonStat::create([
            'user_id' => $memberA->id,
            'season_id' => $this->season->id,
            'season_xp' => 400,
            'redeemable_points' => 100,
            'current_level' => 2,
        ]);
        $statA->created_at = now()->subDays(2);
        $statA->save();

        $statB = MemberSeasonStat::create([
            'user_id' => $memberB->id,
            'season_id' => $this->season->id,
            'season_xp' => 400,
            'redeemable_points' => 100,
            'current_level' => 2,
        ]);
        $statB->created_at = now();
        $statB->save();

        $service = app(LeaderboardService::class);
        $rankA = $service->getUserRank($memberA, $this->season, 'season_xp');
        $rankB = $service->getUserRank($memberB, $this->season, 'season_xp');

        $this->assertEquals(1, $rankA['rank']);
        $this->assertEquals(2, $rankB['rank']);
    }

    public function test_achievement_service_unlocks_and_prevents_duplicate(): void
    {
        $member = User::factory()->create(['role' => 'student']);

        $ach = Achievement::create([
            'slug' => 'first-event',
            'title' => 'First Step',
            'description' => 'Attended first event',
            'category' => 'Participation',
            'criteria_type' => 'event_attendance',
            'criteria_threshold' => 1,
            'xp_bonus' => 50,
            'points_bonus' => 25,
            'is_active' => true,
        ]);

        $service = app(AchievementService::class);

        $firstUnlock = $service->award($member, $ach);
        $this->assertTrue($firstUnlock);

        $secondUnlock = $service->award($member, $ach);
        $this->assertFalse($secondUnlock);

        $this->assertDatabaseCount('user_achievements', 1);
    }

    public function test_evaluating_automated_achievements_grants_rewards(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'student']);
        $ay = \App\Models\AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'is_active' => true,
        ]);

        $event = Event::create([
            'academic_year_id' => $ay->id,
            'created_by' => $admin->id,
            'name' => 'General Assembly 2026',
            'event_date' => now()->toDateString(),
            'status' => 'published',
            'event_type' => 'general_assembly',
            'operating_hours_type' => 'single_day',
            'reward_xp' => 50,
            'reward_points' => 20,
        ]);

        \App\Models\EventRegistration::create([
            'event_id' => $event->id,
            'user_id' => $member->id,
            'full_name' => $member->name,
            'email' => $member->email,
            'status' => 'approved',
        ]);

        $ach = Achievement::create([
            'slug' => 'event-starter',
            'title' => 'Event Enthusiast',
            'description' => 'Attended at least 1 official event',
            'category' => 'Participation',
            'criteria_type' => 'event_attendance',
            'criteria_threshold' => 1,
            'xp_bonus' => 100,
            'points_bonus' => 50,
            'is_active' => true,
        ]);

        $service = app(AchievementService::class);
        $unlocked = $service->evaluateAutomatedAchievements($member);

        $this->assertCount(1, $unlocked);
        $this->assertEquals($ach->id, $unlocked[0]->id);

        $stat = MemberSeasonStat::where('user_id', $member->id)->first();
        $this->assertNotNull($stat);
        $this->assertEquals(100, $stat->season_xp);
        $this->assertEquals(50, $stat->redeemable_points);
    }

    public function test_admin_can_create_and_manually_award_achievement(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'student']);

        $createResponse = $this->actingAs($admin)->post(route('admin.achievements.store'), [
            'title' => 'Hackathon Champion',
            'category' => 'Honor',
            'criteria_type' => 'custom',
            'criteria_threshold' => 1,
            'xp_bonus' => 300,
            'points_bonus' => 150,
            'icon' => 'trophy',
            'description' => 'Awarded to official hackathon winners.',
            'is_active' => 1,
        ]);

        $createResponse->assertRedirect(route('admin.achievements.index'));

        $ach = Achievement::where('title', 'Hackathon Champion')->first();
        $this->assertNotNull($ach);

        $awardResponse = $this->actingAs($admin)->post(route('admin.achievements.award', $ach), [
            'user_id' => $member->id,
        ]);

        $awardResponse->assertRedirect(route('admin.achievements.index'));
        $awardResponse->assertSessionHas('success');

        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $member->id,
            'achievement_id' => $ach->id,
        ]);
    }

    public function test_leaderboard_four_tabbed_interface_renders_and_isolates_content(): void
    {
        $member = User::factory()->create(['name' => 'Dana Leader', 'role' => 'student']);
        UserProfile::create(['user_id' => $member->id, 'is_public' => true]);

        MemberSeasonStat::create([
            'user_id' => $member->id,
            'season_id' => $this->season->id,
            'season_xp' => 750,
            'redeemable_points' => 300,
            'current_level' => 3,
        ]);

        $milestone = Achievement::create([
            'slug' => 'master-of-attendance',
            'title' => 'Master of Attendance',
            'category' => 'Milestone',
            'criteria_type' => 'event_attendance',
            'criteria_threshold' => 5,
            'xp_bonus' => 200,
            'points_bonus' => 100,
            'icon' => 'calendar-check',
            'description' => 'Attend 5 verified events.',
            'is_active' => true,
        ]);

        $badge = Achievement::create([
            'slug' => 'community-champion-badge',
            'title' => 'Community Champion Badge',
            'category' => 'Honor',
            'criteria_type' => 'custom',
            'criteria_threshold' => 1,
            'xp_bonus' => 500,
            'points_bonus' => 250,
            'icon' => 'shield',
            'description' => 'Honored for exceptional community leadership.',
            'is_active' => true,
        ]);

        // 1. Tab: Semester Leaderboard
        $resSemester = $this->actingAs($member)->get(route('leaderboard.index', ['tab' => 'semester']));
        $resSemester->assertOk();
        $resSemester->assertSee('Semester Leaderboard');
        $resSemester->assertSee('Official Semester Rankings');
        $resSemester->assertSee('Dana Leader');
        $resSemester->assertSee('750 EXP');

        // 2. Tab: Lifetime Leaderboard
        $resLifetime = $this->actingAs($member)->get(route('leaderboard.index', ['tab' => 'lifetime']));
        $resLifetime->assertOk();
        $resLifetime->assertSee('Lifetime Leaderboard');
        $resLifetime->assertSee('All-Time Lifetime Rankings');

        // 3. Tab: Milestones
        $resMilestones = $this->actingAs($member)->get(route('leaderboard.index', ['tab' => 'milestones']));
        $resMilestones->assertOk();
        $resMilestones->assertSee('Member Progression Milestones');
        $resMilestones->assertSee('Master of Attendance');
        $resMilestones->assertSee('Progress');

        // 4. Tab: Badges
        $resBadges = $this->actingAs($member)->get(route('leaderboard.index', ['tab' => 'badges']));
        $resBadges->assertOk();
        $resBadges->assertSee('Earned Badges & Honor Awards', false);
        $resBadges->assertSee('Community Champion Badge');
        $resBadges->assertSee('Honor');
    }
}
