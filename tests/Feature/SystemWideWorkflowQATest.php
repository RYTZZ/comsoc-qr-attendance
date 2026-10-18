<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Mission;
use App\Models\MissionSubmission;
use App\Models\PointTransaction;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemWideWorkflowQATest extends TestCase
{
    use RefreshDatabase;

    private User $member;
    private User $officer;
    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->member = User::factory()->create([
            'email' => 'qa_member@test.com',
            'role' => 'student',
            'is_active' => true,
        ]);
        UserProfile::create([
            'user_id' => $this->member->id,
            'lifetime_xp' => 100,
            'season_xp' => 100,
            'current_points' => 50,
            'current_level' => 2,
            'is_public_directory' => true,
        ]);

        $this->officer = User::factory()->create([
            'email' => 'qa_officer@test.com',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->superadmin = User::factory()->create([
            'email' => 'qa_superadmin@test.com',
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    public function test_unauthenticated_user_is_redirected_to_login_on_protected_routes(): void
    {
        $protectedRoutes = [
            route('dashboard'),
            route('events.index'),
            route('missions.index'),
            route('shop.index'),
            route('leaderboard.index'),
            route('directory.index'),
            route('opportunities.index'),
            route('ledger.index'),
            route('tickets.index'),
            route('admin.dashboard'),
            route('admin.events.index'),
            route('admin.seasons.index'),
        ];

        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            $response->assertRedirect(route('login'));
        }
    }

    public function test_member_cannot_access_officer_and_admin_routes(): void
    {
        $adminRoutes = [
            route('admin.dashboard'),
            route('admin.events.index'),
            route('admin.missions.index'),
            route('admin.shop.index'),
            route('admin.seasons.index'),
            route('admin.users.index'),
            route('admin.economy.index'),
        ];

        foreach ($adminRoutes as $route) {
            $response = $this->actingAs($this->member)->get($route);
            $response->assertForbidden();
        }
    }

    public function test_member_cannot_adjust_points_or_escalate_balance(): void
    {
        $response = $this->actingAs($this->member)->post(route('admin.economy.adjust', $this->member), [
            'adjustment_type' => 'points',
            'amount' => 5000,
            'reason' => 'Illicit self adjustment',
        ]);

        $response->assertForbidden();
        $this->assertEquals(0, PointTransaction::where('user_id', $this->member->id)->count());
    }

    public function test_all_member_core_pages_render_with_accessible_elements(): void
    {
        $pages = [
            route('dashboard'),
            route('events.index'),
            route('missions.index'),
            route('shop.index'),
            route('leaderboard.index'),
            route('directory.index'),
            route('opportunities.index'),
            route('ledger.index'),
            route('tickets.index'),
            route('announcements.index'),
            route('notifications.index'),
        ];

        foreach ($pages as $url) {
            $response = $this->actingAs($this->member)->get($url);
            $response->assertOk();
            $content = $response->getContent();
            $this->assertStringNotContainsString('<select', $content);
        }
    }

    public function test_officer_can_access_operations_dashboard_but_not_superadmin_destructive_reset(): void
    {
        $response = $this->actingAs($this->officer)->get(route('admin.dashboard'));
        $response->assertOk();

        $response = $this->actingAs($this->officer)->get(route('admin.seasons.index'));
        $response->assertOk();

        $season = \App\Models\Season::create([
            'name' => 'Season 2025-2026',
            'code' => 'S25-26',
            'academic_year_label' => 'A.Y. 2025-2026',
            'start_date' => now()->subMonths(6),
            'end_date' => now()->subDay(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->officer)->post(route('admin.seasons.execute-reset', $season), [
            'confirm_name' => 'Season 2025-2026',
        ]);
        $response->assertForbidden();
    }
}
