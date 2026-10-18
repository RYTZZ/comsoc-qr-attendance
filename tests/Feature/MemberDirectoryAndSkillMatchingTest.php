<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Skill;
use App\Models\Student;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\MemberDirectoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberDirectoryAndSkillMatchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_search_directory_and_filter_by_skill(): void
    {
        $viewer = User::factory()->create(['role' => 'student']);

        $student = Student::create([
            'student_number' => '20260001',
            'first_name' => 'Alice',
            'last_name' => 'Developer',
            'email' => 'alice@example.com',
            'program' => 'BSIT',
            'year_level' => '3rd Year',
            'block' => '1',
        ]);

        $skillPhp = Skill::create(['name' => 'PHP & Laravel', 'category' => 'Backend']);
        $skillReact = Skill::create(['name' => 'React', 'category' => 'Frontend']);

        $alice = User::factory()->create([
            'name' => 'Alice Developer',
            'role' => 'student',
            'student_id' => $student->id,
        ]);
        UserProfile::create(['user_id' => $alice->id, 'is_public' => true, 'bio' => 'Fullstack Laravel dev']);
        $alice->skills()->attach($skillPhp->id);

        $bob = User::factory()->create(['name' => 'Bob Designer', 'role' => 'student']);
        UserProfile::create(['user_id' => $bob->id, 'is_public' => true, 'bio' => 'UI Designer']);
        $bob->skills()->attach($skillReact->id);

        $response = $this->actingAs($viewer)->get(route('directory.index'));

        $response->assertStatus(200);
        $response->assertSee('Alice Developer');
        $response->assertSee('Bob Designer');

        $filteredResponse = $this->actingAs($viewer)->get(route('directory.index', ['skill_id' => $skillPhp->id]));
        $filteredResponse->assertStatus(200);
        $filteredResponse->assertSee('Alice Developer');
        $filteredResponse->assertDontSee('Bob Designer');
    }

    public function test_private_profile_is_hidden_from_directory_and_cannot_be_viewed(): void
    {
        $viewer = User::factory()->create(['role' => 'student']);

        $privateUser = User::factory()->create(['name' => 'Invisible Member', 'role' => 'student']);
        UserProfile::create(['user_id' => $privateUser->id, 'is_public' => false]);

        $searchResponse = $this->actingAs($viewer)->get(route('directory.index'));
        $searchResponse->assertStatus(200);
        $searchResponse->assertDontSee('Invisible Member');

        $profileResponse = $this->actingAs($viewer)->get(route('directory.show', $privateUser));
        $profileResponse->assertStatus(404);
    }

    public function test_member_can_update_skills_and_collaboration_profile(): void
    {
        $member = User::factory()->create(['role' => 'student']);
        $skill1 = Skill::create(['name' => 'Python', 'category' => 'Data']);
        $skill2 = Skill::create(['name' => 'Docker', 'category' => 'DevOps']);

        $response = $this->actingAs($member)->put(route('directory.profile.update'), [
            'bio' => 'Passionate about distributed cloud computing and IoT.',
            'github_url' => 'https://github.com/mygitprofile',
            'linkedin_url' => 'https://linkedin.com/in/mylinkedin',
            'interests' => 'Cloud, IoT, DevOps',
            'tech_stack' => 'Python, Go, Docker',
            'collaboration_preferences' => 'Hackathons, Remote',
            'project_categories' => ['Mobile Apps', 'Cybersecurity & Networking'],
            'skill_ids' => [$skill1->id, $skill2->id],
            'is_public' => 1,
            'open_to_collaborate' => 1,
        ]);

        $response->assertRedirect(route('directory.profile.edit'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $member->id,
            'bio' => 'Passionate about distributed cloud computing and IoT.',
            'github_url' => 'https://github.com/mygitprofile',
            'is_public' => true,
            'open_to_collaborate' => true,
        ]);

        $this->assertDatabaseHas('user_skills', [
            'user_id' => $member->id,
            'skill_id' => $skill1->id,
        ]);
        $this->assertDatabaseHas('user_skills', [
            'user_id' => $member->id,
            'skill_id' => $skill2->id,
        ]);
    }

    public function test_collaborator_matching_recommends_peers_with_transparent_reasons(): void
    {
        $userA = User::factory()->create(['role' => 'student']);
        $skillShared = Skill::create(['name' => 'TypeScript', 'category' => 'Web']);
        $skillOther = Skill::create(['name' => 'Rust', 'category' => 'Systems']);

        UserProfile::create([
            'user_id' => $userA->id,
            'is_public' => true,
            'open_to_collaborate' => true,
            'interests' => ['Open Source', 'Compilers'],
            'project_categories' => ['Web Development'],
            'collaboration_preferences' => ['Pair Programming'],
        ]);
        $userA->skills()->attach($skillShared->id);

        $userB = User::factory()->create(['name' => 'Teammate Candidate', 'role' => 'student']);
        UserProfile::create([
            'user_id' => $userB->id,
            'is_public' => true,
            'open_to_collaborate' => true,
            'interests' => ['Open Source', 'Robotics'],
            'project_categories' => ['Web Development'],
            'collaboration_preferences' => ['Pair Programming'],
        ]);
        $userB->skills()->attach([$skillShared->id, $skillOther->id]);

        $service = app(MemberDirectoryService::class);
        $matches = $service->findCollaboratorMatches($userA);

        $this->assertNotEmpty($matches);
        $topMatch = $matches->first();

        $this->assertEquals($userB->id, $topMatch['user']->id);
        $this->assertGreaterThan(0, $topMatch['score']);
        $this->assertNotEmpty($topMatch['match_reasons']);

        $matchesResponse = $this->actingAs($userA)->get(route('directory.index', ['tab' => 'matches']));
        $matchesResponse->assertStatus(200);
        $matchesResponse->assertSee('Teammate Candidate');
        $matchesResponse->assertSee('Why Recommended:');
    }
}
