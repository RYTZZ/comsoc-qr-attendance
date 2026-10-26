<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingsAndPreferencesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = Student::create([
            'student_number' => '21-00888',
            'first_name' => 'Jordan',
            'last_name' => 'Cruz',
            'email' => 'jordan@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Technology',
            'year_level' => '3rd Year',
            'block' => 'A',
        ]);

        $this->user = User::create([
            'name' => 'Jordan Cruz',
            'username' => 'jordancruz',
            'email' => 'jordan@sorsu.edu.ph',
            'password' => Hash::make('CurrentPassword123!'),
            'role' => 'student',
            'student_id' => $this->student->id,
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_to_login_when_accessing_settings(): void
    {
        $response = $this->get(route('settings.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authorized_user_can_view_settings_and_preferences_page(): void
    {
        // Add an active session
        UserSession::create([
            'user_id' => $this->user->id,
            'session_id_hash' => hash('sha256', 'mock-session-id'),
            'device_type' => 'Desktop',
            'platform' => 'Windows 11',
            'browser' => 'Chrome',
            'device_signature' => hash('sha256', 'sig'),
            'ip_address' => '127.0.0.1',
            'first_authenticated_at' => now(),
            'last_active_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->get(route('settings.index'));

        $response->assertOk();
        $response->assertSee('Application Settings');
        $response->assertSee('Account Information & Preferences', false);
        $response->assertSee('Notification Preferences');
        $response->assertSee('Jordan Cruz');
        $response->assertSee('jordan@sorsu.edu.ph');
        $response->assertSee('One-Active-Device Rule Enforced');
    }

    public function test_user_can_update_profile_and_account_preferences(): void
    {
        $response = $this->actingAs($this->user)->patch(route('settings.profile.update'), [
            'name' => 'Jordan M. Cruz',
            'email' => 'jordan.updated@sorsu.edu.ph',
            'bio' => 'Interested in distributed systems and systems architecture.',
            'visibility' => 'members_only',
            'github_url' => 'https://github.com/jordancruz',
            'linkedin_url' => 'https://linkedin.com/in/jordancruz',
        ]);

        $response->assertRedirect(route('settings.index'));
        $response->assertSessionHas('status', 'Profile and account settings updated successfully.');

        $this->user->refresh();
        $this->assertEquals('Jordan M. Cruz', $this->user->name);
        $this->assertEquals('jordan.updated@sorsu.edu.ph', $this->user->email);

        $profile = $this->user->profile;
        $this->assertNotNull($profile);
        $this->assertEquals('members_only', $profile->visibility);
        $this->assertEquals('Interested in distributed systems and systems architecture.', $profile->bio);
        $this->assertEquals('https://github.com/jordancruz', $profile->github_url);
    }

    public function test_user_can_update_notification_preferences(): void
    {
        $response = $this->actingAs($this->user)->patch(route('settings.notifications.update'), [
            'notify_events' => '1',
            'notify_achievements' => '1',
            // other checkboxes unchecked
        ]);

        $response->assertRedirect(route('settings.index'));
        $response->assertSessionHas('status', 'Notification preferences updated.');

        $prefs = $this->user->fresh()->notificationPreferences;
        $this->assertNotNull($prefs);
        $this->assertTrue($prefs->notify_events);
        $this->assertTrue($prefs->notify_achievements);
        $this->assertFalse($prefs->notify_expiration_7d);
        $this->assertFalse($prefs->notify_expiration_1d);
        $this->assertFalse($prefs->notify_redemption);
    }

    public function test_user_can_update_password_with_valid_credentials(): void
    {
        $response = $this->actingAs($this->user)->put(route('settings.password.update'), [
            'current_password' => 'CurrentPassword123!',
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ]);

        $response->assertRedirect(route('settings.index'));
        $response->assertSessionHas('status', 'password-updated');

        $this->user->refresh();
        $this->assertTrue(Hash::check('NewSecurePassword123!', $this->user->password));
    }

    public function test_password_update_fails_with_invalid_current_password(): void
    {
        $response = $this->actingAs($this->user)->put(route('settings.password.update'), [
            'current_password' => 'WrongPassword!',
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ]);

        $response->assertSessionHasErrorsIn('updatePassword', ['current_password']);
        $this->user->refresh();
        $this->assertTrue(Hash::check('CurrentPassword123!', $this->user->password));
    }
}
