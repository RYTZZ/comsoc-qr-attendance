<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdventurerSetupTest extends TestCase
{
    use RefreshDatabase;

    private function createStudentUser(array $attributes = []): User
    {
        $avatarSeed = $attributes['avatar_seed'] ?? 'Felix';
        unset($attributes['avatar_seed'], $attributes['avatar']);

        $user = User::factory()->create(array_merge([
            'role' => 'student',
            'is_active' => true,
            'avatar_style' => 'adventurer',
            'avatar_seed' => $avatarSeed,
        ], $attributes));

        UserProfile::create([
            'user_id' => $user->id,
            'is_public' => true,
            'visibility' => 'public',
        ]);

        $student = Student::create([
            'student_number' => '2026-' . str_pad((string) rand(1000, 9999), 4, '0', STR_PAD_LEFT),
            'first_name' => 'RealFirst',
            'last_name' => 'RealLast',
            'email' => $user->email,
            'program' => 'Bachelor of Science in Information Technology',
            'year_level' => '3rd Year',
            'block' => '2',
        ]);

        $user->student_id = $student->id;
        $user->save();

        return $user;
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/onboarding/adventurer');
        $response->assertRedirect('/login');
    }

    public function test_adventurer_setup_screen_renders_for_authenticated_user(): void
    {
        $user = $this->createStudentUser();

        $response = $this->actingAs($user)->get('/onboarding/adventurer');

        $response->assertStatus(200);
        $response->assertSee('Choose Your Adventurer');
        $response->assertSee('Create your identity in the COMSOC HUB community.');
        $response->assertSee('Enter your username');
        $response->assertSee('avatar-grid-responsive', false);
        $response->assertSee('Felix', false);
        $response->assertSee('Silas', false);
        $response->assertSee('Save and Continue');
    }

    public function test_student_can_select_avatar_and_save_unique_username(): void
    {
        $user = $this->createStudentUser();

        $response = $this->actingAs($user)->post('/onboarding/adventurer', [
            'username' => 'cyber_knight',
            'avatar_seed' => 'Silas',
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertEquals('cyber_knight', $user->username);
        $this->assertEquals('Silas', $user->avatar_seed);
        $this->assertNotNull($user->adventurer_setup_at);
        $this->assertTrue($user->hasCompletedAdventurerSetup());
    }

    public function test_duplicate_username_is_rejected_case_insensitively(): void
    {
        $this->createStudentUser([
            'username' => 'shadow_coder',
        ]);

        $newUser = $this->createStudentUser();

        $response = $this->actingAs($newUser)->post('/onboarding/adventurer', [
            'username' => 'SHADOW_CODER',
            'avatar_seed' => 'Felix',
        ]);

        $response->assertSessionHasErrors(['username']);
        $this->assertNull($newUser->refresh()->username);
    }

    public function test_reserved_usernames_are_rejected(): void
    {
        $user = $this->createStudentUser();

        $reservedList = ['admin', 'superadmin', 'comsoc', 'official', 'treasurer', 'root'];

        foreach ($reservedList as $reserved) {
            $response = $this->actingAs($user)->post('/onboarding/adventurer', [
                'username' => $reserved,
                'avatar_seed' => 'Felix',
            ]);

            $response->assertSessionHasErrors(['username']);
        }
    }

    public function test_invalid_characters_and_length_are_rejected(): void
    {
        $user = $this->createStudentUser();

        // Too short (< 3)
        $response = $this->actingAs($user)->post('/onboarding/adventurer', [
            'username' => 'ab',
            'avatar_seed' => 'Felix',
        ]);
        $response->assertSessionHasErrors(['username']);

        // Too long (> 20)
        $response = $this->actingAs($user)->post('/onboarding/adventurer', [
            'username' => 'a_very_long_username_exceeding_twenty',
            'avatar_seed' => 'Felix',
        ]);
        $response->assertSessionHasErrors(['username']);

        // Invalid characters (spaces, hyphens, symbols)
        $response = $this->actingAs($user)->post('/onboarding/adventurer', [
            'username' => 'bad-user-name!',
            'avatar_seed' => 'Felix',
        ]);
        $response->assertSessionHasErrors(['username']);
    }

    public function test_live_username_availability_endpoint(): void
    {
        $user = $this->createStudentUser();
        $this->createStudentUser(['username' => 'existing_user']);

        // Available username
        $response = $this->actingAs($user)->getJson('/onboarding/adventurer/check-username?username=brand_new_user');
        $response->assertStatus(200);
        $response->assertJson([
            'available' => true,
        ]);

        // Existing username (case-insensitive)
        $response = $this->actingAs($user)->getJson('/onboarding/adventurer/check-username?username=EXISTING_USER');
        $response->assertStatus(200);
        $response->assertJson([
            'available' => false,
        ]);

        // Reserved username
        $response = $this->actingAs($user)->getJson('/onboarding/adventurer/check-username?username=admin');
        $response->assertStatus(200);
        $response->assertJson([
            'available' => false,
        ]);
    }

    public function test_public_profile_displays_avatar_and_username_to_other_students(): void
    {
        $studentA = $this->createStudentUser([
            'username' => 'pixel_mage',
            'avatar_seed' => 'Leo',
        ]);

        $studentB = $this->createStudentUser([
            'username' => 'data_scout',
            'avatar_seed' => 'Silas',
        ]);

        $response = $this->actingAs($studentB)->get("/directory/{$studentA->id}");

        $response->assertStatus(200);
        $response->assertSee('@pixel_mage');
        $response->assertSee('Leo');
        // Confidential official identity should NOT be visible to other students
        $response->assertDontSee($studentA->student->student_number);
    }

    public function test_admin_and_owner_can_see_official_identity_on_profile(): void
    {
        $student = $this->createStudentUser([
            'username' => 'code_ninja',
        ]);

        // Profile owner viewing their own profile
        $response = $this->actingAs($student)->get("/directory/{$student->id}");
        $response->assertStatus(200);
        $response->assertSee($student->student->student_number);
        $response->assertSee($student->name);

        // Administrator viewing the student's profile
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get("/directory/{$student->id}");
        $response->assertStatus(200);
        $response->assertSee($student->student->student_number);
        $response->assertSee($student->name);
    }

    public function test_student_can_update_username_and_avatar_via_profile_settings(): void
    {
        $user = $this->createStudentUser([
            'username' => 'original_handle',
        ]);

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'username' => 'updated_handle',
            'avatar_seed' => 'Jocelyn',
        ]);

        $response->assertRedirect('/profile');
        $user->refresh();
        $this->assertEquals('updated_handle', $user->username);
        $this->assertEquals('Jocelyn', $user->avatar_seed);
    }
}
