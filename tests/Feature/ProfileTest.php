<?php

namespace Tests\Feature;

use App\Models\Skill;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_update_bio_only(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'bio' => 'Senior full-stack developer passionate about open source.',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $this->assertNotNull($user->profile);
        $this->assertSame('Senior full-stack developer passionate about open source.', $user->profile->bio);
    }

    public function test_update_github_profile_url_only(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'github_url' => 'https://github.com/torvalds',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $this->assertSame('https://github.com/torvalds', $user->profile->github_url);
    }

    public function test_update_linkedin_profile_url_only(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'linkedin_url' => 'https://linkedin.com/in/linustorvalds',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $this->assertSame('https://linkedin.com/in/linustorvalds', $user->profile->linkedin_url);
    }

    public function test_update_interests_and_topics_only(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'interests' => 'Cloud Computing, Distributed Systems, Laravel, Cyber Security',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $this->assertEquals(
            ['Cloud Computing', 'Distributed Systems', 'Laravel', 'Cyber Security'],
            $user->profile->interests
        );
    }

    public function test_update_technical_skills_and_stack_only(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'skills' => ['PHP', 'Laravel', 'React'],
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $skillNames = $user->skills->pluck('name')->sort()->values()->all();
        $this->assertEquals(['Laravel', 'PHP', 'React'], $skillNames);

        // Verify that the pivot table has non-null UUID ids
        $pivotRecords = \Illuminate\Support\Facades\DB::table('user_skills')
            ->where('user_id', $user->id)
            ->get();
        $this->assertCount(3, $pivotRecords);
        foreach ($pivotRecords as $record) {
            $this->assertNotNull($record->id);
            $this->assertTrue(Str::isUuid($record->id));
        }
    }

    public function test_update_all_five_fields_simultaneously(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Jane Doe',
                'email' => 'janedoe@example.com',
                'bio' => 'Software Engineer and Computer Science Student.',
                'github_url' => 'https://github.com/janedoe',
                'linkedin_url' => 'https://linkedin.com/in/janedoe',
                'interests' => 'AI, Web Development, DevOps',
                'skills' => ['Python', 'Docker', 'Linux'],
                'is_public' => true,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $this->assertSame('Jane Doe', $user->name);
        $this->assertSame('janedoe@example.com', $user->email);
        $this->assertSame('Software Engineer and Computer Science Student.', $user->profile->bio);
        $this->assertSame('https://github.com/janedoe', $user->profile->github_url);
        $this->assertSame('https://linkedin.com/in/janedoe', $user->profile->linkedin_url);
        $this->assertEquals(['AI', 'Web Development', 'DevOps'], $user->profile->interests);
        $this->assertTrue($user->profile->is_public);
        $this->assertEquals(
            ['Docker', 'Linux', 'Python'],
            $user->skills->pluck('name')->sort()->values()->all()
        );
    }

    public function test_clear_optional_fields(): void
    {
        $user = User::factory()->create();
        $profile = UserProfile::create([
            'user_id' => $user->id,
            'bio' => 'Initial Bio',
            'github_url' => 'https://github.com/init',
            'linkedin_url' => 'https://linkedin.com/in/init',
            'interests' => ['Initial', 'Topics'],
            'is_public' => true,
        ]);
        $skill = Skill::firstOrCreate(['name' => 'PHP']);
        $user->skills()->attach($skill->id, ['id' => (string) Str::uuid(), 'proficiency' => 'intermediate']);

        $this->assertCount(1, $user->fresh()->skills);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'bio' => '',
                'github_url' => '',
                'linkedin_url' => '',
                'interests' => '',
                // omitting 'skills' should clear skills
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $this->assertNull($user->profile->bio);
        $this->assertNull($user->profile->github_url);
        $this->assertNull($user->profile->linkedin_url);
        $this->assertEquals([], $user->profile->interests);
        $this->assertCount(0, $user->skills);
    }

    public function test_submit_valid_profile_data_with_boundary_length_values(): void
    {
        $user = User::factory()->create();

        $maxBio = str_repeat('a', 1000);
        $maxUrl = 'https://example.com/' . str_repeat('b', 230); // 251 chars <= 255
        $maxInterests = str_repeat('c', 500);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => str_repeat('n', 255),
                'email' => 'boundary@example.com',
                'bio' => $maxBio,
                'github_url' => $maxUrl,
                'linkedin_url' => $maxUrl,
                'interests' => $maxInterests,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $this->assertSame($maxBio, $user->profile->bio);
        $this->assertSame($maxUrl, $user->profile->github_url);
        $this->assertSame($maxUrl, $user->profile->linkedin_url);
    }

    public function test_submit_malformed_urls_and_invalid_input(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'name' => '',
                'email' => 'not-an-email',
                'github_url' => 'not-a-valid-url',
                'linkedin_url' => 'invalid-url',
                'bio' => str_repeat('x', 1001),
                'interests' => str_repeat('y', 501),
            ]);

        $response
            ->assertSessionHasErrors(['name', 'email', 'github_url', 'linkedin_url', 'bio', 'interests'])
            ->assertRedirect('/profile');
    }

    public function test_guest_cannot_update_profile(): void
    {
        $response = $this->patch('/profile', [
            'name' => 'Intruder',
            'email' => 'intruder@example.com',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_user_cannot_modify_protected_fields_such_as_role_or_is_admin(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Attempted Privilege Escalation',
                'email' => $user->email,
                'role' => 'super_admin',
                'is_admin' => 1,
                'is_active' => 0,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $this->assertSame('Attempted Privilege Escalation', $user->name);
        $this->assertSame('student', $user->role);
        $this->assertTrue($user->is_active);
    }

    public function test_unrelated_account_fields_and_other_users_remain_unchanged(): void
    {
        $otherUser = User::factory()->create([
            'name' => 'Untouched User',
            'email' => 'untouched@example.com',
        ]);
        $otherProfile = UserProfile::create([
            'user_id' => $otherUser->id,
            'bio' => 'Original Other Bio',
        ]);

        $currentUser = User::factory()->create([
            'role' => 'student',
            'student_number' => '2024-00100',
        ]);

        $response = $this
            ->actingAs($currentUser)
            ->patch('/profile', [
                'name' => 'Current User Updated',
                'email' => 'updated_current@example.com',
                'bio' => 'New Current Bio',
            ]);

        $response->assertRedirect('/profile');

        $otherUser->refresh();
        $this->assertSame('Untouched User', $otherUser->name);
        $this->assertSame('untouched@example.com', $otherUser->email);
        $this->assertSame('Original Other Bio', $otherUser->fresh()->profile->bio);

        $currentUser->refresh();
        $this->assertSame('student', $currentUser->role);
        $this->assertSame('2024-00100', $currentUser->student_number);
    }

    public function test_successful_update_persists_after_refresh_and_relogin(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => 'Persisted User',
                'email' => 'persisted@example.com',
                'bio' => 'Persisted Bio',
                'github_url' => 'https://github.com/persisted',
                'skills' => ['Laravel', 'MySQL'],
            ])
            ->assertRedirect('/profile');

        $this->flushSession();
        auth()->logout();

        $this->post('/login', [
            'login' => 'persisted@example.com',
            'password' => 'password123',
        ]);

        $user = User::where('email', 'persisted@example.com')->firstOrFail();

        $viewResponse = $this->get('/profile');
        $viewResponse->assertOk();
        $viewResponse->assertSee('Persisted User');
        $viewResponse->assertSee('Persisted Bio');
        $viewResponse->assertSee('https://github.com/persisted');
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
