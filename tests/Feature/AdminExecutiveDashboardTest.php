<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\Event;
use App\Models\Mission;
use App\Models\MissionSubmission;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\Student;
use App\Models\User;
use App\Models\XpTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminExecutiveDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_officer_can_access_admin_dashboard_with_operational_metrics(): void
    {
        $officer = User::factory()->create([
            'role' => 'admin',
        ]);

        $ay = AcademicYear::create([
            'year_start' => 2025,
            'year_end' => 2026,
            'label' => 'A.Y. 2025-2026',
            'is_active' => true,
        ]);

        $student = Student::create([
            'student_number' => '2025-00001-BN-0',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'maria@university.edu.ph',
            'program' => 'BSIT',
            'year_level' => '2nd Year',
        ]);

        $event = Event::create([
            'academic_year_id' => $ay->id,
            'name' => 'Annual Computing Assembly',
            'event_date' => now()->addDays(5),
            'status' => 'published',
            'is_published' => true,
            'created_by' => $officer->id,
        ]);

        $mission = Mission::create([
            'title' => 'Code Review Challenge',
            'description' => 'Review open source PRs.',
            'category' => 'technical',
            'xp_reward' => 150,
            'points_reward' => 75,
            'status' => 'published',
            'submission_type' => 'link',
        ]);

        MissionSubmission::create([
            'mission_id' => $mission->id,
            'user_id' => $officer->id,
            'proof_url' => 'https://github.com/pull/1',
            'status' => 'pending',
        ]);

        $reward = Reward::create([
            'title' => 'Computing Society Lanyard',
            'category' => 'merchandise',
            'points_cost' => 50,
            'min_level' => 1,
            'stock' => 10,
            'is_active' => true,
        ]);

        RewardRedemption::create([
            'user_id' => $officer->id,
            'reward_id' => $reward->id,
            'points_spent' => 50,
            'item_title_snapshot' => $reward->title,
            'claim_code' => 'CLAIM-12345',
            'status' => 'pending',
        ]);

        XpTransaction::create([
            'user_id' => $officer->id,
            'amount' => 500,
            'activity_type' => 'event_attendance',
            'description' => 'Attendance XP',
        ]);

        $response = $this->actingAs($officer)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Enrolled Students');
        $response->assertSee('Pending Missions');
        $response->assertSee('Pending Claims');
        $response->assertSee('XP Distributed');
        $response->assertSee('Annual Computing Assembly');
        $response->assertSee('500');
    }

    public function test_student_cannot_access_officer_dashboard(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
        ]);

        $response = $this->actingAs($student)->get(route('admin.dashboard'));
        $response->assertForbidden();
    }
}
