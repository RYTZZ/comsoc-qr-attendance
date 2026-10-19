<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SingleActiveDeviceSessionTakeoverTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_first_login_creates_one_active_session(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertNotNull($user->active_session_hash);
        $this->assertEquals(1, UserSession::where('user_id', $user->id)->whereNull('revoked_at')->count());
    }

    public function test_second_login_from_another_device_invalidates_first_session_and_takes_over(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'password' => bcrypt('password123'),
        ]);

        // Device A logs in
        $responseA = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ], [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
        ]);
        $responseA->assertRedirect('/dashboard');

        $user->refresh();
        $firstSessionHash = $user->active_session_hash;
        $this->assertNotNull($firstSessionHash);

        // Disconnect test context to simulate independent Device B
        $this->flushSession();
        auth()->logout();

        // Device B logs in
        $responseB = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ], [
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_5 like Mac OS X) Safari/604.1',
        ]);
        $responseB->assertRedirect('/dashboard');

        $user->refresh();
        $secondSessionHash = $user->active_session_hash;

        // Device B is now the active session
        $this->assertNotEquals($firstSessionHash, $secondSessionHash);
        $this->assertEquals($secondSessionHash, $user->active_session_hash);

        // Device A's session record is revoked
        $firstSessionRecord = UserSession::where('session_id_hash', $firstSessionHash)->first();
        $this->assertNotNull($firstSessionRecord->revoked_at);

        // Only Device B is active
        $this->assertEquals(1, UserSession::where('user_id', $user->id)->whereNull('revoked_at')->count());
    }

    public function test_displaced_device_a_cannot_access_protected_routes(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'password' => bcrypt('password123'),
        ]);

        $deviceASessionId = 'device-a-session-token-12345';
        $deviceASessionHash = hash('sha256', $deviceASessionId);

        // Device B is the current active session
        $deviceBSessionId = 'device-b-session-token-67890';
        $deviceBSessionHash = hash('sha256', $deviceBSessionId);
        $user->update(['active_session_hash' => $deviceBSessionHash]);

        // Device A was marked revoked
        UserSession::create([
            'user_id' => $user->id,
            'session_id_hash' => $deviceASessionHash,
            'device_type' => 'Desktop',
            'platform' => 'Windows 10/11',
            'browser' => 'Chrome',
            'device_signature' => 'sig_a',
            'first_authenticated_at' => now()->subDay(),
            'last_active_at' => now()->subHour(),
            'revoked_at' => now(),
        ]);

        // Device A attempts to access dashboard with its old session
        $response = $this->withSession(['id' => $deviceASessionId])
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
    }

    public function test_displaced_device_a_polling_receives_401_takeover_response(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'password' => bcrypt('password123'),
        ]);

        $deviceASessionId = 'device-a-session-id';
        $deviceASessionHash = hash('sha256', $deviceASessionId);

        // Device B took over
        $user->update(['active_session_hash' => hash('sha256', 'device-b-session-id')]);

        $response = $this->withSession(['id' => $deviceASessionId])
            ->actingAs($user)
            ->getJson(route('profile.sessions.poll'));

        $response->assertStatus(401);
        $response->assertJson([
            'session_replaced' => true,
        ]);
    }

    public function test_failed_login_does_not_invalidate_existing_active_session(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'password' => bcrypt('password123'),
        ]);

        // User logs in on Device A
        $this->post('/login', ['email' => $user->email, 'password' => 'password123']);
        $user->refresh();
        $activeHash = $user->active_session_hash;
        $this->assertNotNull($activeHash);

        // Attacker attempts failed login
        $this->flushSession();
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);

        $user->refresh();
        // Active session must remain intact
        $this->assertEquals($activeHash, $user->active_session_hash);
        $this->assertEquals(1, UserSession::where('user_id', $user->id)->whereNull('revoked_at')->count());
    }

    public function test_third_login_invalidates_second_session(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'password' => bcrypt('password123'),
        ]);

        // Login 1
        $this->post('/login', ['email' => $user->email, 'password' => 'password123'], ['User-Agent' => 'Device1/1.0']);
        $user->refresh();
        $hash1 = $user->active_session_hash;

        // Login 2 (simulating independent device)
        $this->flushSession();
        auth()->logout();
        $this->post('/login', ['email' => $user->email, 'password' => 'password123'], ['User-Agent' => 'Device2/1.0']);
        $user->refresh();
        $hash2 = $user->active_session_hash;

        // Login 3 (simulating independent device)
        $this->flushSession();
        auth()->logout();
        $this->post('/login', ['email' => $user->email, 'password' => 'password123'], ['User-Agent' => 'Device3/1.0']);
        $user->refresh();
        $hash3 = $user->active_session_hash;

        $this->assertNotEquals($hash1, $hash2);
        $this->assertNotEquals($hash2, $hash3);
        $this->assertEquals($hash3, $user->active_session_hash);

        // Only Session 3 is active; Session 1 and 2 are revoked
        $this->assertEquals(1, UserSession::where('user_id', $user->id)->whereNull('revoked_at')->count());
        $this->assertNotNull(UserSession::where('session_id_hash', $hash1)->first()->revoked_at);
        $this->assertNotNull(UserSession::where('session_id_hash', $hash2)->first()->revoked_at);
    }

    public function test_logging_out_from_displaced_old_session_does_not_log_out_new_active_session(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'password' => bcrypt('password123'),
        ]);

        // Device B is the new active session
        $deviceBSessionHash = hash('sha256', 'device-b-session-token');
        $user->update(['active_session_hash' => $deviceBSessionHash]);

        // Device A logs out (after seeing blocking modal)
        $deviceASessionId = 'device-a-session-token';
        $deviceASessionHash = hash('sha256', $deviceASessionId);

        $response = $this->withSession(['id' => $deviceASessionId])
            ->actingAs($user)
            ->post(route('logout'));

        $response->assertRedirect('/');

        $user->refresh();
        // Crucial: Device B's active session must NOT have been cleared!
        $this->assertEquals($deviceBSessionHash, $user->active_session_hash);
    }

    public function test_logging_out_normally_clears_active_session_hash(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'password' => bcrypt('password123'),
        ]);

        $sessionId = 'authenticated-session-123';
        $sessionIdHash = hash('sha256', $sessionId);
        $user->update(['active_session_hash' => $sessionIdHash]);

        UserSession::create([
            'user_id' => $user->id,
            'session_id_hash' => $sessionIdHash,
            'device_type' => 'Desktop',
            'platform' => 'Windows 10/11',
            'browser' => 'Chrome',
            'device_signature' => 'sig_desktop',
            'first_authenticated_at' => now(),
            'last_active_at' => now(),
            'revoked_at' => null,
        ]);

        // Normal logout from that session
        $response = $this->withSession(['id' => $sessionId, 'current_session_hash' => $sessionIdHash])
            ->actingAs($user)
            ->post(route('logout'));

        $response->assertRedirect('/');

        $user->refresh();
        $this->assertNull($user->active_session_hash);
    }

    public function test_one_users_login_does_not_affect_another_users_active_session(): void
    {
        $userA = User::factory()->create(['password' => bcrypt('password123')]);
        $userB = User::factory()->create(['password' => bcrypt('password123')]);

        // User A logs in
        $this->post('/login', ['email' => $userA->email, 'password' => 'password123'], ['User-Agent' => 'UserA/1.0']);
        $userA->refresh();
        $hashA = $userA->active_session_hash;

        // User B logs in
        $this->flushSession();
        auth()->logout();
        $this->post('/login', ['email' => $userB->email, 'password' => 'password123'], ['User-Agent' => 'UserB/1.0']);
        $userB->refresh();
        $hashB = $userB->active_session_hash;

        // User A's session remains untouched
        $userA->refresh();
        $this->assertEquals($hashA, $userA->active_session_hash);
        $this->assertNotNull($userB->active_session_hash);
        $this->assertNotEquals($userA->active_session_hash, $userB->active_session_hash);
    }

    public function test_invalidated_admin_session_cannot_access_administrative_endpoints(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'password' => bcrypt('password123'),
        ]);

        $adminOldSessionId = 'admin-old-session';
        $adminOldHash = hash('sha256', $adminOldSessionId);

        // Admin logged in on another device
        $admin->update(['active_session_hash' => hash('sha256', 'admin-new-session')]);

        UserSession::create([
            'user_id' => $admin->id,
            'session_id_hash' => $adminOldHash,
            'device_type' => 'Desktop',
            'platform' => 'macOS',
            'browser' => 'Safari',
            'device_signature' => 'sig_admin',
            'first_authenticated_at' => now()->subDay(),
            'last_active_at' => now()->subHour(),
            'revoked_at' => now(),
        ]);

        $response = $this->withSession(['id' => $adminOldSessionId])
            ->actingAs($admin)
            ->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
    }

    public function test_invalidated_session_cannot_perform_profile_update(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'name' => 'Original Name',
            'password' => bcrypt('password123'),
        ]);

        $oldSessionId = 'displaced-student-session';
        $oldSessionHash = hash('sha256', $oldSessionId);

        // New device took over
        $user->update(['active_session_hash' => hash('sha256', 'new-device-session')]);

        UserSession::create([
            'user_id' => $user->id,
            'session_id_hash' => $oldSessionHash,
            'device_type' => 'Desktop',
            'platform' => 'Windows 10/11',
            'browser' => 'Firefox',
            'device_signature' => 'sig_firefox',
            'first_authenticated_at' => now()->subDay(),
            'last_active_at' => now()->subHour(),
            'revoked_at' => now(),
        ]);

        $response = $this->withSession(['id' => $oldSessionId])
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Hacked Name',
                'email' => $user->email,
            ]);

        $response->assertRedirect(route('login'));
        $user->refresh();
        $this->assertEquals('Original Name', $user->name);
    }
}
