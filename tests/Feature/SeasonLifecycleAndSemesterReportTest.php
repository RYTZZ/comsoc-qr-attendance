<?php

namespace Tests\Feature;

use App\Models\MemberSeasonStat;
use App\Models\Season;
use App\Models\SeasonLeaderboardSnapshot;
use App\Models\SeasonReset;
use App\Models\User;
use App\Services\SeasonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeasonLifecycleAndSemesterReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_preview_and_execute_idempotent_season_reset(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
        ]);

        $seasonA = Season::create([
            'name' => 'Season 2025-2026',
            'code' => 'S25-26',
            'academic_year_label' => 'A.Y. 2025-2026',
            'start_date' => now()->subMonths(6),
            'end_date' => now()->subDay(),
            'is_active' => true,
            'status' => 'active',
        ]);

        $seasonB = Season::create([
            'name' => 'Season 2026-2027',
            'code' => 'S26-27',
            'academic_year_label' => 'A.Y. 2026-2027',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'is_active' => false,
            'status' => 'scheduled',
        ]);

        $studentA = User::factory()->create(['role' => 'student']);
        $studentB = User::factory()->create(['role' => 'student']);

        MemberSeasonStat::create([
            'user_id' => $studentA->id,
            'season_id' => $seasonA->id,
            'season_xp' => 1200,
            'redeemable_points' => 300,
            'current_level' => 4,
        ]);

        MemberSeasonStat::create([
            'user_id' => $studentB->id,
            'season_id' => $seasonA->id,
            'season_xp' => 800,
            'redeemable_points' => 150,
            'current_level' => 3,
        ]);

        $previewResponse = $this->actingAs($superAdmin)->get(route('admin.seasons.preview-reset', $seasonA));
        $previewResponse->assertOk();
        $previewResponse->assertSee('Season 2025-2026');
        $previewResponse->assertSee('450');

        $executeResponse = $this->actingAs($superAdmin)->post(route('admin.seasons.execute-reset', $seasonA), [
            'confirm_name' => 'Season 2025-2026',
            'next_season_id' => $seasonB->id,
        ]);
        $executeResponse->assertRedirect(route('admin.seasons.index'));

        $this->assertFalse($seasonA->fresh()->is_active);
        $this->assertTrue($seasonB->fresh()->is_active);

        $this->assertDatabaseHas('season_leaderboard_snapshots', [
            'season_id' => $seasonA->id,
            'user_id' => $studentA->id,
            'final_rank' => 1,
            'final_xp' => 1200,
        ]);

        $this->assertDatabaseHas('season_leaderboard_snapshots', [
            'season_id' => $seasonA->id,
            'user_id' => $studentB->id,
            'final_rank' => 2,
            'final_xp' => 800,
        ]);

        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $studentA->id,
            'season_id' => $seasonA->id,
            'amount' => -300,
            'transaction_type' => 'season_reset_expiration',
        ]);

        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $studentB->id,
            'season_id' => $seasonA->id,
            'amount' => -150,
            'transaction_type' => 'season_reset_expiration',
        ]);

        $repeatAttempt = app(SeasonService::class)->executeSeasonReset($seasonA, $seasonB, $superAdmin);
        $this->assertEquals(1, SeasonReset::where('closing_season_id', $seasonA->id)->count());
    }

    public function test_officer_cannot_execute_season_reset_without_superadmin_role(): void
    {
        $officer = User::factory()->create(['role' => 'admin']);

        $season = Season::create([
            'name' => 'Season 2025-2026',
            'code' => 'S25-26',
            'academic_year_label' => 'A.Y. 2025-2026',
            'start_date' => now()->subMonths(6),
            'end_date' => now()->subDay(),
            'is_active' => true,
        ]);

        $previewResponse = $this->actingAs($officer)->get(route('admin.seasons.preview-reset', $season));
        $previewResponse->assertForbidden();

        $executeResponse = $this->actingAs($officer)->post(route('admin.seasons.execute-reset', $season), [
            'confirm_name' => 'Season 2025-2026',
        ]);
        $executeResponse->assertForbidden();
    }

    public function test_officer_can_view_semester_report_without_resetting_balances(): void
    {
        $officer = User::factory()->create(['role' => 'admin']);

        $season = Season::create([
            'name' => 'First Semester 2025-2026',
            'code' => 'SEM1-25',
            'academic_year_label' => 'A.Y. 2025-2026',
            'start_date' => now()->subMonths(4),
            'end_date' => now()->addMonths(2),
            'is_active' => true,
        ]);

        $response = $this->actingAs($officer)->get(route('admin.seasons.report', ['season_id' => $season->id]));

        $response->assertOk();
        $response->assertSee('Academic Semester');
        $response->assertSee('First Semester 2025-2026');
        $this->assertTrue($season->fresh()->is_active);
    }
}
