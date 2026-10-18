<?php

namespace Tests\Feature;

use App\Models\MemberSeasonStat;
use App\Models\PointTransaction;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\Season;
use App\Models\User;
use App\Services\RewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RewardShopAndRedemptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Season::create([
            'name' => 'Season 2026-2027',
            'academic_year_label' => 'AY 2026-2027',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(5),
            'is_active' => true,
        ]);
    }

    public function test_member_can_view_reward_shop_catalog(): void
    {
        $member = User::factory()->create(['role' => 'student']);

        Reward::create([
            'title' => 'Computing Society Lanyard',
            'category' => 'Accessories',
            'description' => 'Official high quality woven lanyard.',
            'points_cost' => 30,
            'min_level' => 1,
            'stock' => 50,
            'is_active' => true,
        ]);

        $response = $this->actingAs($member)->get(route('shop.index'));

        $response->assertStatus(200);
        $response->assertSee('Reward Shop');
        $response->assertSee('Computing Society Lanyard');
        $response->assertSee('30 pts');
    }

    public function test_member_can_redeem_reward_with_sufficient_balance(): void
    {
        $member = User::factory()->create(['role' => 'student']);
        $season = Season::where('is_active', true)->first();

        MemberSeasonStat::create([
            'user_id' => $member->id,
            'season_id' => $season->id,
            'season_xp' => 100,
            'redeemable_points' => 100,
            'current_level' => 2,
        ]);

        $reward = Reward::create([
            'title' => 'COMSOC Ceramic Mug',
            'category' => 'Merchandise',
            'points_cost' => 45,
            'min_level' => 1,
            'stock' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($member)->post(route('shop.redeem', $reward), [
            'notes' => 'Pickup at ICT 101',
        ]);

        $response->assertRedirect(route('shop.show', $reward));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('reward_redemptions', [
            'user_id' => $member->id,
            'reward_id' => $reward->id,
            'points_spent' => 45,
            'status' => 'pending_collection',
            'notes' => 'Pickup at ICT 101',
        ]);

        $reward->refresh();
        $this->assertEquals(9, $reward->stock);

        $stat = MemberSeasonStat::where('user_id', $member->id)->first();
        $this->assertEquals(55, $stat->redeemable_points);

        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $member->id,
            'amount' => -45,
            'transaction_type' => 'reward_redemption',
        ]);
    }

    public function test_member_cannot_redeem_with_insufficient_points(): void
    {
        $member = User::factory()->create(['role' => 'student']);
        $season = Season::where('is_active', true)->first();

        MemberSeasonStat::create([
            'user_id' => $member->id,
            'season_id' => $season->id,
            'season_xp' => 50,
            'redeemable_points' => 10,
            'current_level' => 1,
        ]);

        $reward = Reward::create([
            'title' => 'COMSOC Hoodie',
            'category' => 'Merchandise',
            'points_cost' => 150,
            'min_level' => 1,
            'stock' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($member)->post(route('shop.redeem', $reward));

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('reward_redemptions', [
            'user_id' => $member->id,
            'reward_id' => $reward->id,
        ]);

        $reward->refresh();
        $this->assertEquals(5, $reward->stock);
    }

    public function test_member_cannot_redeem_out_of_stock_reward(): void
    {
        $member = User::factory()->create(['role' => 'student']);
        $season = Season::where('is_active', true)->first();

        MemberSeasonStat::create([
            'user_id' => $member->id,
            'season_id' => $season->id,
            'season_xp' => 200,
            'redeemable_points' => 200,
            'current_level' => 3,
        ]);

        $reward = Reward::create([
            'title' => 'Limited Sticker Pack',
            'category' => 'Accessories',
            'points_cost' => 15,
            'min_level' => 1,
            'stock' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($member)->post(route('shop.redeem', $reward));

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('reward_redemptions', [
            'user_id' => $member->id,
            'reward_id' => $reward->id,
        ]);
    }

    public function test_member_cannot_redeem_if_level_requirement_not_met(): void
    {
        $member = User::factory()->create(['role' => 'student']);
        $season = Season::where('is_active', true)->first();

        MemberSeasonStat::create([
            'user_id' => $member->id,
            'season_id' => $season->id,
            'season_xp' => 50,
            'redeemable_points' => 300,
            'current_level' => 1,
        ]);

        $reward = Reward::create([
            'title' => 'Special Hacker Jacket',
            'category' => 'Merchandise',
            'points_cost' => 100,
            'min_level' => 4,
            'stock' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($member)->post(route('shop.redeem', $reward));

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('reward_redemptions', [
            'user_id' => $member->id,
            'reward_id' => $reward->id,
        ]);
    }

    public function test_admin_can_fulfill_redemption(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'student']);

        $reward = Reward::create([
            'title' => 'COMSOC Keycap',
            'category' => 'Accessories',
            'points_cost' => 25,
            'stock' => 8,
            'is_active' => true,
        ]);

        $redemption = RewardRedemption::create([
            'user_id' => $member->id,
            'reward_id' => $reward->id,
            'points_spent' => 25,
            'item_title_snapshot' => $reward->title,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.shop.fulfill', $redemption));

        $response->assertRedirect(route('admin.shop.index', ['tab' => 'requests']));
        $redemption->refresh();
        $this->assertEquals('fulfilled', $redemption->status);
        $this->assertEquals($admin->id, $redemption->fulfilled_by);
        $this->assertNotNull($redemption->fulfilled_at);
    }

    public function test_admin_can_reject_and_refund_redemption(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'student']);
        $season = Season::where('is_active', true)->first();

        MemberSeasonStat::create([
            'user_id' => $member->id,
            'season_id' => $season->id,
            'season_xp' => 100,
            'redeemable_points' => 20,
            'current_level' => 2,
        ]);

        $reward = Reward::create([
            'title' => 'COMSOC Embroidered Cap',
            'category' => 'Merchandise',
            'points_cost' => 40,
            'stock' => 2,
            'is_active' => true,
        ]);

        $redemption = RewardRedemption::create([
            'user_id' => $member->id,
            'reward_id' => $reward->id,
            'season_id' => $season->id,
            'points_spent' => 40,
            'item_title_snapshot' => $reward->title,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.shop.reject', $redemption), [
            'rejection_reason' => 'Item defective and out of replacement stock',
        ]);

        $response->assertRedirect(route('admin.shop.index', ['tab' => 'requests']));
        $redemption->refresh();
        $this->assertEquals('rejected', $redemption->status);
        $this->assertEquals('Item defective and out of replacement stock', $redemption->rejection_reason);

        $reward->refresh();
        $this->assertEquals(3, $reward->stock);

        $stat = MemberSeasonStat::where('user_id', $member->id)->first();
        $this->assertEquals(60, $stat->redeemable_points);

        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $member->id,
            'amount' => 40,
            'transaction_type' => 'redemption_refund',
        ]);
    }
}
