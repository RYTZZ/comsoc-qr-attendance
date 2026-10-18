<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\ExpAllocation;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentRewardShopVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'is_eligible' => true,
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'is_eligible' => true,
        ]);
    }

    public function test_super_admin_creates_and_publishes_reward_and_it_appears_in_student_shop(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.shop.store'), [
            'title' => 'Exclusive COMSOC Hoodie',
            'category' => 'Apparel',
            'reward_type' => 'physical',
            'points_cost' => 150,
            'min_level' => 1,
            'stock' => 10,
            'description' => 'Comfortable fleece hoodie',
            'action' => 'publish',
            'status' => 'published',
        ]);

        $response->assertRedirect(route('admin.shop.index', ['tab' => 'catalog']));
        $this->assertDatabaseHas('rewards', [
            'title' => 'Exclusive COMSOC Hoodie',
            'status' => 'published',
            'is_active' => true,
            'is_archived' => false,
        ]);

        // Student opens shop
        $shopResponse = $this->actingAs($this->student)->get(route('shop.index'));
        $shopResponse->assertOk();
        $shopResponse->assertSee('Exclusive COMSOC Hoodie');
        $shopResponse->assertDontSee('No Available Rewards Found');
    }

    public function test_draft_reward_remains_hidden_from_student_shop(): void
    {
        $draft = Reward::create([
            'title' => 'Unreleased Sticker Pack',
            'category' => 'Stickers',
            'reward_type' => 'physical',
            'points_cost' => 20,
            'min_level' => 1,
            'stock' => 50,
            'status' => 'draft',
            'is_active' => false,
            'is_archived' => false,
        ]);

        $response = $this->actingAs($this->student)->get(route('shop.index'));
        $response->assertOk();
        $response->assertDontSee('Unreleased Sticker Pack');
        $response->assertSee('No Available Rewards Found');

        // Direct show endpoint access must 404
        $showResponse = $this->actingAs($this->student)->get(route('shop.show', $draft));
        $showResponse->assertNotFound();
    }

    public function test_unpublished_or_archived_reward_remains_hidden(): void
    {
        $archived = Reward::create([
            'title' => 'Old Legacy Mug',
            'category' => 'Accessories',
            'reward_type' => 'physical',
            'points_cost' => 40,
            'min_level' => 1,
            'stock' => 5,
            'status' => 'archived',
            'is_active' => false,
            'is_archived' => true,
        ]);

        $response = $this->actingAs($this->student)->get(route('shop.index'));
        $response->assertOk();
        $response->assertDontSee('Old Legacy Mug');

        $showResponse = $this->actingAs($this->student)->get(route('shop.show', $archived));
        $showResponse->assertNotFound();
    }

    public function test_published_reward_within_availability_period_appears(): void
    {
        $validReward = Reward::create([
            'title' => 'Seasonal Tech Lanyard',
            'category' => 'Accessories',
            'reward_type' => 'physical',
            'points_cost' => 50,
            'min_level' => 1,
            'stock' => 25,
            'status' => 'published',
            'is_active' => true,
            'is_archived' => false,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addWeek(),
        ]);

        $response = $this->actingAs($this->student)->get(route('shop.index'));
        $response->assertOk();
        $response->assertSee('Seasonal Tech Lanyard');
    }

    public function test_future_reward_not_yet_started_is_hidden(): void
    {
        $futureReward = Reward::create([
            'title' => 'Next Month Exclusive Backpack',
            'category' => 'Accessories',
            'reward_type' => 'physical',
            'points_cost' => 500,
            'min_level' => 1,
            'stock' => 10,
            'status' => 'published',
            'is_active' => true,
            'is_archived' => false,
            'starts_at' => now()->addDays(5),
            'ends_at' => now()->addMonth(),
        ]);

        $response = $this->actingAs($this->student)->get(route('shop.index'));
        $response->assertOk();
        $response->assertDontSee('Next Month Exclusive Backpack');

        // Direct show endpoint access must 404
        $showResponse = $this->actingAs($this->student)->get(route('shop.show', $futureReward));
        $showResponse->assertNotFound();
    }

    public function test_expired_reward_past_end_date_is_hidden(): void
    {
        $expiredReward = Reward::create([
            'title' => 'Past Year T-Shirt',
            'category' => 'Apparel',
            'reward_type' => 'physical',
            'points_cost' => 100,
            'min_level' => 1,
            'stock' => 10,
            'status' => 'published',
            'is_active' => true,
            'is_archived' => false,
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->student)->get(route('shop.index'));
        $response->assertOk();
        $response->assertDontSee('Past Year T-Shirt');

        // Direct show endpoint access must 404
        $showResponse = $this->actingAs($this->student)->get(route('shop.show', $expiredReward));
        $showResponse->assertNotFound();
    }

    public function test_out_of_stock_published_reward_is_displayed_with_out_of_stock_badge(): void
    {
        $outOfStock = Reward::create([
            'title' => 'Limited Edition Keychain',
            'category' => 'Accessories',
            'reward_type' => 'physical',
            'points_cost' => 30,
            'min_level' => 1,
            'stock' => 0,
            'status' => 'published',
            'is_active' => true,
            'is_archived' => false,
        ]);

        $response = $this->actingAs($this->student)->get(route('shop.index'));
        $response->assertOk();
        $response->assertSee('Limited Edition Keychain');
        $response->assertSee('Out of Stock');
    }

    public function test_category_filter_with_empty_or_all_or_specific_value(): void
    {
        $cap = Reward::create([
            'title' => 'Official COMSOC Cap',
            'category' => 'Caps',
            'reward_type' => 'physical',
            'points_cost' => 60,
            'min_level' => 1,
            'stock' => 15,
            'status' => 'published',
            'is_active' => true,
            'is_archived' => false,
        ]);

        $shirt = Reward::create([
            'title' => 'Official COMSOC Shirt',
            'category' => 'Apparel',
            'reward_type' => 'physical',
            'points_cost' => 120,
            'min_level' => 1,
            'stock' => 15,
            'status' => 'published',
            'is_active' => true,
            'is_archived' => false,
        ]);

        // Default / all
        $resAll = $this->actingAs($this->student)->get(route('shop.index', ['category' => 'all']));
        $resAll->assertSee('Official COMSOC Cap');
        $resAll->assertSee('Official COMSOC Shirt');

        // Empty category parameter should safely default to all without hiding items
        $resEmpty = $this->actingAs($this->student)->get(route('shop.index', ['category' => '']));
        $resEmpty->assertSee('Official COMSOC Cap');
        $resEmpty->assertSee('Official COMSOC Shirt');

        // Specific category: Caps
        $resCaps = $this->actingAs($this->student)->get(route('shop.index', ['category' => 'Caps']));
        $resCaps->assertSee('Official COMSOC Cap');
        $resCaps->assertDontSee('Official COMSOC Shirt');
    }

    public function test_admin_dashboard_published_count_matches_student_accessible_rewards(): void
    {
        Reward::create([
            'title' => 'Available Reward 1',
            'category' => 'Accessories',
            'reward_type' => 'physical',
            'points_cost' => 50,
            'min_level' => 1,
            'stock' => 10,
            'status' => 'published',
            'is_active' => true,
            'is_archived' => false,
        ]);

        Reward::create([
            'title' => 'Draft Reward 2',
            'category' => 'Accessories',
            'reward_type' => 'physical',
            'points_cost' => 50,
            'min_level' => 1,
            'stock' => 10,
            'status' => 'draft',
            'is_active' => false,
            'is_archived' => false,
        ]);

        Reward::create([
            'title' => 'Future Reward 3',
            'category' => 'Accessories',
            'reward_type' => 'physical',
            'points_cost' => 50,
            'min_level' => 1,
            'stock' => 10,
            'status' => 'published',
            'is_active' => true,
            'is_archived' => false,
            'starts_at' => now()->addDays(10),
        ]);

        $adminRes = $this->actingAs($this->superAdmin)->get(route('admin.shop.index', ['tab' => 'catalog']));
        $adminRes->assertOk();
        // published_rewards metric in view should equal Reward::published()->count() (1)
        $this->assertEquals(1, Reward::published()->count());
    }

    public function test_student_cannot_redeem_unpublished_or_draft_reward(): void
    {
        $draft = Reward::create([
            'title' => 'Hidden Prototype',
            'category' => 'Gadgets',
            'reward_type' => 'physical',
            'points_cost' => 10,
            'min_level' => 1,
            'stock' => 10,
            'status' => 'draft',
            'is_active' => false,
            'is_archived' => false,
        ]);

        app(\App\Services\ExpLedgerService::class)->creditExp(
            user: $this->student,
            amount: 100,
            sourceType: 'adjustment',
            description: 'Test EXP'
        );

        $response = $this->actingAs($this->student)->post(route('shop.redeem', $draft));
        $response->assertSessionHas('error');
        $this->assertEquals(0, RewardRedemption::count());
    }

    public function test_student_with_insufficient_spendable_exp_cannot_redeem(): void
    {
        $reward = Reward::create([
            'title' => 'Premium Jacket',
            'category' => 'Apparel',
            'reward_type' => 'physical',
            'points_cost' => 200,
            'min_level' => 1,
            'stock' => 5,
            'status' => 'published',
            'is_active' => true,
            'is_archived' => false,
        ]);

        app(\App\Services\ExpLedgerService::class)->creditExp(
            user: $this->student,
            amount: 50,
            sourceType: 'adjustment',
            description: 'Test EXP'
        );

        $response = $this->actingAs($this->student)->post(route('shop.redeem', $reward));
        $response->assertSessionHas('error');
        $this->assertEquals(50, $this->student->getSpendableExp());
        $this->assertEquals(5, $reward->fresh()->stock);
        $this->assertEquals(0, RewardRedemption::count());
    }

    public function test_successful_redemption_deducts_spendable_exp_and_decrements_stock(): void
    {
        $reward = Reward::create([
            'title' => 'COMSOC Thermal Mug',
            'category' => 'Drinkware',
            'reward_type' => 'physical',
            'points_cost' => 75,
            'min_level' => 1,
            'stock' => 8,
            'status' => 'published',
            'is_active' => true,
            'is_archived' => false,
        ]);

        app(\App\Services\ExpLedgerService::class)->creditExp(
            user: $this->student,
            amount: 100,
            sourceType: 'adjustment',
            description: 'Initial EXP'
        );

        $this->assertEquals(100, $this->student->getSpendableExp());
        $this->assertEquals(100, $this->student->getLifetimeExp());

        $response = $this->actingAs($this->student)->post(route('shop.redeem', $reward));
        $response->assertRedirect(route('shop.show', $reward));
        $response->assertSessionHas('success');

        // Verify stock decremented
        $this->assertEquals(7, $reward->fresh()->stock);

        // Verify spendable EXP deducted but lifetime EXP preserved
        $this->assertEquals(25, $this->student->getSpendableExp());
        $this->assertEquals(100, $this->student->getLifetimeExp());

        // Verify redemption recorded
        $redemption = RewardRedemption::first();
        $this->assertNotNull($redemption);
        $this->assertEquals($this->student->id, $redemption->user_id);
        $this->assertEquals($reward->id, $redemption->reward_id);
        $this->assertEquals(75, $redemption->points_spent);
        $this->assertNotNull($redemption->claim_code);
    }

    public function test_bsit_program_shirt_is_visible_to_eligible_student_with_1000_exp_and_redeems_properly(): void
    {
        $shirt = Reward::create([
            'title' => 'BSIT PROGRAM SHIRT',
            'category' => 'Apparel',
            'reward_type' => 'physical',
            'points_cost' => 450,
            'stock' => 5,
            'redemption_limit_per_member' => 1,
            'status' => 'published',
            'is_active' => true,
            'is_archived' => false,
            'min_level' => 1,
            'published_at' => now(),
        ]);

        // Student with 1,000 EXP
        app(\App\Services\ExpLedgerService::class)->creditExp(
            user: $this->student,
            amount: 1000,
            sourceType: 'event_attendance',
            description: 'Attendance EXP'
        );

        $this->assertEquals(1000, $this->student->getSpendableExp());

        // 1. Check shop index visibility
        $shopRes = $this->actingAs($this->student)->get(route('shop.index'));
        $shopRes->assertOk();
        $shopRes->assertSee('BSIT PROGRAM SHIRT');
        $shopRes->assertSee('450 EXP');
        $shopRes->assertSee('Apparel');
        $shopRes->assertSee('5 left');

        // 1b. Check student dashboard featured rewards visibility
        $dashboardRes = $this->actingAs($this->student)->get(route('dashboard'));
        $dashboardRes->assertOk();
        $dashboardRes->assertSee('BSIT PROGRAM SHIRT');
        $dashboardRes->assertSee('450 EXP');

        // 2. Check shop show page
        $showRes = $this->actingAs($this->student)->get(route('shop.show', $shirt));
        $showRes->assertOk();
        $showRes->assertSee('BSIT PROGRAM SHIRT');
        $showRes->assertSee('450 EXP');
        $showRes->assertSee('Redeem for 450 EXP');

        // 3. Ineligible member cannot redeem
        $ineligibleStudent = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'is_eligible' => false,
        ]);
        app(\App\Services\ExpLedgerService::class)->creditExp(
            user: $ineligibleStudent,
            amount: 1000,
            sourceType: 'adjustment',
            description: 'Credit'
        );
        $ineligibleRes = $this->actingAs($ineligibleStudent)->post(route('shop.redeem', $shirt));
        $ineligibleRes->assertSessionHas('error');
        $this->assertEquals(5, $shirt->fresh()->stock);

        // 4. Eligible student redeems shirt
        $redeemRes = $this->actingAs($this->student)->post(route('shop.redeem', $shirt), [
            'notes' => 'Size Large',
        ]);
        $redeemRes->assertRedirect(route('shop.show', $shirt));
        $redeemRes->assertSessionHas('success');

        // Stock decremented from 5 to 4
        $this->assertEquals(4, $shirt->fresh()->stock);

        // Spendable balance deducted from 1000 to 550
        $this->assertEquals(550, $this->student->getSpendableExp());

        // 5. Per-user redemption limit enforced (limit 1/user)
        $duplicateRes = $this->actingAs($this->student)->post(route('shop.redeem', $shirt));
        $duplicateRes->assertSessionHas('error');
        $this->assertEquals(4, $shirt->fresh()->stock);
        $this->assertEquals(550, $this->student->getSpendableExp());
    }
}
