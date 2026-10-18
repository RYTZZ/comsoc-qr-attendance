<?php

namespace Tests\Feature;

use App\Models\Mission;
use App\Models\MissionSubmission;
use App\Models\Season;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberMissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_view_missions_catalog(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $mission = Mission::create([
            'title' => 'Build a Portfolio Site',
            'description' => 'Create a personal website using HTML, CSS, or Tailwind.',
            'category' => 'coding',
            'xp_reward' => 200,
            'points_reward' => 50,
            'submission_type' => 'link',
            'status' => 'published',
        ]);

        $response = $this->actingAs($user)->get(route('missions.index'));
        $response->assertStatus(200);
        $response->assertSee('Build a Portfolio Site');
        $response->assertSee('200 XP');
        $response->assertDontSee('<select', false);
    }

    public function test_member_can_submit_mission_with_link(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $mission = Mission::create([
            'title' => 'Open Source Pull Request',
            'description' => 'Contribute to an approved repository.',
            'category' => 'coding',
            'xp_reward' => 300,
            'points_reward' => 80,
            'submission_type' => 'link',
            'status' => 'published',
        ]);

        $detailResponse = $this->actingAs($user)->get(route('missions.show', $mission));
        $detailResponse->assertStatus(200);

        $submitResponse = $this->actingAs($user)->post(route('missions.submit', $mission), [
            'proof_url' => 'https://github.com/comsoc/repo/pull/12',
        ]);

        $submitResponse->assertRedirect(route('missions.show', $mission));

        $this->assertDatabaseHas('mission_submissions', [
            'mission_id' => $mission->id,
            'user_id' => $user->id,
            'proof_url' => 'https://github.com/comsoc/repo/pull/12',
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_approve_submission_and_grant_rewards(): void
    {
        $season = Season::create([
            'name' => 'Season 2026-2027',
            'academic_year_label' => 'AY 2026-2027',
            'start_date' => '2026-08-01',
            'end_date' => '2027-07-31',
            'is_active' => true,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'student']);

        $mission = Mission::create([
            'title' => 'Lead Workshop Discussion',
            'description' => 'Facilitate a breakout room discussion.',
            'category' => 'volunteer',
            'xp_reward' => 120,
            'points_reward' => 40,
            'submission_type' => 'text',
            'status' => 'published',
        ]);

        $submission = MissionSubmission::create([
            'mission_id' => $mission->id,
            'user_id' => $member->id,
            'submission_text' => 'I facilitated the Git version control breakout room for 15 freshmen.',
            'status' => 'pending',
        ]);

        $approveResponse = $this->actingAs($admin)->post(route('admin.missions.approve', $submission));
        $approveResponse->assertRedirect();

        $submission->refresh();
        $this->assertEquals('approved', $submission->status);
        $this->assertEquals($admin->id, $submission->reviewed_by);

        $this->assertDatabaseHas('xp_transactions', [
            'user_id' => $member->id,
            'amount' => 120,
            'activity_type' => 'mission_completion',
        ]);

        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $member->id,
            'amount' => 40,
            'transaction_type' => 'mission_completion',
        ]);
    }

    public function test_admin_can_reject_submission_with_feedback(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'student']);

        $mission = Mission::create([
            'title' => 'UI Mockup Design',
            'description' => 'Submit Figma prototype link.',
            'category' => 'design',
            'xp_reward' => 150,
            'points_reward' => 30,
            'submission_type' => 'link',
            'status' => 'published',
            'allow_resubmission' => true,
        ]);

        $submission = MissionSubmission::create([
            'mission_id' => $mission->id,
            'user_id' => $member->id,
            'proof_url' => 'https://figma.com/file/invalid',
            'status' => 'pending',
        ]);

        $rejectResponse = $this->actingAs($admin)->post(route('admin.missions.reject', $submission), [
            'review_notes' => 'Link permissions are private. Please set access to viewable by anyone with link.',
        ]);

        $rejectResponse->assertRedirect();

        $submission->refresh();
        $this->assertEquals('rejected', $submission->status);
        $this->assertEquals('Link permissions are private. Please set access to viewable by anyone with link.', $submission->review_notes);
    }
}
