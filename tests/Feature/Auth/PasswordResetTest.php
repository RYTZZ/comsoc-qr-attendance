<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            $this->assertTrue(auth()->validate([
                'email' => $user->email,
                'password' => 'new-password-123',
            ]));

            $this->assertFalse(auth()->validate([
                'email' => $user->email,
                'password' => 'password',
            ]));

            return true;
        });
    }

    public function test_reset_password_request_does_not_reveal_unregistered_email(): void
    {
        Notification::fake();

        $response = $this->post('/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertSessionHas('status');
        $response->assertSessionHasNoErrors();
        Notification::assertNothingSent();
    }

    public function test_password_cannot_be_reset_with_invalid_token(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertFalse(auth()->validate([
            'email' => $user->email,
            'password' => 'new-password-123',
        ]));
    }

    public function test_password_cannot_be_reset_with_mismatched_confirmation(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-password-123',
                'password_confirmation' => 'different-password',
            ]);

            $response->assertSessionHasErrors(['password']);

            return true;
        });
    }

    public function test_password_reset_token_is_persisted_in_database(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', ['email' => $user->email]);

        $response->assertSessionHas('status');
        $response->assertSessionHasNoErrors();

        // Verify token persistence in password_reset_tokens table
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);

        $record = \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->first();

        $this->assertNotNull($record);
        $this->assertNotEmpty($record->token);
        // Framework stores hashed token
        $this->assertStringStartsWith('$2y$', $record->token);
    }

    public function test_forgot_password_handles_mail_transport_exception_gracefully(): void
    {
        // Simulate a mailer transport failure when dispatching notification
        Notification::shouldReceive('send')
            ->andThrow(new \Symfony\Component\Mailer\Exception\TransportException('SMTP Connection timeout or bad credentials'));

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', ['email' => $user->email]);

        // Must not blow up with a 500 error or expose the failure
        $response->assertSessionHas('status');
        $response->assertSessionHasNoErrors();
    }

    public function test_password_reset_invalidates_active_device_sessions(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'active_session_hash' => hash('sha256', 'existing-device-session-id'),
        ]);

        $session = \App\Models\UserSession::create([
            'user_id' => $user->id,
            'session_id_hash' => hash('sha256', 'existing-device-session-id'),
            'device_signature' => hash('sha256', 'existing-device-sig'),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'device_type' => 'desktop',
            'browser' => 'Chrome',
            'platform' => 'Windows',
            'first_authenticated_at' => now(),
            'last_active_at' => now(),
            'revoked_at' => null,
        ]);

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user, $session) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'BrandNewPassword123!',
                'password_confirmation' => 'BrandNewPassword123!',
            ]);

            $response->assertRedirect(route('login'));

            // Check that active_session_hash was cleared
            $user->refresh();
            $this->assertNull($user->active_session_hash);

            // Check that previous user session was marked revoked
            $session->refresh();
            $this->assertTrue($session->isRevoked());
            $this->assertNotNull($session->revoked_at);

            // Token consumed from password_reset_tokens
            $this->assertDatabaseMissing('password_reset_tokens', [
                'email' => $user->email,
            ]);

            return true;
        });
    }
}

