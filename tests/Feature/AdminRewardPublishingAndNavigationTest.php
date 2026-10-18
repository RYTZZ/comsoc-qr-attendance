<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\Season;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRewardPublishingAndNavigationTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $member;
    private Season $season;

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

        $this->superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'email' => 'superadmin@comsochub.tech',
        ]);

        $this->member = User::factory()->create([
            'role' => 'student',
            'email' => 'student@comsochub.tech',
        ]);
    }

    public function test_super_admin_reward_economy_route_loads_admin_layout(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.economy.index'));

        $response->assertOk();
        $response->assertSee('Reward Rules &amp; Economy Control', false);
        $response->assertSee('Computing Society');
        // Confirms it's not wrapped in member x-app-layout
        $response->assertDontSee('Switch to Executive & Officer Administration Console');
    }

    public function test_super_admin_manage_exp_and_points_route_loads_admin_layout(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.students.exp-points.index'));

        $response->assertOk();
        $response->assertSee('Manage Student EXP &amp; Points', false);
        $response->assertDontSee('Switch to Executive & Officer Administration Console');
    }

    public function test_member_cannot_access_administrative_reward_and_exp_routes(): void
    {
        $this->actingAs($this->member)
            ->get(route('admin.economy.index'))
            ->assertForbidden();

        $this->actingAs($this->member)
            ->get(route('admin.students.exp-points.index'))
            ->assertForbidden();

        $this->actingAs($this->member)
            ->get(route('admin.shop.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_create_reward_draft_and_it_is_hidden_from_members(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.shop.store'), [
                'action' => 'draft',
                'title' => 'Exclusive COMSOC Hoodie',
                'reward_type' => 'merchandise',
                'category' => 'Merchandise',
                'points_cost' => 150,
                'min_level' => 2,
                'stock' => 15,
                'redemption_limit_per_member' => 1,
                'description' => 'Official COMSOC dark blue embroidered hoodie',
            ]);

        $response->assertRedirect(route('admin.shop.index', ['tab' => 'catalog']));
        $this->assertDatabaseHas('rewards', [
            'title' => 'Exclusive COMSOC Hoodie',
            'reward_type' => 'merchandise',
            'status' => 'draft',
            'is_active' => false,
        ]);

        $reward = Reward::where('title', 'Exclusive COMSOC Hoodie')->first();

        // Member cannot see the draft in catalog
        $this->flushSession();
        $memberCatalog = $this->actingAs($this->member)->get(route('shop.index'));
        $memberCatalog->assertOk();
        $memberCatalog->assertDontSee('Exclusive COMSOC Hoodie');

        // Member cannot access direct URL to draft
        $this->actingAs($this->member)
            ->get(route('shop.show', $reward))
            ->assertNotFound();

        // Member cannot redeem draft reward
        $this->actingAs($this->member)
            ->post(route('shop.redeem', $reward))
            ->assertSessionHas('error');
    }

    public function test_super_admin_can_publish_reward_and_it_becomes_visible_to_members(): void
    {
        $reward = Reward::create([
            'title' => 'COMSOC Tech Pin & Sticker Bundle',
            'reward_type' => 'kit',
            'category' => 'Kit',
            'points_cost' => 50,
            'min_level' => 1,
            'stock' => 50,
            'status' => 'draft',
            'is_active' => false,
        ]);

        // Publish reward via admin action
        $publishResponse = $this->actingAs($this->superAdmin)
            ->post(route('admin.shop.publish', $reward));

        $publishResponse->assertRedirect();
        $reward->refresh();

        $this->assertEquals('published', $reward->status);
        $this->assertTrue($reward->is_active);
        $this->assertNotNull($reward->published_at);

        // Member now sees published reward in member catalog
        $memberCatalog = $this->actingAs($this->member)->get(route('shop.index'));
        $memberCatalog->assertOk();
        $memberCatalog->assertSee('COMSOC Tech Pin &amp; Sticker Bundle', false);
        $memberCatalog->assertSee('50 pts');
    }

    public function test_cannot_redeem_out_of_stock_reward(): void
    {
        $reward = Reward::create([
            'title' => 'Computing Society Tote Bag',
            'reward_type' => 'merchandise',
            'category' => 'Merchandise',
            'points_cost' => 30,
            'min_level' => 1,
            'stock' => 0, // Out of stock
            'status' => 'published',
            'is_active' => true,
        ]);

        // Award points to member via MemberSeasonStat
        \App\Models\MemberSeasonStat::create([
            'user_id' => $this->member->id,
            'season_id' => $this->season->id,
            'season_xp' => 100,
            'redeemable_points' => 100,
            'current_level' => 2,
        ]);

        $response = $this->actingAs($this->member)
            ->post(route('shop.redeem', $reward));

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('reward_redemptions', [
            'user_id' => $this->member->id,
            'reward_id' => $reward->id,
        ]);
    }
}
