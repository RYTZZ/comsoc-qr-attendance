<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Membership;
use App\Models\Skill;
use App\Models\Student;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\ProfileMetadataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpandedProfileAndPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $regularUser;
    private User $otherMember;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'name' => 'Super Administrator',
            'email' => 'superadmin@comsoc.local',
            'is_active' => true,
            'is_activated' => true,
            'is_eligible' => true,
        ]);

        $this->student = Student::create([
            'student_number' => '2024-99881-MN-0',
            'first_name' => 'Alex',
            'last_name' => 'Rivera',
            'program' => 'Bachelor of Science in Information Technology',
            'year_level' => '3rd',
            'email' => 'alex.rivera@example.com',
        ]);

        $this->regularUser = User::factory()->create([
            'role' => 'student',
            'name' => 'Alex Rivera',
            'email' => 'alex.rivera@example.com',
            'student_id' => $this->student->id,
            'student_number' => '2024-99881-MN-0',
            'is_active' => true,
            'is_activated' => true,
            'is_eligible' => true,
        ]);

        $this->otherMember = User::factory()->create([
            'role' => 'student',
            'name' => 'Jordan Doe',
            'email' => 'jordan@example.com',
            'is_active' => true,
            'is_activated' => true,
            'is_eligible' => true,
        ]);
    }

    public function test_user_can_view_profile_edit_page_with_expanded_categories(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('profile.edit'));

        $response->assertOk();
        $response->assertSee('Technical Skills');
        $response->assertSee('Interests');
        $response->assertSee('Profile Visibility');
        // Ensure student ID is not rendered to regular user on their profile settings
        $response->assertDontSee('2024-99881-MN-0');
    }

    public function test_user_can_save_multiple_skills_including_predefined_and_custom_entries(): void
    {
        $payload = [
            'name' => 'Alex Rivera Updated',
            'email' => 'alex.rivera@example.com',
            'bio' => 'Full-stack developer building cool apps.',
            'visibility' => 'public',
            'skills' => ['Python', 'Laravel', 'Docker', 'Solidity', '  Python  ', 'Docker'],
            'interests' => 'Web Development, Artificial Intelligence, Hackathons,   Web Development  ',
        ];

        $response = $this->actingAs($this->regularUser)->patch(route('profile.update'), $payload);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('status', 'profile-updated');

        $this->regularUser->refresh();
        $this->assertEquals('Alex Rivera Updated', $this->regularUser->name);

        $savedSkillNames = $this->regularUser->skills->pluck('name')->toArray();
        $this->assertContains('Python', $savedSkillNames);
        $this->assertContains('Laravel', $savedSkillNames);
        $this->assertContains('Docker', $savedSkillNames);
        $this->assertContains('Solidity', $savedSkillNames);
        // Ensure deduplication
        $this->assertCount(4, $savedSkillNames);

        $profile = $this->regularUser->profile;
        $this->assertEquals('public', $profile->visibility);
        $this->assertTrue($profile->is_public);
        $this->assertContains('Web Development', $profile->interests);
        $this->assertContains('Artificial Intelligence', $profile->interests);
        $this->assertContains('Hackathons', $profile->interests);
        // Ensure interests deduplication
        $this->assertCount(3, $profile->interests);
    }

    public function test_user_can_change_profile_visibility_to_members_only(): void
    {
        $payload = [
            'name' => 'Alex Rivera',
            'email' => 'alex.rivera@example.com',
            'visibility' => 'members_only',
            'skills' => ['Laravel'],
            'interests' => 'Web Development',
        ];

        $response = $this->actingAs($this->regularUser)->patch(route('profile.update'), $payload);
        $response->assertRedirect(route('profile.edit'));

        $this->regularUser->refresh();
        $this->assertEquals('members_only', $this->regularUser->profile->visibility);
        $this->assertFalse($this->regularUser->profile->is_public);

        // Authenticated member can view profile in directory
        $memberResponse = $this->actingAs($this->otherMember)->get(route('directory.show', $this->regularUser));
        $memberResponse->assertOk();
        $memberResponse->assertSee('Members Only');

        // Unauthenticated visitor cannot view members_only profile
        auth()->logout();
        $guestResponse = $this->get(route('directory.show', $this->regularUser));
        $guestResponse->assertRedirect(route('login'));
    }

    public function test_private_profile_is_inaccessible_to_other_members_but_accessible_to_owner_and_super_admin(): void
    {
        $payload = [
            'name' => 'Alex Rivera',
            'email' => 'alex.rivera@example.com',
            'visibility' => 'private',
            'skills' => ['Python'],
            'interests' => 'Data Science',
        ];

        $this->actingAs($this->regularUser)->patch(route('profile.update'), $payload);

        // Other regular member is rejected (404)
        $otherResponse = $this->actingAs($this->otherMember)->get(route('directory.show', $this->regularUser));
        $otherResponse->assertNotFound();

        // Profile owner can view
        $ownerResponse = $this->actingAs($this->regularUser)->get(route('directory.show', $this->regularUser));
        $ownerResponse->assertOk();
        $ownerResponse->assertSee('Private Profile');

        // Super Admin can view
        $adminResponse = $this->actingAs($this->superAdmin)->get(route('directory.show', $this->regularUser));
        $adminResponse->assertOk();
    }

    public function test_student_id_privacy_strict_enforcement(): void
    {
        // 1. Regular user accessing their own dynamic QR
        $qrResponse = $this->actingAs($this->regularUser)->get(route('member.dynamic_qr'));
        $qrResponse->assertOk();
        $qrResponse->assertDontSee($this->student->student_number);

        // 2. Regular user accessing dashboard
        $dashResponse = $this->actingAs($this->regularUser)->get(route('dashboard'));
        $dashResponse->assertOk();
        $dashResponse->assertDontSee($this->student->student_number);

        // 3. Regular user accessing public directory profile of another member
        UserProfile::updateOrCreate(
            ['user_id' => $this->regularUser->id],
            ['visibility' => 'public', 'is_public' => true]
        );
        $this->regularUser->refresh();
        $dirResponse = $this->actingAs($this->otherMember)->get(route('directory.show', $this->regularUser));
        $dirResponse->assertOk();
        $dirResponse->assertDontSee($this->student->student_number);

        // 4. Super Admin can view student ID in authorized admin views
        $superAdminStudentsResponse = $this->actingAs($this->superAdmin)->get(route('admin.students.index'));
        $superAdminStudentsResponse->assertOk();
        $superAdminStudentsResponse->assertSee($this->student->student_number);

        // 5. User model masking logic
        $this->assertNull($this->regularUser->getMaskedStudentNumber($this->regularUser));
        $this->assertNull($this->regularUser->getMaskedStudentNumber($this->otherMember));
        $this->assertEquals($this->student->student_number, $this->regularUser->getMaskedStudentNumber($this->superAdmin));
    }
}
