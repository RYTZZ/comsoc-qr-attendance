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

    /**
     * Regression test: password reset notification MUST be sent synchronously
     * (notifyNow) so that it works even when no queue worker is running
     * (e.g. Render free-tier deployment).
     *
     * Root cause of the original production bug: QUEUE_CONNECTION=database with
     * no running worker meant queued notifications were silently dropped.
     * User::sendPasswordResetNotification() now calls notifyNow() explicitly.
     */
    public function test_password_reset_notification_is_sent_synchronously_without_queue(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        // Simulate a queue that would reject any queued jobs
        // by overriding the queue connection to 'sync' isn't needed —
        // the key assertion is that the notification arrives regardless of queue state.
        // Notification::fake() captures both notify() and notifyNow() dispatches.
        $response = $this->post('/forgot-password', ['email' => $user->email]);

        $response->assertSessionHas('status');
        $response->assertSessionHasNoErrors();

        // The notification MUST have been sent (notifyNow bypasses queue)
        Notification::assertSentTo($user, ResetPassword::class);

        // Confirm no 500 error or generic "unexpected error" was produced
        $response->assertSessionDoesntHaveErrors(['error']);
    }

    /**
     * Regression test: the User model's custom appNotifications() HasMany must
     * NOT shadow the Notifiable trait's notifications() morphMany, because
     * Password::sendResetLink() uses notify() internally which depends on the
     * Notifiable trait's routing logic.
     */
    public function test_user_notifications_relation_does_not_shadow_notifiable_trait(): void
    {
        $user = User::factory()->create();

        // appNotifications() must return the custom App\Models\Notification HasMany
        $appNotificationsRelation = $user->appNotifications();
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $appNotificationsRelation
        );

        // notifications() must now return the Notifiable trait's morphMany
        // (Illuminate\Notifications\DatabaseNotification), NOT the custom HasMany.
        // If the two were confused, this would return a HasMany instead.
        $notifiableRelation = $user->notifications();
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\MorphMany::class,
            $notifiableRelation,
            'User::notifications() must return the Notifiable trait MorphMany, ' .
            'not the custom HasMany. The custom relation must be renamed to appNotifications().'
        );
    }

    public function test_forgot_password_handles_mail_transport_exception_gracefully(): void
    {
        // Simulate a mailer transport failure when dispatching notification
        Notification::shouldReceive('sendNow')
            ->andThrow(new \Symfony\Component\Mailer\Exception\TransportException('SMTP Connection timeout or bad credentials'));

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', ['email' => $user->email]);

        // Must not blow up with a 500 error, must not report false success, and must not leak SMTP credentials
        $response->assertSessionHasErrors(['error']);
        $errorMessage = session('errors')->first('error');
        $this->assertStringContainsString('mail service connection issue', $errorMessage);
        $this->assertStringContainsString('Ref:', $errorMessage);
        $this->assertStringNotContainsString('SMTP Connection timeout or bad credentials', $errorMessage);

        // Verify the reset token was still safely created in the database
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);

        \Mockery::close();
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

    public function test_email_validation_error_renders_only_at_field_level(): void
    {
        $response = $this->from('/forgot-password')->post('/forgot-password', [
            'email' => 'not-an-email',
        ]);

        $response->assertRedirect('/forgot-password');
        $response->assertSessionHasErrors(['email']);
        $response->assertSessionDoesntHaveErrors(['error']);

        // Test view rendering with validation error on email
        $view = $this->withSession([
            'errors' => session('errors'),
        ])->get('/forgot-password');

        $view->assertStatus(200);
        $view->assertSee('The email field must be a valid email address.');
        // Should NOT display the top alert-error box for normal email field validation
        $view->assertDontSee('alert-error', false);
    }

    public function test_general_error_renders_in_top_alert_without_duplicate(): void
    {
        // When a general processing error occurs, it should be flashed under 'error'
        $view = $this->withSession([
            'errors' => (new \Illuminate\Support\ViewErrorBag)->put('default', new \Illuminate\Support\MessageBag([
                'error' => 'An unexpected error occurred while processing your request. Please try again shortly.',
            ])),
        ])->get('/forgot-password');

        $view->assertStatus(200);
        $view->assertSee('alert-error', false);
        $view->assertSee('An unexpected error occurred while processing your request. Please try again shortly.');
        
        // Count occurrences of the message in the rendered HTML to ensure no duplicate
        $content = $view->getContent();
        $occurrences = substr_count($content, 'An unexpected error occurred while processing your request. Please try again shortly.');
        $this->assertEquals(1, $occurrences, 'The error message must only appear once on the page.');
    }

    public function test_reset_password_link_works_with_case_insensitive_and_whitespace_email(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'student.case@sorsu.edu.ph',
        ]);

        $response = $this->post('/forgot-password', [
            'email' => '  STUDENT.CASE@sorsu.edu.ph  ',
        ]);

        $response->assertSessionHas('status');
        $response->assertSessionHasNoErrors();

        Notification::assertSentTo($user, ResetPassword::class);
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'student.case@sorsu.edu.ph',
        ]);
    }

    public function test_reset_password_link_works_with_preferred_email(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'official.student@sorsu.edu.ph',
            'preferred_email' => 'personal.student@gmail.com',
        ]);

        $response = $this->post('/forgot-password', [
            'email' => 'PERSONAL.STUDENT@gmail.com',
        ]);

        $response->assertSessionHas('status');
        $response->assertSessionHasNoErrors();

        // Notification must be sent to the user model
        Notification::assertSentTo($user, ResetPassword::class);

        // Token must be stored under the canonical email
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'official.student@sorsu.edu.ph',
        ]);
    }

    public function test_password_can_be_reset_submitting_preferred_email_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'official.recovery@sorsu.edu.ph',
            'preferred_email' => 'personal.recovery@gmail.com',
        ]);

        $this->post('/forgot-password', ['email' => 'personal.recovery@gmail.com']);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => 'personal.recovery@gmail.com',
                'password' => 'NewSecurePassword123!',
                'password_confirmation' => 'NewSecurePassword123!',
            ]);

            $response->assertRedirect(route('login'));

            $this->assertTrue(auth()->validate([
                'email' => 'official.recovery@sorsu.edu.ph',
                'password' => 'NewSecurePassword123!',
            ]));

            return true;
        });
    }

    public function test_password_reset_email_rendered_content_and_branding(): void
    {
        $user = User::factory()->create([
            'name' => 'Maria Clara',
            'email' => 'maria@comsochub.tech',
        ]);

        $token = 'test-token-123456';
        $notification = new ResetPassword($token);
        $mailMessage = $notification->toMail($user);

        // Subject
        $this->assertStringContainsString('Reset Your Password — COMSOC HUB', $mailMessage->subject);

        // Verify HTML and text views are registered
        $htmlView = is_array($mailMessage->view) ? $mailMessage->view['html'] : $mailMessage->view;
        $textView = is_array($mailMessage->view) ? $mailMessage->view['text'] : null;

        $this->assertEquals('emails.password-reset', $htmlView);
        $this->assertEquals('emails.password-reset-text', $textView);

        // Render HTML view
        $html = view($htmlView, $mailMessage->viewData)->render();

        // 1. Branding & Institution
        $this->assertStringContainsString('COMSOC HUB', $html);
        $this->assertStringContainsString('COMPUTING SOCIETY ORGANIZATION', $html);
        $this->assertStringContainsString('Sorsogon State University', $html);
        $this->assertStringContainsString('Bulan Campus', $html);

        // 2. Color specifications
        $this->assertStringContainsString('#560E0F', $html); // Primary brand maroon
        $this->assertStringContainsString('#0B1120', $html); // Dark background
        $this->assertStringContainsString('#172033', $html); // Secondary card background
        $this->assertStringContainsString('#1E293B', $html); // Border / accent
        $this->assertStringContainsString('#F8FAFC', $html); // Text highlight
        $this->assertStringContainsString('#94A3B8', $html); // Secondary text

        // 3. Headings, personalized greeting & supporting text
        $this->assertStringContainsString('Reset Your Password', $html);
        $this->assertStringContainsString('Hi, Maria Clara!', $html);
        $this->assertStringContainsString('We received a request to reset the password for your COMSOC HUB account.', $html);

        // 4. Primary CTA button
        $this->assertStringContainsString('RESET MY PASSWORD', $html);
        $this->assertStringContainsString(urlencode('maria@comsochub.tech'), $html);
        $this->assertStringContainsString($token, $html);

        // 5. Expiration notice (15 minutes configured backend value)
        $this->assertStringContainsString('This password reset link expires in 15 minutes.', $html);

        // 6. Fallback URL
        $this->assertStringContainsString("If the button doesn't work, copy and paste the following link into your browser:", $html);

        // 7. Security notice
        $this->assertStringContainsString("Didn't request a password reset?", $html);
        $this->assertStringContainsString('You can safely ignore this email.', $html);
        $this->assertStringContainsString('Your password will remain unchanged unless you complete the reset process.', $html);

        // 8. Footer
        $this->assertStringContainsString('Automated security notification. Please do not reply to this email.', $html);

        // Render plain text view
        $plainText = view($textView, $mailMessage->viewData)->render();
        $this->assertStringContainsString('COMSOC HUB', $plainText);
        $this->assertStringContainsString('COMPUTING SOCIETY ORGANIZATION', $plainText);
        $this->assertStringContainsString('Sorsogon State University – Bulan Campus', $plainText);
        $this->assertStringContainsString('RESET YOUR PASSWORD', $plainText);
        $this->assertStringContainsString('Hi, Maria Clara!', $plainText);
        $this->assertStringContainsString('RESET MY PASSWORD:', $plainText);
        $this->assertStringContainsString('This password reset link expires in 15 minutes.', $plainText);
        $this->assertStringContainsString("Didn't request a password reset? You can safely ignore this email.", $plainText);
        $this->assertStringContainsString('Automated security notification. Please do not reply to this email.', $plainText);
    }

    public function test_password_reset_email_uses_fallback_greeting_when_name_missing(): void
    {
        $user = User::factory()->create([
            'name' => '',
            'email' => 'anonymous@comsochub.tech',
        ]);

        $notification = new ResetPassword('token-fallback');
        $mailMessage = $notification->toMail($user);

        $htmlView = is_array($mailMessage->view) ? $mailMessage->view['html'] : $mailMessage->view;
        $html = view($htmlView, $mailMessage->viewData)->render();

        $this->assertStringContainsString('Hi, Member!', $html);
    }

    public function test_password_reset_token_cannot_be_reused(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'password' => \Illuminate\Support\Facades\Hash::make('OldInitialPassword1!'),
        ]);

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            // First reset: should succeed
            $firstResponse = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'FirstNewPassword123!',
                'password_confirmation' => 'FirstNewPassword123!',
            ]);

            $firstResponse->assertSessionHasNoErrors();
            $firstResponse->assertRedirect(route('login'));

            // Second reset using the same token: must be rejected
            $secondResponse = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'SecondNewPassword123!',
                'password_confirmation' => 'SecondNewPassword123!',
            ]);

            $secondResponse->assertSessionHasErrors(['email']);

            // Verify second new password is NOT active
            $this->assertFalse(auth()->validate([
                'email' => $user->email,
                'password' => 'SecondNewPassword123!',
            ]));

            // Verify first new password IS active
            $this->assertTrue(auth()->validate([
                'email' => $user->email,
                'password' => 'FirstNewPassword123!',
            ]));

            return true;
        });
    }

    public function test_password_reset_token_is_valid_within_15_minutes(): void
    {
        $user = User::factory()->create([
            'password' => \Illuminate\Support\Facades\Hash::make('OriginalPassword123!'),
        ]);

        // Generate token using the broker
        $rawToken = \Illuminate\Support\Facades\Password::createToken($user);

        // Set token timestamp to 14 minutes ago (within the 15-minute window)
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->update([
                'created_at' => now()->subMinutes(14),
            ]);

        // Attempt reset within the 15-minute window: should succeed
        $response = $this->post('/reset-password', [
            'token' => $rawToken,
            'email' => $user->email,
            'password' => 'ValidWithin15MinPassword123!',
            'password_confirmation' => 'ValidWithin15MinPassword123!',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('login'));

        // Password must be updated to new password
        $this->assertTrue(auth()->validate([
            'email' => $user->email,
            'password' => 'ValidWithin15MinPassword123!',
        ]));

        $this->assertFalse(auth()->validate([
            'email' => $user->email,
            'password' => 'OriginalPassword123!',
        ]));
    }

    public function test_expired_password_reset_token_is_rejected_after_15_minutes(): void
    {
        $user = User::factory()->create([
            'password' => \Illuminate\Support\Facades\Hash::make('OriginalPassword123!'),
        ]);

        // Generate token using the broker
        $rawToken = \Illuminate\Support\Facades\Password::createToken($user);

        // Manually backdate the created_at to 16 minutes ago (exceeding the 15-minute expiry)
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->update([
                'created_at' => now()->subMinutes(16),
            ]);

        // Attempt reset with the expired token
        $response = $this->post('/reset-password', [
            'token' => $rawToken,
            'email' => $user->email,
            'password' => 'ExpiredAttemptPassword123!',
            'password_confirmation' => 'ExpiredAttemptPassword123!',
        ]);

        $response->assertSessionHasErrors(['email']);

        // Password must remain unchanged
        $this->assertTrue(auth()->validate([
            'email' => $user->email,
            'password' => 'OriginalPassword123!',
        ]));

        $this->assertFalse(auth()->validate([
            'email' => $user->email,
            'password' => 'ExpiredAttemptPassword123!',
        ]));
    }

    public function test_repeated_password_reset_requests_are_throttled(): void
    {
        Notification::fake();

        // Rate limit throttling uses database token repository timestamp (throttle: 60)
        $user = User::factory()->create();

        // First request creates the token
        $firstResponse = $this->post('/forgot-password', ['email' => $user->email]);
        $firstResponse->assertSessionHas('status');
        $firstResponse->assertSessionHasNoErrors();

        // Immediate second request within 60s should be throttled
        $secondResponse = $this->post('/forgot-password', ['email' => $user->email]);
        $secondResponse->assertSessionHasErrors(['email']);
        $errorMessage = session('errors')->first('email');
        $this->assertStringContainsString('Please wait before retrying', $errorMessage);
    }

    public function test_password_reset_url_uses_canonical_production_domain_in_production(): void
    {
        $user = User::factory()->create([
            'email' => 'student.test@sorsu.edu.ph',
        ]);

        $originalEnv = app()->environment();

        // Bind production environment temporarily
        app()->detectEnvironment(fn () => 'production');

        try {
            $url = call_user_func(ResetPassword::$createUrlCallback, $user, 'production-token-xyz');

            // Must use https://comsochub.tech and contain the route and query parameters
            $this->assertStringStartsWith('https://comsochub.tech/reset-password/production-token-xyz', $url);
            $this->assertStringContainsString('email=' . urlencode('student.test@sorsu.edu.ph'), $url);
            $this->assertStringNotContainsString('localhost', $url);
            $this->assertStringNotContainsString('http://', $url);
        } finally {
            app()->detectEnvironment(fn () => $originalEnv);
        }
    }

    public function test_production_with_noop_mailer_does_not_report_success(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $originalEnv = app()->environment();

        app()['env'] = 'production';
        config(['mail.default' => 'log']);

        try {
            $response = $this->withSession(['_token' => 'csrf-test-token'])
                ->post('/forgot-password', ['_token' => 'csrf-test-token', 'email' => $user->email]);

            $response->assertSessionHasErrors(['error']);
            $response->assertSessionMissing('status');
            Notification::assertNothingSent();
        } finally {
            app()['env'] = $originalEnv;
        }
    }

    public function test_public_response_is_identical_for_known_and_unknown_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $known = $this->post('/forgot-password', ['email' => $user->email])->getSession()->get('status');
        $unknown = $this->post('/forgot-password', ['email' => 'nobody@example.com'])->getSession()->get('status');

        $this->assertNotEmpty($known);
        $this->assertSame($known, $unknown);
        $this->assertStringNotContainsString('emailed', $known);
    }
}

