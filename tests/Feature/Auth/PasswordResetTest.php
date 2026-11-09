<?php

namespace Tests\Feature\Auth;

use App\Mail\PasswordChangedConfirmationMail;
use App\Mail\PasswordResetTokenMail;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
        $response->assertSee('Forgot your password?');
        $response->assertSee('Send Reset Token');
    }

    public function test_reset_token_can_be_requested_for_registered_user(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'student@example.com',
            'is_active' => true,
        ]);

        $response = $this->post('/forgot-password', [
            'email' => 'student@example.com',
        ]);

        $response->assertRedirect(route('password.verify.show', ['email' => 'student@example.com']));
        $response->assertSessionHas('status');

        Mail::assertSent(PasswordResetTokenMail::class, function ($mail) use ($user) {
            return $mail->hasTo('student@example.com')
                && !empty($mail->token)
                && $mail->userName === $user->name;
        });

        // Verify token is stored hashed in password_reset_tokens table
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'student@example.com',
        ]);
    }

    public function test_forgot_password_returns_generic_response_for_unregistered_email(): void
    {
        Mail::fake();

        $response = $this->post('/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertRedirect(route('password.verify.show', ['email' => 'nonexistent@example.com']));
        $response->assertSessionHas('status');

        Mail::assertNothingSent();
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'nonexistent@example.com',
        ]);
    }

    public function test_verify_reset_token_screen_can_be_rendered(): void
    {
        $response = $this->get('/verify-reset-token?email=student@example.com');

        $response->assertStatus(200);
        $response->assertSee('Verify Reset Token');
        $response->assertSee('student@example.com');
    }

    public function test_valid_token_can_be_verified_and_proceeds_to_password_reset_form(): void
    {
        $user = User::factory()->create([
            'email' => 'student@example.com',
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->post('/verify-reset-token', [
            'email' => 'student@example.com',
            'token' => $token,
        ]);

        $response->assertRedirect(route('password.reset.form'));
        $response->assertSessionHas('status');
        $response->assertSessionHas('verified_password_reset');

        // Now user can view reset password form
        $formResponse = $this->get('/reset-password');
        $formResponse->assertStatus(200);
        $formResponse->assertSee('Create New Password');
        $formResponse->assertSee('student@example.com');
    }

    public function test_invalid_token_is_rejected(): void
    {
        $user = User::factory()->create([
            'email' => 'student@example.com',
        ]);

        Password::broker()->createToken($user);

        $response = $this->post('/verify-reset-token', [
            'email' => 'student@example.com',
            'token' => 'invalid-token-123',
        ]);

        $response->assertSessionHasErrors('token');
        $this->assertNull(session('verified_password_reset'));
    }

    public function test_expired_token_is_rejected(): void
    {
        $user = User::factory()->create([
            'email' => 'student@example.com',
        ]);

        $token = Password::broker()->createToken($user);

        // Artificially expire the token in database (older than 60 minutes)
        DB::table('password_reset_tokens')
            ->where('email', 'student@example.com')
            ->update(['created_at' => now()->subMinutes(61)]);

        $response = $this->post('/verify-reset-token', [
            'email' => 'student@example.com',
            'token' => $token,
        ]);

        $response->assertSessionHasErrors('token');
    }

    public function test_cannot_access_reset_password_form_without_verifying_token_first(): void
    {
        $response = $this->get('/reset-password');

        $response->assertRedirect(route('password.verify.show'));
        $response->assertSessionHasErrors('token');
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'student@example.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        // Create an active device session
        UserSession::create([
            'user_id' => $user->id,
            'session_id_hash' => hash('sha256', 'sample-session'),
            'device_type' => 'Desktop',
            'device_signature' => 'sig_sample_123',
            'first_authenticated_at' => now(),
            'last_active_at' => now(),
            'revoked_at' => null,
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => 'student@example.com',
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        // Verify password changed
        $user->refresh();
        $this->assertTrue(Hash::check('NewSecurePassword123!', $user->password));
        $this->assertFalse(Hash::check('OldPassword123!', $user->password));

        // Verify user is NOT automatically authenticated
        $this->assertGuest();

        // Verify active sessions revoked
        $this->assertEquals(0, UserSession::where('user_id', $user->id)->whereNull('revoked_at')->count());

        // Verify token cannot be reused
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'student@example.com',
        ]);

        // Verify confirmation email dispatched
        Mail::assertSent(PasswordChangedConfirmationMail::class, function ($mail) {
            return $mail->hasTo('student@example.com');
        });
    }

    public function test_password_reset_fails_with_mismatched_password_confirmation(): void
    {
        $user = User::factory()->create([
            'email' => 'student@example.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => 'student@example.com',
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'Mismatch123!',
        ]);

        $response->assertSessionHasErrors('password');

        $user->refresh();
        $this->assertTrue(Hash::check('OldPassword123!', $user->password));
    }

    public function test_used_token_cannot_be_reused(): void
    {
        $user = User::factory()->create([
            'email' => 'student@example.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        $token = Password::broker()->createToken($user);

        // First reset
        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'student@example.com',
            'password' => 'NewSecurePassword1!',
            'password_confirmation' => 'NewSecurePassword1!',
        ])->assertRedirect(route('login'));

        // Second reset attempt with same token
        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => 'student@example.com',
            'password' => 'AnotherPassword2!',
            'password_confirmation' => 'AnotherPassword2!',
        ]);

        $response->assertSessionHasErrors('token');

        $user->refresh();
        $this->assertTrue(Hash::check('NewSecurePassword1!', $user->password));
        $this->assertFalse(Hash::check('AnotherPassword2!', $user->password));
    }

    public function test_token_cannot_be_used_for_different_user_email(): void
    {
        $userA = User::factory()->create(['email' => 'usera@example.com']);
        $userB = User::factory()->create([
            'email' => 'userb@example.com',
            'password' => Hash::make('UserBOldPassword1!'),
        ]);

        // Token generated for User A
        $tokenA = Password::broker()->createToken($userA);

        // Attempt to use User A's token to reset User B's password
        $response = $this->post('/reset-password', [
            'token' => $tokenA,
            'email' => 'userb@example.com',
            'password' => 'AttackerPassword123!',
            'password_confirmation' => 'AttackerPassword123!',
        ]);

        $response->assertSessionHasErrors('token');

        $userB->refresh();
        $this->assertTrue(Hash::check('UserBOldPassword1!', $userB->password));
    }

    public function test_login_screen_contains_forgot_password_link(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee(route('password.request'));
        $response->assertSee('Forgot Password?');
    }
}
