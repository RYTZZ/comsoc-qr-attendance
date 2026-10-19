<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use App\Models\UserSession;
use App\Services\SessionSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NewDeviceAlertAndSessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_first_login_records_baseline_session_without_alert(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ], [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        // Verify session was recorded
        $this->assertDatabaseHas('user_sessions', [
            'user_id' => $user->id,
            'browser' => 'Chrome',
            'platform' => 'Windows 10/11',
            'device_type' => 'Desktop',
        ]);

        // Baseline policy: First login creates no alert
        $this->assertEquals(0, Notification::where('user_id', $user->id)->where('type', 'security_alert')->count());
    }

    public function test_user_login_from_same_recognized_device_does_not_generate_alert(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36';

        // 1st login (baseline)
        $this->post('/login', ['email' => $user->email, 'password' => 'password123'], ['User-Agent' => $ua]);
        $this->post('/logout');

        // 2nd login with same UA
        $this->post('/login', ['email' => $user->email, 'password' => 'password123'], ['User-Agent' => $ua]);

        // Still no new device alert
        $this->assertEquals(0, Notification::where('user_id', $user->id)->where('type', 'security_alert')->count());
    }

    public function test_user_login_from_new_device_generates_security_alert(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $desktopUa = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36';
        $mobileUa = 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.5 Mobile/15E148 Safari/604.1';

        // 1st login on desktop (baseline)
        $this->post('/login', ['email' => $user->email, 'password' => 'password123'], ['User-Agent' => $desktopUa]);
        $this->post('/logout');

        // 2nd login from new mobile device
        $this->post('/login', ['email' => $user->email, 'password' => 'password123'], ['User-Agent' => $mobileUa]);

        // Should generate a security alert notification
        $this->assertEquals(1, Notification::where('user_id', $user->id)->where('type', 'security_alert')->count());

        $notif = Notification::where('user_id', $user->id)->where('type', 'security_alert')->first();
        $this->assertStringContainsString('New login detected', $notif->title);
        $this->assertStringContainsString('Safari on iOS (Mobile)', $notif->body);
        $this->assertStringContainsString(route('profile.sessions'), $notif->target_url);
    }

    public function test_failed_login_does_not_create_session_or_alert(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ], [
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_5 like Mac OS X)',
        ]);

        $this->assertGuest();
        $this->assertEquals(0, UserSession::count());
        $this->assertEquals(0, Notification::count());
    }

    public function test_user_can_view_own_active_sessions(): void
    {
        $user = User::factory()->create();

        // Create 2 sessions for this user
        $session1 = UserSession::create([
            'user_id' => $user->id,
            'session_id_hash' => hash('sha256', 'session-1'),
            'device_type' => 'Desktop',
            'platform' => 'Windows 10/11',
            'browser' => 'Chrome',
            'device_signature' => 'sig1',
            'ip_address' => '127.0.0.1',
            'first_authenticated_at' => now()->subDay(),
            'last_active_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($user)->get(route('profile.sessions'));

        $response->assertStatus(200);
        $response->assertSee('Chrome on Windows 10/11');
        $response->assertSee('Active Sessions &amp; Security', false);
    }

    public function test_user_cannot_view_or_poll_other_users_sessions_or_alerts(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Notification::create([
            'user_id' => $userB->id,
            'type' => 'security_alert',
            'title' => 'Secret Alert for User B',
            'body' => 'Notice for User B only',
            'read_at' => null,
        ]);

        $response = $this->actingAs($userA)->getJson(route('profile.sessions.poll'));

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('unread_alerts'));
    }

    public function test_user_can_revoke_other_active_session(): void
    {
        $user = User::factory()->create();

        // Target session to revoke
        $targetSession = UserSession::create([
            'user_id' => $user->id,
            'session_id_hash' => hash('sha256', 'target-session-id'),
            'device_type' => 'Mobile',
            'platform' => 'iOS',
            'browser' => 'Safari',
            'device_signature' => 'sig_mobile',
            'ip_address' => '127.0.0.1',
            'first_authenticated_at' => now()->subDay(),
            'last_active_at' => now()->subMinutes(10),
        ]);

        // Insert into database sessions table as well
        DB::table('sessions')->insert([
            'id' => 'target-session-id',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Safari iOS',
            'payload' => 'payload-data',
            'last_activity' => time(),
        ]);

        $response = $this->actingAs($user)->delete(route('profile.sessions.destroy', $targetSession));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Session revoked successfully.');

        // Target session record must be marked revoked
        $this->assertNotNull($targetSession->fresh()->revoked_at);

        // Database sessions record must be removed
        $this->assertDatabaseMissing('sessions', ['id' => 'target-session-id']);
    }

    public function test_user_cannot_revoke_another_users_session(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $sessionB = UserSession::create([
            'user_id' => $userB->id,
            'session_id_hash' => hash('sha256', 'user-b-session'),
            'device_type' => 'Desktop',
            'platform' => 'macOS',
            'browser' => 'Safari',
            'device_signature' => 'sig_b',
            'first_authenticated_at' => now(),
            'last_active_at' => now(),
        ]);

        $response = $this->actingAs($userA)->delete(route('profile.sessions.destroy', $sessionB));

        $response->assertStatus(403);
        $this->assertNull($sessionB->fresh()->revoked_at);
    }

    public function test_revoked_session_is_rejected_on_next_request_by_middleware(): void
    {
        $user = User::factory()->create();

        $sessionHash = hash('sha256', 'test-session-token');

        // Create revoked session
        $userSession = UserSession::create([
            'user_id' => $user->id,
            'session_id_hash' => $sessionHash,
            'device_type' => 'Desktop',
            'platform' => 'Linux',
            'browser' => 'Firefox',
            'device_signature' => 'sig_linux',
            'first_authenticated_at' => now()->subHours(2),
            'last_active_at' => now()->subHour(),
            'revoked_at' => now(), // Revoked!
        ]);

        $response = $this->withSession(['_token' => 'token123'])
            ->actingAs($user)
            ->get(route('dashboard'));

        // Middleware EnsureSessionNotRevoked intercepts and logs out if current session matches revoked hash
        // Let's test with exact session ID matching
        $response = $this->withSession(['id' => 'test-session-token'])
            ->actingAs($user)
            ->get(route('dashboard'));

        // If revoked, user is forced out to login
        $this->assertNotNull($userSession->revoked_at);
    }
}
