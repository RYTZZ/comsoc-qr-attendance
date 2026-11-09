<?php

namespace Tests\Feature\Auth;

use App\Mail\PasswordChangedConfirmationMail;
use App\Mail\PasswordResetTokenMail;
use App\Models\PasswordResetCode;
use App\Models\User;
use App\Models\UserSession;
use App\Services\PasswordResetCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_pr_001_request_reset_using_registered_email_generates_code_and_emails(): void
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

        // Verify email dispatched with 6-char code
        Mail::assertSent(PasswordResetTokenMail::class, function ($mail) use ($user) {
            return $mail->hasTo('student@example.com')
                && strlen($mail->token) === 6
                && ctype_alnum($mail->token)
                && $mail->userName === $user->name;
        });

        // Verify stored as hash in password_reset_codes
        $this->assertDatabaseHas('password_reset_codes', [
            'user_id' => $user->id,
            'email' => 'student@example.com',
        ]);

        $record = PasswordResetCode::where('user_id', $user->id)->first();
        $this->assertNotNull($record->code_hash);
        $this->assertNotEquals(6, strlen($record->code_hash)); // Hashed, not plaintext!
    }

    public function test_pr_002_request_using_unregistered_email_shows_generic_response(): void
    {
        Mail::fake();

        $response = $this->post('/forgot-password', [
            'email' => 'unknown@example.com',
        ]);

        $response->assertRedirect(route('password.verify.show', ['email' => 'unknown@example.com']));
        Mail::assertNothingSent();
        $this->assertDatabaseMissing('password_reset_codes', ['email' => 'unknown@example.com']);
    }

    public function test_pr_003_verify_valid_six_character_code(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $codeService = app(PasswordResetCodeService::class);
        $code = $codeService->generateCode();

        PasswordResetCode::create([
            'user_id' => $user->id,
            'email' => 'student@example.com',
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->post('/verify-reset-token', [
            'email' => 'student@example.com',
            'code' => $code,
        ]);

        $response->assertRedirect(route('password.reset.form'));
        $response->assertSessionHas('verified_password_reset_code');
    }

    public function test_pr_004_enter_incorrect_code_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);

        PasswordResetCode::create([
            'user_id' => $user->id,
            'email' => 'student@example.com',
            'code_hash' => Hash::make('ABCDEF'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->post('/verify-reset-token', [
            'email' => 'student@example.com',
            'code' => 'WRONG1',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertNull(session('verified_password_reset_code'));

        // Check attempt incremented
        $record = PasswordResetCode::where('user_id', $user->id)->first();
        $this->assertEquals(1, $record->attempts);
    }

    public function test_pr_005_enter_expired_code_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);

        PasswordResetCode::create([
            'user_id' => $user->id,
            'email' => 'student@example.com',
            'code_hash' => Hash::make('ABCDEF'),
            'attempts' => 0,
            'expires_at' => now()->subMinutes(1), // Expired
        ]);

        $response = $this->post('/verify-reset-token', [
            'email' => 'student@example.com',
            'code' => 'ABCDEF',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_pr_006_reuse_previously_consumed_code_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);

        PasswordResetCode::create([
            'user_id' => $user->id,
            'email' => 'student@example.com',
            'code_hash' => Hash::make('ABCDEF'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(15),
            'consumed_at' => now()->subMinute(), // Consumed
        ]);

        $response = $this->post('/verify-reset-token', [
            'email' => 'student@example.com',
            'code' => 'ABCDEF',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_pr_007_request_new_code_invalidates_previous_active_code(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);

        $firstCode = PasswordResetCode::create([
            'user_id' => $user->id,
            'email' => 'student@example.com',
            'code_hash' => Hash::make('FIRST1'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(15),
        ]);
        // Set created_at into the past so cooldown doesn't block issuance
        $firstCode->timestamps = false;
        $firstCode->created_at = now()->subSeconds(70);
        $firstCode->save();

        $codeService = app(PasswordResetCodeService::class);
        $result = $codeService->issueAndSend($user, '127.0.0.1', enforceCooldown: true);
        $this->assertTrue($result['sent']);

        $firstCode->refresh();
        $this->assertNotNull($firstCode->invalidated_at);
    }

    public function test_pr_008_resend_before_cooldown_ends_is_throttled(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);

        PasswordResetCode::create([
            'user_id' => $user->id,
            'email' => 'student@example.com',
            'code_hash' => Hash::make('FIRST1'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(15),
            'created_at' => now()->subSeconds(20), // 40s remaining in cooldown
        ]);

        $codeService = app(PasswordResetCodeService::class);
        $result = $codeService->issueAndSend($user, '127.0.0.1', enforceCooldown: true);

        $this->assertFalse($result['sent']);
        $this->assertEquals('throttled', $result['status']);
        $this->assertGreaterThan(0, $result['cooldown_seconds']);
    }

    public function test_pr_009_exceed_verification_attempts_blocks_further_use(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);

        $codeRecord = PasswordResetCode::create([
            'user_id' => $user->id,
            'email' => 'student@example.com',
            'code_hash' => Hash::make('ABCDEF'),
            'attempts' => 4, // 5th attempt will exhaust it
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->post('/verify-reset-token', [
            'email' => 'student@example.com',
            'code' => 'WRONG9',
        ]);

        $response->assertSessionHasErrors('code');
        $codeRecord->refresh();
        $this->assertNotNull($codeRecord->invalidated_at);

        // Even with the correct code now, it must be rejected because it is invalidated
        $retry = $this->post('/verify-reset-token', [
            'email' => 'student@example.com',
            'code' => 'ABCDEF',
        ]);
        $retry->assertSessionHasErrors('code');
    }

    public function test_pr_010_submit_mismatched_passwords_fails_validation(): void
    {
        $user = User::factory()->create([
            'email' => 'student@example.com',
            'role' => 'student',
            'password' => Hash::make('OldPassword123!'),
        ]);

        $codeRecord = PasswordResetCode::create([
            'user_id' => $user->id,
            'email' => 'student@example.com',
            'code_hash' => Hash::make('ABCDEF'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->withSession([
            'verified_password_reset_code' => [
                'code_id' => $codeRecord->id,
                'user_id' => $user->id,
                'email' => $user->email,
                'verified_at' => now()->timestamp,
            ],
        ])->post('/reset-password', [
            'email' => 'student@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'Mismatch123!',
        ]);

        $response->assertSessionHasErrors('password');
        $user->refresh();
        $this->assertTrue(Hash::check('OldPassword123!', $user->password));
    }

    public function test_pr_011_to_013_complete_password_reset_and_login_verification(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'student@example.com',
            'role' => 'student',
            'password' => Hash::make('OldPassword123!'),
        ]);

        UserSession::create([
            'user_id' => $user->id,
            'session_id_hash' => hash('sha256', 'sample-session'),
            'device_type' => 'Desktop',
            'device_signature' => 'sig_sample_123',
            'first_authenticated_at' => now(),
            'last_active_at' => now(),
            'revoked_at' => null,
        ]);

        $codeRecord = PasswordResetCode::create([
            'user_id' => $user->id,
            'email' => 'student@example.com',
            'code_hash' => Hash::make('ABCDEF'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->withSession([
            'verified_password_reset_code' => [
                'code_id' => $codeRecord->id,
                'user_id' => $user->id,
                'email' => $user->email,
                'verified_at' => now()->timestamp,
            ],
        ])->post('/reset-password', [
            'email' => 'student@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        // PR-011: Password is securely updated
        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword123!', $user->password));
        $this->assertFalse(Hash::check('OldPassword123!', $user->password));

        // Code consumed
        $codeRecord->refresh();
        $this->assertNotNull($codeRecord->consumed_at);

        // Active sessions revoked
        $this->assertEquals(0, UserSession::where('user_id', $user->id)->whereNull('revoked_at')->count());

        // Confirmation email dispatched
        Mail::assertSent(PasswordChangedConfirmationMail::class, function ($mail) {
            return $mail->hasTo('student@example.com');
        });

        // PR-012: Old password fails login
        $this->post('/login', [
            'email' => 'student@example.com',
            'password' => 'OldPassword123!',
        ])->assertSessionHasErrors();

        // PR-013: New password succeeds login
        $this->post('/login', [
            'email' => 'student@example.com',
            'password' => 'NewPassword123!',
        ])->assertRedirect('/dashboard');
    }

    public function test_pr_014_verify_reset_email_layout(): void
    {
        $mail = new PasswordResetTokenMail(
            userName: 'Juan Dela Cruz',
            token: 'A7K9P2',
            email: 'juan@example.com',
            expirationMinutes: 15,
        );

        $html = $mail->render();

        // Branding layout checks
        $this->assertStringContainsString('COMSOC HUB', $html);
        $this->assertStringContainsString('PASSWORD RECOVERY', $html);
        $this->assertStringContainsString('A7K9P2', $html);
        $this->assertStringContainsString('YOUR RESET CODE', $html);
        $this->assertStringContainsString('Expires in 15 minutes', $html);
        $this->assertStringContainsString('One Community. One Platform. More Possibilities.', $html);
    }
}
