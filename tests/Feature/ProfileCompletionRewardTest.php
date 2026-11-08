<?php

namespace Tests\Feature;

use App\Models\ExpCredit;
use App\Models\Level;
use App\Models\MemberSeasonStat;
use App\Models\Notification;
use App\Models\PointTransaction;
use App\Models\ProfileCompletionReward;
use App\Models\Season;
use App\Models\Skill;
use App\Models\Student;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\XpTransaction;
use App\Services\ProfileCompletionService;
use App\Services\SeasonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ProfileCompletionRewardTest extends TestCase
{
    use RefreshDatabase;

    protected Season $activeSeason;

    protected function setUp(): void
    {
        parent::setUp();

        Level::firstOrCreate(
            ['level' => 1],
            ['title' => 'Novice Adventurer', 'min_xp' => 0, 'badge_icon' => 'shield']
        );
        Level::firstOrCreate(
            ['level' => 2],
            ['title' => 'Apprentice Coder', 'min_xp' => 100, 'badge_icon' => 'code']
        );

        $this->activeSeason = Season::create([
            'name' => 'AY 2026-2027 Semester 1',
            'academic_year_label' => '2026-2027',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(4)->toDateString(),
            'is_active' => true,
            'status' => 'active',
        ]);
    }

    /**
     * Helper to create a bare 0% member account.
     */
    private function createZeroPercentMember(): User
    {
        return User::factory()->create([
            'name' => '2026-00001', // looks like a raw student ID -> invalid full name
            'username' => null,
            'email' => 'unverified.member@example.com',
            'email_verified_at' => null,
            'avatar_style' => 'adventurer',
            'avatar_seed' => null,
            'role' => 'student',
            'student_id' => null,
            'is_active' => true,
        ]);
    }

    /**
     * Helper to create a fully valid 100% member account (without triggering controller yet).
     */
    private function createCompleteMember(array $userOverrides = [], array $studentOverrides = [], array $profileOverrides = []): User
    {
        $student = Student::create(array_merge([
            'student_number' => '2026-'.rand(10000, 99999),
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'program' => 'BS Computer Science',
            'year_level' => '3rd Year',
            'block' => 'A',
            'email' => 'maria.santos@comsochub.tech',
        ], $studentOverrides));

        $user = User::factory()->create(array_merge([
            'name' => 'Maria Santos',
            'username' => 'maria_codes',
            'email' => $student->email,
            'email_verified_at' => now(),
            'avatar_style' => 'adventurer',
            'avatar_seed' => 'Felix',
            'adventurer_setup_at' => now(),
            'role' => 'student',
            'student_id' => $student->id,
            'is_active' => true,
        ], $userOverrides));

        UserProfile::create(array_merge([
            'user_id' => $user->id,
            'bio' => 'Full-stack Laravel developer and COMSOC hackathon enthusiast.',
            'interests' => ['Web Development', 'Cybersecurity'],
            'visibility' => 'public',
            'is_public' => true,
        ], $profileOverrides));

        $skill = Skill::firstOrCreate(['name' => 'Laravel']);
        $user->skills()->sync([
            $skill->id => [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'proficiency' => 'intermediate',
            ],
        ]);

        return $user->fresh(['profile', 'student', 'skills']);
    }

    /**
     * Scenario 1: Profile at 0% completion -> No reward
     */
    public function test_scenario_1_profile_at_0_percent_completion_grants_no_reward(): void
    {
        $user = $this->createZeroPercentMember();
        $service = app(ProfileCompletionService::class);

        $evaluation = $service->evaluate($user);
        $this->assertSame(0, $evaluation['percentage']);
        $this->assertFalse($evaluation['is_complete']);
        $this->assertCount(8, $evaluation['missing_fields']);

        $result = $service->evaluateAndAwardIfComplete($user, $user);

        $this->assertFalse($result['awarded']);
        $this->assertSame(0, $user->getLifetimeExp());
        $this->assertSame(0, $user->getSpendablePoints());
        $this->assertDatabaseCount('profile_completion_rewards', 0);
    }

    /**
     * Scenario 2: Profile at 50% completion -> No reward
     */
    public function test_scenario_2_profile_at_50_percent_completion_grants_no_reward(): void
    {
        $student = Student::create([
            'student_number' => '2026-50001',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'program' => 'BS Information Technology',
            'year_level' => '2nd Year',
            'block' => '1',
            'email' => 'juan.50@comsochub.tech',
        ]);

        // 4 of 8 requirements completed: name, username, avatar, academic_record
        // Missing 4: email (unverified), bio, skills, interests
        $user = User::factory()->create([
            'name' => 'Juan Dela Cruz',
            'username' => 'juan_dc',
            'email' => 'juan.50@comsochub.tech',
            'email_verified_at' => null,
            'avatar_style' => 'adventurer',
            'avatar_seed' => 'Aneka',
            'role' => 'student',
            'student_id' => $student->id,
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'bio' => '   ', // whitespace only -> invalid
            'interests' => [],
        ]);

        $service = app(ProfileCompletionService::class);
        $evaluation = $service->evaluate($user);

        $this->assertSame(50, $evaluation['percentage']);
        $this->assertFalse($evaluation['is_complete']);

        $result = $service->evaluateAndAwardIfComplete($user, $user);
        $this->assertFalse($result['awarded']);
        $this->assertSame(0, $user->getLifetimeExp());
        $this->assertSame(0, $user->getSpendablePoints());
    }

    /**
     * Scenario 3: Profile at <100% (one required field missing / 99% cap) -> No reward
     */
    public function test_scenario_3_profile_missing_single_requirement_never_rounds_to_100_and_grants_no_reward(): void
    {
        // Complete 7 of 8 fields (missing only interests)
        $user = $this->createCompleteMember(
            profileOverrides: ['interests' => []]
        );

        $service = app(ProfileCompletionService::class);
        $evaluation = $service->evaluate($user);

        $this->assertLessThan(100, $evaluation['percentage']);
        $this->assertFalse($evaluation['is_complete']);
        $this->assertCount(1, $evaluation['missing_fields']);
        $this->assertSame('interests', $evaluation['missing_fields'][0]['key']);

        $result = $service->evaluateAndAwardIfComplete($user, $user);
        $this->assertFalse($result['awarded']);
        $this->assertSame(0, $user->getLifetimeExp());
        $this->assertSame(0, $user->getSpendablePoints());
    }

    /**
     * Scenario 4: Profile reaching 100% for the first time -> +100 EXP, +50 Points
     */
    public function test_scenario_4_profile_reaching_100_percent_first_time_awards_100_exp_and_50_points(): void
    {
        $user = $this->createCompleteMember(
            profileOverrides: ['bio' => null, 'interests' => []]
        );

        // Complete profile through the actual ProfileController update route
        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Maria Santos',
            'username' => 'maria_codes',
            'email' => $user->email,
            'avatar_style' => 'adventurer',
            'avatar_seed' => 'Felix',
            'bio' => 'Passionate software engineer and COMSOC member.',
            'interests' => ['AI & Machine Learning', 'Backend Engineering'],
            'skills' => ['Laravel', 'PostgreSQL'],
            'visibility' => 'public',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('profile_completion_rewarded', function (array $flash) {
            return $flash['exp_awarded'] === 100
                && $flash['points_awarded'] === 50
                && $flash['title'] === 'Profile Completed!';
        });

        $user->refresh();
        $expectedRef = 'profile_completion:'.$user->id;

        $this->assertSame(100, $user->getLifetimeExp());
        $this->assertSame(50, $user->getSpendablePoints());

        $this->assertDatabaseHas('profile_completion_rewards', [
            'user_id' => $user->id,
            'source' => 'profile_completion',
            'reference_key' => $expectedRef,
            'exp_awarded' => 100,
            'points_awarded' => 50,
            'completion_percentage' => 100,
        ]);

        $this->assertDatabaseHas('xp_transactions', [
            'user_id' => $user->id,
            'amount' => 100,
            'lifetime_delta' => 100,
            'source_type' => 'profile_completion',
            'idempotency_key' => $expectedRef,
        ]);

        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $user->id,
            'amount' => 50,
            'transaction_type' => 'profile_completion',
            'source_type' => 'profile_completion',
            'idempotency_key' => $expectedRef,
        ]);

        // Verify in-app notification was created after commit
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'profile_completion',
            'title' => 'Profile Completed!',
        ]);
    }

    /**
     * Scenario 5: Repeated completion request -> No duplicate reward
     */
    public function test_scenario_5_repeated_completion_request_prevents_duplicate_reward(): void
    {
        $user = $this->createCompleteMember();
        $service = app(ProfileCompletionService::class);

        $first = $service->evaluateAndAwardIfComplete($user, $user);
        $second = $service->evaluateAndAwardIfComplete($user, $user);
        $third = $service->evaluateAndAwardIfComplete($user, $user);

        $this->assertTrue($first['awarded']);
        $this->assertFalse($second['awarded']);
        $this->assertSame('already_rewarded', $second['reason']);
        $this->assertFalse($third['awarded']);

        $this->assertSame(100, $user->fresh()->getLifetimeExp());
        $this->assertSame(50, $user->fresh()->getSpendablePoints());
        $this->assertSame(1, ProfileCompletionReward::where('user_id', $user->id)->count());
        $this->assertSame(1, XpTransaction::where('user_id', $user->id)->where('source_type', 'profile_completion')->count());
        $this->assertSame(1, PointTransaction::where('user_id', $user->id)->where('source_type', 'profile_completion')->count());
    }

    /**
     * Scenario 6: Subsequent profile edits -> No additional reward
     */
    public function test_scenario_6_subsequent_profile_edits_grant_no_additional_reward(): void
    {
        $user = $this->createCompleteMember();

        // First update triggers the reward
        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Maria Santos',
            'username' => 'maria_codes',
            'email' => $user->email,
            'avatar_style' => 'adventurer',
            'avatar_seed' => 'Felix',
            'bio' => 'Initial complete bio.',
            'interests' => ['Web Development'],
            'skills' => ['Laravel'],
            'visibility' => 'public',
        ])->assertSessionHas('profile_completion_rewarded');

        // Subsequent update modifies bio, skills, and avatar
        $secondResponse = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Maria Clara Santos',
            'username' => 'maria_codes',
            'email' => $user->email,
            'avatar_style' => 'adventurer',
            'avatar_seed' => 'Aneka',
            'bio' => 'Updated bio with new hackathon achievements.',
            'interests' => ['Web Development', 'Cloud Computing'],
            'skills' => ['Laravel', 'Docker'],
            'visibility' => 'public',
        ]);

        $secondResponse->assertSessionMissing('profile_completion_rewarded');

        $this->assertSame(100, $user->fresh()->getLifetimeExp());
        $this->assertSame(50, $user->fresh()->getSpendablePoints());
        $this->assertSame(1, ProfileCompletionReward::where('user_id', $user->id)->count());
    }

    /**
     * Scenario 7: Removal of a required field after reward issuance -> No clawback and no second reward upon re-completion
     */
    public function test_scenario_7_removal_of_required_field_after_issuance_preserves_reward_and_never_reissues(): void
    {
        $user = $this->createCompleteMember();
        $service = app(ProfileCompletionService::class);

        $first = $service->evaluateAndAwardIfComplete($user, $user);
        $this->assertTrue($first['awarded']);

        // Member later clears their bio (drops completion below 100%)
        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Maria Santos',
            'username' => 'maria_codes',
            'email' => $user->email,
            'avatar_style' => 'adventurer',
            'avatar_seed' => 'Felix',
            'bio' => '',
            'interests' => ['Web Development'],
            'skills' => ['Laravel'],
            'visibility' => 'public',
        ]);

        $user->refresh();
        $evalIncomplete = $service->evaluate($user);
        $this->assertLessThan(100, $evalIncomplete['percentage']);
        $this->assertTrue($evalIncomplete['reward_issued']);

        // Balances remain intact (no clawback)
        $this->assertSame(100, $user->getLifetimeExp());
        $this->assertSame(50, $user->getSpendablePoints());

        // Member re-adds bio to return to 100% -> must NOT receive a second reward
        $recompleteResponse = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Maria Santos',
            'username' => 'maria_codes',
            'email' => $user->email,
            'avatar_style' => 'adventurer',
            'avatar_seed' => 'Felix',
            'bio' => 'Restored bio!',
            'interests' => ['Web Development'],
            'skills' => ['Laravel'],
            'visibility' => 'public',
        ]);

        $recompleteResponse->assertSessionMissing('profile_completion_rewarded');
        $this->assertSame(100, $user->fresh()->getLifetimeExp());
        $this->assertSame(50, $user->fresh()->getSpendablePoints());
    }

    /**
     * Scenario 8: Unverified email -> Profile remains incomplete
     */
    public function test_scenario_8_unverified_email_keeps_profile_incomplete_and_grants_no_reward(): void
    {
        $user = $this->createCompleteMember([
            'email_verified_at' => null,
        ]);

        $service = app(ProfileCompletionService::class);
        $evaluation = $service->evaluate($user);

        $this->assertFalse($evaluation['is_complete']);
        $this->assertLessThan(100, $evaluation['percentage']);
        $this->assertContains('email', array_column($evaluation['missing_fields'], 'key'));

        $result = $service->evaluateAndAwardIfComplete($user, $user);
        $this->assertFalse($result['awarded']);
        $this->assertSame(0, $user->getLifetimeExp());
        $this->assertSame(0, $user->getSpendablePoints());
    }

    /**
     * Scenario 9: Invalid academic information -> Profile remains incomplete
     */
    public function test_scenario_9_invalid_academic_information_keeps_profile_incomplete(): void
    {
        $user = $this->createCompleteMember(
            studentOverrides: [
                'program' => '   ', // Blank program
                'year_level' => '9th Year', // Invalid year level
            ]
        );

        $service = app(ProfileCompletionService::class);
        $evaluation = $service->evaluate($user);

        $this->assertFalse($evaluation['is_complete']);
        $this->assertLessThan(100, $evaluation['percentage']);
        $this->assertContains('academic_record', array_column($evaluation['missing_fields'], 'key'));

        $result = $service->evaluateAndAwardIfComplete($user, $user);
        $this->assertFalse($result['awarded']);
        $this->assertSame(0, $user->getLifetimeExp());
        $this->assertSame(0, $user->getSpendablePoints());
    }

    /**
     * Scenario 10: Unauthorized profile modification attempt -> Rejected
     */
    public function test_scenario_10_unauthorized_profile_modification_and_admin_field_tampering_are_rejected(): void
    {
        // 10a: Member with missing academic program tries to spoof student program & year_level in profile PATCH
        $member = $this->createCompleteMember(
            studentOverrides: ['program' => '', 'year_level' => '']
        );

        $this->actingAs($member)->patch(route('profile.update'), [
            'name' => 'Maria Santos',
            'username' => 'maria_codes',
            'email' => $member->email,
            'avatar_style' => 'adventurer',
            'avatar_seed' => 'Felix',
            'bio' => 'Trying to spoof admin-managed academic fields.',
            'interests' => ['Security'],
            'skills' => ['Laravel'],
            'program' => 'BS Computer Science',
            'year_level' => '4th Year',
            'student_number' => '2026-99999',
        ]);

        $member->refresh();
        $this->assertSame('', $member->student->program);
        $this->assertSame(0, $member->getLifetimeExp());

        // 10b: Another user / actor attempting to trigger reward issuance for a different user
        $completeMember = $this->createCompleteMember([
            'username' => 'other_member',
            'email' => 'other.member@comsochub.tech',
        ], [
            'student_number' => '2026-77777',
            'email' => 'other.member@comsochub.tech',
        ]);

        $attacker = $this->createZeroPercentMember();
        $service = app(ProfileCompletionService::class);

        $unauthorizedResult = $service->evaluateAndAwardIfComplete($completeMember, $attacker);
        $this->assertFalse($unauthorizedResult['awarded']);
        $this->assertSame('unauthorized_actor', $unauthorizedResult['reason']);
        $this->assertSame(0, $completeMember->fresh()->getLifetimeExp());
    }

    /**
     * Scenario 11: Concurrent / race condition profile completion submissions -> Only one reward issued
     */
    public function test_scenario_11_concurrent_submissions_and_database_unique_constraints_allow_only_one_reward(): void
    {
        $user = $this->createCompleteMember();
        $service = app(ProfileCompletionService::class);

        $first = $service->evaluateAndAwardIfComplete($user, $user);
        $this->assertTrue($first['awarded']);

        // Attempt a direct duplicate insert into profile_completion_rewards to verify DB-level unique constraint
        $this->expectException(\Illuminate\Database\QueryException::class);
        ProfileCompletionReward::create([
            'user_id' => $user->id,
            'source' => 'profile_completion',
            'reference_key' => 'profile_completion:'.$user->id,
            'exp_awarded' => 100,
            'points_awarded' => 50,
            'completion_percentage' => 100,
            'awarded_at' => now(),
        ]);
    }

    /**
     * Scenario 12: Simulated EXP failure -> Full rollback
     */
    public function test_scenario_12_simulated_exp_failure_triggers_full_atomic_rollback(): void
    {
        $user = $this->createCompleteMember();
        $service = app(ProfileCompletionService::class);

        $service->setExpCreditHook(function () {
            throw new RuntimeException('Simulated EXP ledger service failure.');
        });

        try {
            $service->evaluateAndAwardIfComplete($user, $user);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('Simulated EXP ledger service failure.', $e->getMessage());
        }

        $user->refresh();
        $this->assertSame(0, $user->getLifetimeExp());
        $this->assertSame(0, $user->getSpendablePoints());
        $this->assertDatabaseCount('profile_completion_rewards', 0);
        $this->assertDatabaseCount('xp_transactions', 0);
        $this->assertDatabaseCount('point_transactions', 0);
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $user->id,
            'type' => 'profile_completion',
        ]);
    }

    /**
     * Scenario 13: Simulated Points failure -> Full rollback (including already-created EXP credit & transaction)
     */
    public function test_scenario_13_simulated_points_failure_rolls_back_exp_and_all_records(): void
    {
        $user = $this->createCompleteMember();
        $service = app(ProfileCompletionService::class);

        $service->setPointsCreditHook(function () {
            throw new RuntimeException('Simulated Points ledger failure after EXP credit.');
        });

        try {
            $service->evaluateAndAwardIfComplete($user, $user);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('Simulated Points ledger failure after EXP credit.', $e->getMessage());
        }

        $user->refresh();
        $this->assertSame(0, $user->getLifetimeExp());
        $this->assertSame(0, $user->getSpendableExp());
        $this->assertSame(0, $user->getSpendablePoints());
        $this->assertSame(0, ExpCredit::where('user_id', $user->id)->count());
        $this->assertSame(0, XpTransaction::where('user_id', $user->id)->count());
        $this->assertSame(0, PointTransaction::where('user_id', $user->id)->count());
        $this->assertSame(0, ProfileCompletionReward::where('user_id', $user->id)->count());
        $this->assertSame(0, Notification::where('user_id', $user->id)->where('type', 'profile_completion')->count());
    }

    /**
     * Scenario 14: Academic-year Points reset -> Lifetime EXP remains preserved while Points reset according to existing rules
     */
    public function test_scenario_14_academic_year_season_reset_preserves_lifetime_exp_and_resets_season_points(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'email_verified_at' => now(),
        ]);

        $user = $this->createCompleteMember();
        $service = app(ProfileCompletionService::class);

        $award = $service->evaluateAndAwardIfComplete($user, $user);
        $this->assertTrue($award['awarded']);
        $this->assertSame(100, $user->fresh()->getLifetimeExp());
        $this->assertSame(50, $user->fresh()->getSpendablePoints());

        $nextSeason = Season::create([
            'name' => 'AY 2027-2028 Semester 1',
            'academic_year_label' => '2027-2028',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(5)->toDateString(),
            'is_active' => false,
            'status' => 'upcoming',
        ]);

        // Execute academic-year / season reset
        $seasonService = app(SeasonService::class);
        $seasonService->executeSeasonReset(
            $this->activeSeason,
            $nextSeason,
            $admin
        );

        $user->refresh();

        // Lifetime EXP (+100 EXP) is permanently preserved!
        $this->assertSame(100, $user->getLifetimeExp());

        // Redeemable season Points reset to 0 for the new academic season
        $this->assertSame(0, $user->getSpendablePoints());

        // Even in the new season, the member cannot earn the one-time profile completion reward a second time
        $retryInNewSeason = $service->evaluateAndAwardIfComplete($user, $user);
        $this->assertFalse($retryInNewSeason['awarded']);
        $this->assertSame('already_rewarded', $retryInNewSeason['reason']);
    }

    /**
     * Verify Administrator Visibility & Filtering in RewardEconomyController
     */
    public function test_admin_can_view_and_filter_profile_completion_rewards_ledger(): void
    {
        $admin = User::factory()->create([
            'name' => 'COMSOC Super Admin',
            'role' => 'super_admin',
            'email_verified_at' => now(),
        ]);

        $rewardedMember = $this->createCompleteMember([
            'name' => 'Rewarded Adventurer',
            'username' => 'rewarded_adv',
            'email' => 'rewarded@comsochub.tech',
        ], [
            'student_number' => '2026-11111',
            'email' => 'rewarded@comsochub.tech',
        ]);
        app(ProfileCompletionService::class)->evaluateAndAwardIfComplete($rewardedMember, $rewardedMember);

        $incompleteMember = $this->createZeroPercentMember();
        $incompleteMember->update(['name' => 'Incomplete Student']);

        $responseAll = $this->actingAs($admin)->get(route('admin.economy.index'));
        $responseAll->assertOk();
        $responseAll->assertSee('Profile Completion Rewards Ledger');
        $responseAll->assertSee('Rewarded Adventurer');
        $responseAll->assertSee('profile_completion:'.$rewardedMember->id);

        $responseRewarded = $this->actingAs($admin)->get(route('admin.economy.index', ['completion_filter' => 'rewarded']));
        $responseRewarded->assertOk();
        $rewardedRows = $responseRewarded->viewData('profileCompletionRows');
        $this->assertCount(1, $rewardedRows);
        $this->assertSame($rewardedMember->id, $rewardedRows->first()['member']->id);

        $responseIncomplete = $this->actingAs($admin)->get(route('admin.economy.index', ['completion_filter' => 'incomplete']));
        $responseIncomplete->assertOk();
        $incompleteRows = $responseIncomplete->viewData('profileCompletionRows');
        $this->assertCount(1, $incompleteRows);
        $this->assertSame($incompleteMember->id, $incompleteRows->first()['member']->id);
    }
}
