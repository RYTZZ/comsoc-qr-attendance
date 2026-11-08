<?php

namespace Tests\Feature\Auth;

use App\Mail\EmailVerificationOtpMail;
use App\Models\AcademicYear;
use App\Models\EmailVerificationCode;
use App\Models\Student;
use App\Models\User;
use App\Services\EmailVerificationOtpService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailVerificationOtpTest extends TestCase
{
    use RefreshDatabase;

    private function validSignatureDataUrl(int $w = 300, int $h = 100): string
    {
        $im = imagecreatetruecolor($w, $h);
        imagealphablending($im, false);
        imagesavealpha($im, true);
        imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 127));
        imagesetthickness($im, 4);
        imageline($im, 10, 10, $w - 10, $h - 10, imagecolorallocate($im, 0, 0, 0));
        ob_start();
        imagepng($im);
        $png = ob_get_clean();

        return 'data:image/png;base64,' . base64_encode($png);
    }

    public function test_otp_generates_exactly_six_digits_and_preserves_leading_zeroes(): void
    {
        $service = app(EmailVerificationOtpService::class);

        for ($i = 0; $i < 50; $i++) {
            $code = $service->generateOtpCode();
            $this->assertSame(6, strlen($code));
            $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        }
    }

    public function test_otp_is_stored_as_a_hash_and_never_plaintext(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'student.otp@sorsu.edu.ph',
            'name' => 'Maria Santos',
            'role' => 'student',
            'is_active' => true,
        ]);

        $service = app(EmailVerificationOtpService::class);
        $result = $service->issueAndSend($user, '127.0.0.1', enforceCooldown: false);

        $this->assertTrue($result['sent']);
        $this->assertDatabaseCount('email_verification_codes', 1);

        $record = EmailVerificationCode::first();
        $this->assertNotNull($record);
        $this->assertNotEquals('000000', $record->code_hash);
        $this->assertDoesNotMatchRegularExpression('/^\d{6}$/', $record->code_hash);
        $this->assertTrue(str_starts_with($record->code_hash, '$2y$'));

        Mail::assertSent(EmailVerificationOtpMail::class, function ($mail) use ($user, $record) {
            return $mail->hasTo('student.otp@sorsu.edu.ph')
                && Hash::check($mail->otpCode, $record->code_hash);
        });
    }

    public function test_valid_otp_verifies_email_and_invalidates_code(): void
    {
        Event::fake([Verified::class]);

        $user = User::factory()->unverified()->create([
            'email' => 'maria.verify@sorsu.edu.ph',
            'role' => 'student',
            'is_active' => true,
        ]);

        $plainCode = '048291';
        $codeRecord = EmailVerificationCode::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'code_hash' => Hash::make($plainCode),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->actingAs($user)->post(route('verification.otp.verify'), [
            'code' => '048291',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false) . '?verified=1');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertNotNull($codeRecord->fresh()->consumed_at);

        Event::assertDispatched(Verified::class);
    }

    public function test_invalid_otp_is_rejected_and_increments_attempts(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'invalid.otp@sorsu.edu.ph',
            'role' => 'student',
            'is_active' => true,
        ]);

        $plainCode = '123456';
        $codeRecord = EmailVerificationCode::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'code_hash' => Hash::make($plainCode),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->actingAs($user)->from(route('verification.notice'))->post(route('verification.otp.verify'), [
            'code' => '999999',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $response->assertSessionHasErrors(['code']);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->assertSame(1, $codeRecord->fresh()->attempts);
    }

    public function test_expired_otp_is_rejected(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'expired.otp@sorsu.edu.ph',
            'role' => 'student',
            'is_active' => true,
        ]);

        $plainCode = '654321';
        $codeRecord = EmailVerificationCode::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'code_hash' => Hash::make($plainCode),
            'attempts' => 0,
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($user)->from(route('verification.notice'))->post(route('verification.otp.verify'), [
            'code' => '654321',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $response->assertSessionHasErrors(['code']);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->assertNotNull($codeRecord->fresh()->invalidated_at);
    }

    public function test_consumed_otp_cannot_be_reused(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'consumed.otp@sorsu.edu.ph',
            'role' => 'student',
            'is_active' => true,
        ]);

        $plainCode = '777888';
        EmailVerificationCode::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'code_hash' => Hash::make($plainCode),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(15),
            'consumed_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($user)->from(route('verification.notice'))->post(route('verification.otp.verify'), [
            'code' => '777888',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $response->assertSessionHasErrors(['code']);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_fifth_failed_attempt_invalidates_the_code_and_sixth_attempt_is_rejected(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'maxattempts@sorsu.edu.ph',
            'role' => 'student',
            'is_active' => true,
        ]);

        $plainCode = '112233';
        $codeRecord = EmailVerificationCode::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'code_hash' => Hash::make($plainCode),
            'attempts' => 4, // 4 failed attempts already
            'expires_at' => now()->addMinutes(15),
        ]);

        // 5th attempt with wrong code
        $this->actingAs($user)->post(route('verification.otp.verify'), ['code' => '000000']);

        $codeRecord->refresh();
        $this->assertSame(5, $codeRecord->attempts);
        $this->assertNotNull($codeRecord->invalidated_at);

        // 6th attempt even with the correct code must now be rejected
        $sixthResponse = $this->actingAs($user)->from(route('verification.notice'))->post(route('verification.otp.verify'), [
            'code' => '112233',
        ]);

        $sixthResponse->assertRedirect(route('verification.notice'));
        $sixthResponse->assertSessionHasErrors(['code']);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_resend_is_blocked_before_60_seconds_and_allowed_after(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'cooldown@sorsu.edu.ph',
            'role' => 'student',
            'is_active' => true,
        ]);

        $service = app(EmailVerificationOtpService::class);

        // Initial issuance
        $first = $service->issueAndSend($user, '127.0.0.1', enforceCooldown: false);
        $this->assertTrue($first['sent']);

        // Immediate resend attempt should be blocked by 60s cooldown
        $second = $service->issueAndSend($user, '127.0.0.1', enforceCooldown: true);
        $this->assertFalse($second['sent']);
        $this->assertSame('cooldown', $second['status']);
        $this->assertGreaterThan(0, $second['cooldown_seconds']);

        // Fast forward 61 seconds
        $this->travel(61)->seconds();

        // Resend attempt should now succeed
        $third = $service->issueAndSend($user, '127.0.0.1', enforceCooldown: true);
        $this->assertTrue($third['sent']);
        $this->assertSame('sent', $third['status']);
    }

    public function test_previous_code_becomes_invalidated_when_resend_generates_a_new_code(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'replacement@sorsu.edu.ph',
            'role' => 'student',
            'is_active' => true,
        ]);

        $service = app(EmailVerificationOtpService::class);

        $res1 = $service->issueAndSend($user, '127.0.0.1', enforceCooldown: false);
        $codeRecord1 = $res1['code_record'];

        $this->travel(65)->seconds();

        $res2 = $service->issueAndSend($user, '127.0.0.1', enforceCooldown: true);
        $codeRecord2 = $res2['code_record'];

        $this->assertNotNull($codeRecord1->fresh()->invalidated_at);
        $this->assertNull($codeRecord2->fresh()->invalidated_at);
        $this->assertNotEquals($codeRecord1->id, $codeRecord2->id);
    }

    public function test_email_changes_invalidate_previous_otps_and_marks_email_unverified(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'original@sorsu.edu.ph',
            'email_verified_at' => now(),
            'role' => 'student',
        ]);

        $service = app(EmailVerificationOtpService::class);
        EmailVerificationCode::create([
            'user_id' => $user->id,
            'email' => 'original@sorsu.edu.ph',
            'code_hash' => Hash::make('123456'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => 'new.email@sorsu.edu.ph',
            'username' => 'newusername',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $user->refresh();

        $this->assertSame('new.email@sorsu.edu.ph', $user->email);
        $this->assertNull($user->email_verified_at);

        // Previous OTP is invalidated
        $previousCode = EmailVerificationCode::where('email', 'original@sorsu.edu.ph')->first();
        $this->assertNotNull($previousCode->invalidated_at);

        // New OTP was created for the new email address
        $newCode = EmailVerificationCode::where('email', 'new.email@sorsu.edu.ph')->first();
        $this->assertNotNull($newCode);
    }

    public function test_unverified_students_cannot_bypass_verification_through_protected_routes(): void
    {
        $user = User::factory()->unverified()->create([
            'role' => 'student',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertRedirect(route('verification.notice'));
    }

    public function test_existing_verified_students_and_super_admins_retain_access(): void
    {
        $verifiedStudent = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $resp1 = $this->actingAs($verifiedStudent)->get(route('dashboard'));
        $resp1->assertStatus(200);

        // Super Admin account without email_verified_at retains admin access
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $resp2 = $this->actingAs($superAdmin)->get(route('admin.dashboard'));
        $resp2->assertStatus(200);
    }

    public function test_resend_endpoint_returns_json_with_cooldown_and_server_time(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'jsonresend@sorsu.edu.ph',
            'role' => 'student',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->postJson(route('verification.send'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'cooldown_seconds',
            'cooldown_expires_at',
            'server_time',
        ]);
        $this->assertTrue($response->json('success'));
        $this->assertEquals(60, $response->json('cooldown_seconds'));

        // Immediate subsequent request must return 429 JSON response
        $throttledResponse = $this->actingAs($user)->postJson(route('verification.send'));
        $throttledResponse->assertStatus(429);
        $throttledResponse->assertJsonStructure([
            'success',
            'message',
            'cooldown_seconds',
            'cooldown_expires_at',
            'server_time',
        ]);
        $this->assertFalse($throttledResponse->json('success'));
        $this->assertGreaterThan(0, $throttledResponse->json('cooldown_seconds'));
    }

    public function test_email_template_contains_required_branding_logo_and_security_notice(): void
    {
        $mailable = new EmailVerificationOtpMail('Juan Dela Cruz', '456789', 15);
        $rendered = $mailable->render();

        $this->assertStringContainsString('Verify Your Email Address', $rendered);
        $this->assertStringContainsString('Hello, Juan Dela Cruz!', $rendered);
        $this->assertStringContainsString('456789', $rendered);
        $this->assertStringContainsString('15 minutes', $rendered);
        $this->assertStringContainsString('Never share this verification code with anyone', $rendered);
        $this->assertStringContainsString('One Community. One Platform. More Possibilities.', $rendered);
        $this->assertStringContainsString('Computing Society Organization', $rendered);
        $this->assertStringContainsString(date('Y'), $rendered);
        $this->assertStringContainsString('COMSOC.png', $rendered);
    }
}
