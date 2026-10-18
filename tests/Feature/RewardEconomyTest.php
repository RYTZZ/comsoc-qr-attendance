<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\MemberSeasonStat;
use App\Models\PointTransaction;
use App\Models\RewardRule;
use App\Models\Season;
use App\Models\Student;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\RewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RewardEconomyTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_view_activity_ledger_and_switch_tabs(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $season = Season::create([
            'name' => 'Season 2026-2027',
            'academic_year_label' => 'AY 2026-2027',
            'start_date' => '2026-08-01',
            'end_date' => '2027-07-31',
            'is_active' => true,
        ]);

        Level::create([
            'level' => 1,
            'title' => 'Novice',
            'min_xp' => 0,
        ]);

        Level::create([
            'level' => 2,
            'title' => 'Apprentice',
            'min_xp' => 200,
        ]);

        XpTransaction::create([
            'user_id' => $user->id,
            'season_id' => $season->id,
            'amount' => 120,
            'activity_type' => 'workshop',
            'description' => 'Attended Web Workshop',
        ]);

        PointTransaction::create([
            'user_id' => $user->id,
            'season_id' => $season->id,
            'amount' => 45,
            'transaction_type' => 'workshop',
            'description' => 'Points for Web Workshop',
        ]);

        MemberSeasonStat::create([
            'user_id' => $user->id,
            'season_id' => $season->id,
            'season_xp' => 120,
            'redeemable_points' => 45,
            'current_level' => 1,
        ]);

        $responseXp = $this->actingAs($user)->get(route('ledger.index', ['tab' => 'xp']));
        $responseXp->assertStatus(200);
        $responseXp->assertSee('Attended Web Workshop');
        $responseXp->assertSee('120 XP');
        $responseXp->assertDontSee('<select', false);

        $responsePoints = $this->actingAs($user)->get(route('ledger.index', ['tab' => 'points']));
        $responsePoints->assertStatus(200);
        $responsePoints->assertSee('Points for Web Workshop');
        $responsePoints->assertSee('45 Pts');
    }

    public function test_reward_service_updates_balances_and_level_progression(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $season = Season::create([
            'name' => 'Season 2026-2027',
            'academic_year_label' => 'AY 2026-2027',
            'start_date' => '2026-08-01',
            'end_date' => '2027-07-31',
            'is_active' => true,
        ]);

        Level::create(['level' => 1, 'title' => 'Novice', 'min_xp' => 0]);
        Level::create(['level' => 2, 'title' => 'Apprentice', 'min_xp' => 100]);
        Level::create(['level' => 3, 'title' => 'Adept', 'min_xp' => 300]);

        $rewardService = app(RewardService::class);

        $res1 = $rewardService->grantReward(
            user: $user,
            activityType: 'challenge',
            xpAmount: 150,
            pointsAmount: 50,
            description: 'Completed Algorithmic Challenge',
            idempotencyKey: 'challenge_1_user_' . $user->id
        );

        $this->assertTrue($res1['awarded']);
        $this->assertEquals(2, $res1['level']);

        $dupRes = $rewardService->grantReward(
            user: $user,
            activityType: 'challenge',
            xpAmount: 150,
            pointsAmount: 50,
            description: 'Duplicate Request',
            idempotencyKey: 'challenge_1_user_' . $user->id
        );

        $this->assertFalse($dupRes['awarded']);
        $this->assertEquals('already_rewarded', $dupRes['reason']);

        $stat = MemberSeasonStat::where('user_id', $user->id)->first();
        $this->assertEquals(150, $stat->season_xp);
        $this->assertEquals(50, $stat->redeemable_points);
        $this->assertEquals(2, $stat->current_level);
    }

    public function test_points_spending_is_idempotent_and_validates_balance(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $season = Season::create([
            'name' => 'Season 2026-2027',
            'academic_year_label' => 'AY 2026-2027',
            'start_date' => '2026-08-01',
            'end_date' => '2027-07-31',
            'is_active' => true,
        ]);

        MemberSeasonStat::create([
            'user_id' => $user->id,
            'season_id' => $season->id,
            'season_xp' => 200,
            'redeemable_points' => 80,
            'current_level' => 1,
        ]);

        $rewardService = app(RewardService::class);

        $insufficient = $rewardService->spendPoints($user, 100, 'Redeem Hoodie');
        $this->assertFalse($insufficient['success']);
        $this->assertEquals(80, MemberSeasonStat::where('user_id', $user->id)->first()->redeemable_points);

        $success = $rewardService->spendPoints($user, 50, 'Redeem Lanyard');
        $this->assertTrue($success['success']);
        $this->assertEquals(30, $success['remaining_balance']);
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $user->id,
            'amount' => -50,
            'transaction_type' => 'reward_redemption',
        ]);
    }

    public function test_admin_can_adjust_member_balances_with_audit_trail(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'student']);

        $season = Season::create([
            'name' => 'Season 2026-2027',
            'academic_year_label' => 'AY 2026-2027',
            'start_date' => '2026-08-01',
            'end_date' => '2027-07-31',
            'is_active' => true,
        ]);

        Level::create(['level' => 1, 'title' => 'Novice', 'min_xp' => 0]);

        $response = $this->actingAs($admin)->post(route('admin.economy.adjust'), [
            'user_id' => $member->id,
            'xp_adjustment' => 100,
            'points_adjustment' => 40,
            'reason' => 'Honorarium for volunteer committee lead',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('xp_transactions', [
            'user_id' => $member->id,
            'amount' => 100,
            'activity_type' => 'manual_adjustment',
            'created_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $member->id,
            'amount' => 40,
            'transaction_type' => 'manual_adjustment',
            'created_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('member_season_stats', [
            'user_id' => $member->id,
            'season_xp' => 100,
            'redeemable_points' => 40,
        ]);
    }
}
