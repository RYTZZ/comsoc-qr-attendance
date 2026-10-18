<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Mission;
use App\Models\MissionSubmission;
use App\Models\Reward;
use App\Models\RewardRule;
use App\Models\Season;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\RewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAndAntiAbuseTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_member_cannot_tamper_with_reward_rules(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $officer = User::factory()->create(['role' => 'admin']);

        $rule = RewardRule::create([
            'activity_type' => 'test_rule',
            'name' => 'Testing Rule',
            'xp_amount' => 50,
            'points_amount' => 25,
            'is_active' => true,
        ]);

        $studentAttempt = $this->actingAs($student)->put(route('admin.economy.rules.update', $rule), [
            'name' => 'Hacked Rule',
            'xp_amount' => 99999,
            'points_amount' => 99999,
        ]);
        $studentAttempt->assertForbidden();

        $officerAttempt = $this->actingAs($officer)->put(route('admin.economy.rules.update', $rule), [
            'name' => 'Officer Hacked Rule',
            'xp_amount' => 99999,
            'points_amount' => 99999,
        ]);
        $officerAttempt->assertForbidden();

        $this->assertEquals(50, $rule->fresh()->xp_amount);
    }

    public function test_officer_cannot_approve_their_own_mission_submission(): void
    {
        $officer = User::factory()->create(['role' => 'admin']);

        $mission = Mission::create([
            'title' => 'Self Review Test Mission',
            'description' => 'Officer should not self-approve.',
            'category' => 'academic',
            'xp_reward' => 200,
            'points_reward' => 100,
            'status' => 'published',
            'submission_type' => 'text',
        ]);

        $submission = MissionSubmission::create([
            'mission_id' => $mission->id,
            'user_id' => $officer->id,
            'submission_text' => 'My submission.',
            'status' => 'pending',
        ]);

        $approveAttempt = $this->actingAs($officer)->post(route('admin.missions.approve', $submission));
        $approveAttempt->assertSessionHas('error');

        $this->assertEquals('pending', $submission->fresh()->status);
    }

    public function test_officer_cannot_adjust_their_own_balance(): void
    {
        $officer = User::factory()->create(['role' => 'admin']);

        $adjustAttempt = $this->actingAs($officer)->post(route('admin.economy.adjust'), [
            'user_id' => $officer->id,
            'xp_adjustment' => 5000,
            'points_adjustment' => 5000,
            'reason' => 'Free points for myself',
        ]);

        $adjustAttempt->assertSessionHas('error');
    }

    public function test_concurrent_redemption_prevents_negative_inventory_and_points(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $season = Season::create([
            'name' => 'Season 2026',
            'code' => 'S2026',
            'academic_year_label' => 'A.Y. 2025-2026',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonth(),
            'is_active' => true,
        ]);

        $rewardService = app(RewardService::class);
        $rewardService->grantReward(
            user: $student,
            activityType: 'initial_grant',
            xpAmount: 1000,
            pointsAmount: 100,
            description: 'Seed points'
        );

        $reward = Reward::create([
            'title' => 'Limited ComSoc Sticker Set',
            'category' => 'merchandise',
            'points_cost' => 80,
            'min_level' => 1,
            'stock' => 1,
            'is_active' => true,
        ]);

        $firstRedeem = $rewardService->redeemReward($student, $reward);
        $this->assertTrue($firstRedeem['success']);
        $this->assertEquals(0, $reward->fresh()->stock);

        $secondRedeem = $rewardService->redeemReward($student, $reward);
        $this->assertFalse($secondRedeem['success']);
    }

    public function test_private_profile_is_protected_from_unauthorized_member_viewing(): void
    {
        $studentA = User::factory()->create(['role' => 'student']);
        $studentB = User::factory()->create(['role' => 'student']);

        UserProfile::create([
            'user_id' => $studentB->id,
            'bio' => 'Private member bio.',
            'is_public' => false,
        ]);

        $response = $this->actingAs($studentA)->get(route('directory.show', $studentB));
        $response->assertNotFound();
    }

    public function test_kiosk_endpoints_enforce_role_and_rate_limiting(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $kiosk = \App\Models\Kiosk::create([
            'name' => 'Main Hall Kiosk',
            'kiosk_identifier' => 'KIOSK-01',
            'location' => 'Main Hall',
            'is_active' => true,
        ]);

        $unauthorizedAccess = $this->actingAs($student)->get(route('kiosk.attendance', $kiosk));
        $unauthorizedAccess->assertForbidden();
    }
}
