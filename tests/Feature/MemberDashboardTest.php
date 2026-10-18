<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Level;
use App\Models\Mission;
use App\Models\Season;
use App\Models\Student;
use App\Models\User;
use App\Models\XpTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_view_gamified_dashboard(): void
    {
        $season = Season::create([
            'name' => 'Season 2026-2027',
            'academic_year_label' => 'AY 2026-2027',
            'start_date' => '2026-08-01',
            'end_date' => '2027-07-31',
            'is_active' => true,
        ]);

        Level::create([
            'level' => 1,
            'title' => 'Novice Explorer',
            'min_xp' => 0,
            'badge_icon' => 'sparkles',
        ]);

        $student = Student::create([
            'student_number' => '21-00999',
            'first_name' => 'Alex',
            'last_name' => 'Rivera',
            'email' => 'alex@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Technology',
            'year_level' => '2nd Year',
            'block' => '3',
        ]);

        $user = User::create([
            'name' => 'Alex Rivera',
            'username' => 'alexrivera',
            'email' => 'alex@sorsu.edu.ph',
            'password' => bcrypt('Password123!'),
            'role' => 'student',
            'student_id' => $student->id,
            'is_active' => true,
        ]);

        XpTransaction::create([
            'user_id' => $user->id,
            'season_id' => $season->id,
            'amount' => 150,
            'activity_type' => 'workshop_attendance',
            'description' => 'Attended Web Dev Bootcamp',
        ]);

        $ay = \App\Models\AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'is_active' => true,
        ]);

        Event::create([
            'academic_year_id' => $ay->id,
            'created_by' => $user->id,
            'name' => 'Annual ComSoc Tech Fest',
            'event_date' => now()->addDays(5)->toDateString(),
            'is_published' => true,
            'location' => 'Main Auditorium',
        ]);

        Mission::create([
            'title' => 'Git Hero Quest',
            'description' => 'Create a GitHub repo with clean commit history',
            'xp_reward' => 100,
            'points_reward' => 50,
            'status' => 'published',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Welcome back, Alex!');
        $response->assertSee('Season 2026-2027');
        $response->assertSee('Novice Explorer');
        $response->assertSee('Annual ComSoc Tech Fest');
        $response->assertSee('Git Hero Quest');
        $response->assertDontSee('<select', false);
    }

    public function test_mobile_navigation_contains_community_features_and_valid_routes(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertOk();

        // Verify the 4 missing features are present in the response
        $response->assertSee(route('projects.index'), false);
        $response->assertSee(route('volunteers.index'), false);
        $response->assertSee(route('helpdesk.index'), false);
        $response->assertSee(route('directory.index'), false);

        $response->assertSee('Project Collaboration');
        $response->assertSee('Volunteer Board');
        $response->assertSee('Help Desk &amp; Disputes', false);
        $response->assertSee('Member Directory');
    }

    public function test_community_feature_pages_accessible_to_authenticated_member(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
        ]);

        $this->actingAs($user)->get(route('projects.index'))->assertOk();
        $this->actingAs($user)->get(route('volunteers.index'))->assertOk();
        $this->actingAs($user)->get(route('helpdesk.index'))->assertOk();
        $this->actingAs($user)->get(route('directory.index'))->assertOk();
    }

    public function test_unauthenticated_user_cannot_access_community_features(): void
    {
        $this->get(route('projects.index'))->assertRedirect('/login');
        $this->get(route('volunteers.index'))->assertRedirect('/login');
        $this->get(route('helpdesk.index'))->assertRedirect('/login');
        $this->get(route('directory.index'))->assertRedirect('/login');
    }
}
