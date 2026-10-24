<?php

namespace Tests\Feature\Auth;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Student;
use App\Models\StudentSignature;
use App\Models\User;
use App\Services\AttendanceSheetService;
use App\Services\SignatureVaultService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentRegistrationTest extends TestCase
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

    public function test_student_signup_screen_can_be_rendered(): void
    {
        $response = $this->get('/signup');

        $response->assertStatus(200);
        $response->assertSee('Student Registration');
        $response->assertSee('Academic Program');
        $response->assertSee('Bachelor of Science in Information Technology');
        $response->assertDontSee('<select', false);
    }

    public function test_student_signup_screen_displays_four_steps_and_predictable_responsive_grids(): void
    {
        $response = $this->get('/signup');

        $response->assertStatus(200);
        // Step order: Step 1, Step 2, Step 3, Step 4
        $response->assertSee('STEP 1 OF 4', false);
        $response->assertSee('STEP 2 OF 4', false);
        $response->assertSee('STEP 3 OF 4', false);
        $response->assertSee('STEP 4 OF 4', false);

        // Step 1: Student Information & Academic Grids
        $response->assertSee('Student Info', false);
        $response->assertSee('academic-name-grid', false);
        $response->assertSee('academic-cohort-grid', false);
        $response->assertSee('Student ID / Student Number', false);
        $response->assertSee('name="student_id"', false);
        $response->assertSee('name="first_name"', false);
        $response->assertSee('name="middle_name"', false);
        $response->assertSee('name="last_name"', false);
        $response->assertSee('name="email"', false);
        $response->assertSee("name: 'program'", false);
        $response->assertSee("name: 'year'", false);
        $response->assertSee("name: 'block'", false);

        // Step 2: Avatar & Username
        $response->assertSee('Avatar & Username', false);
        $response->assertSee('Choose Your Adventurer Avatar', false);
        $response->assertSee('avatar-grid-responsive', false);
        $response->assertSee('name="username"', false);
        $response->assertSee('Enter your username', false);
        $response->assertDontSee('e.g., rytzzdev', false);
        $response->assertSee('name="avatar_seed"', false);

        // Step 3: Account Security
        $response->assertSee('Account Security', false);
        $response->assertSee('security-passwords-grid', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="password_confirmation"', false);
        $response->assertSee('Next: Register Signature', false);

        // Step 4: Register Your Signature
        $response->assertSee('Register Signature', false);
        $response->assertSee('Draw Your Handwritten Signature', false);
        $response->assertSee('id="signature-canvas"', false);
        $response->assertSee('name="signature"', false);
        $response->assertSee('name="signature_consent"', false);
        $response->assertSee('Clear & Redraw', false);
        $response->assertSee('I confirm that this is my handwritten signature and authorize COMSOC Hub to securely store and reuse it', false);
        $response->assertSee('Create Account', false);
    }

    public function test_student_can_register_with_valid_details_and_signature(): void
    {
        AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'is_active' => true,
        ]);

        $sigData = $this->validSignatureDataUrl();

        $response = $this->post('/signup', [
            'student_id' => '21-00123',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'middle_name' => 'Santos',
            'email' => 'juan.delacruz@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Technology',
            'year' => '3rd',
            'block' => '2',
            'username' => 'juandelacruz',
            'avatar_seed' => 'Felix',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'terms' => '1',
            'signature' => $sigData,
            'signature_consent' => '1',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        $this->assertDatabaseHas('students', [
            'student_number' => '21-00123',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'middle_name' => 'Santos',
            'email' => 'juan.delacruz@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Technology',
            'year_level' => '3rd Year',
            'block' => '2',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'juan.delacruz@sorsu.edu.ph',
            'username' => 'juandelacruz',
            'avatar_seed' => 'Felix',
            'avatar_style' => 'adventurer',
            'role' => 'student',
            'is_active' => true,
        ]);

        $user = User::where('email', 'juan.delacruz@sorsu.edu.ph')->first();
        $this->assertNotNull($user->student_id);
        $this->assertNotNull($user->profile);
        $this->assertNotNull($user->adventurer_setup_at);

        // Verify signature is securely encrypted and linked to the user
        $sig = StudentSignature::where('user_id', $user->id)->first();
        $this->assertNotNull($sig);
        $this->assertEquals(StudentSignature::STATUS_ACTIVE, $sig->status);
        $this->assertEquals(1, $sig->version);
        $this->assertNotNull($sig->encrypted_payload);
        $this->assertNotNull($sig->consent_acknowledged_at);

        // Decrypt with vault and confirm normalized PNG
        $vault = app(SignatureVaultService::class);
        $decrypted = $vault->decrypt($sig);
        $this->assertNotNull($decrypted);
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $decrypted);
    }

    public function test_student_cannot_register_without_signature(): void
    {
        $response = $this->from('/signup')->post('/signup', [
            'student_id' => '21-00124',
            'first_name' => 'No',
            'last_name' => 'Signature',
            'email' => 'nosig@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Technology',
            'year' => '1st',
            'block' => '1',
            'username' => 'nosigstudent',
            'avatar_seed' => 'Felix',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'terms' => '1',
            'signature_consent' => '1',
        ]);

        $response->assertRedirect('/signup');
        $response->assertSessionHasErrors(['signature']);
        $this->assertDatabaseMissing('students', ['email' => 'nosig@sorsu.edu.ph']);
        $this->assertDatabaseMissing('users', ['email' => 'nosig@sorsu.edu.ph']);
    }

    public function test_student_cannot_register_without_signature_consent(): void
    {
        $response = $this->from('/signup')->post('/signup', [
            'student_id' => '21-00125',
            'first_name' => 'No',
            'last_name' => 'Consent',
            'email' => 'noconsent@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Technology',
            'year' => '1st',
            'block' => '1',
            'username' => 'noconsentstudent',
            'avatar_seed' => 'Felix',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'terms' => '1',
            'signature' => $this->validSignatureDataUrl(),
        ]);

        $response->assertRedirect('/signup');
        $response->assertSessionHasErrors(['signature_consent']);
        $this->assertDatabaseMissing('students', ['email' => 'noconsent@sorsu.edu.ph']);
        $this->assertDatabaseMissing('users', ['email' => 'noconsent@sorsu.edu.ph']);
    }

    public function test_student_registration_rejects_empty_or_malformed_signature_payloads(): void
    {
        // Blank canvas (all transparent pixels)
        $im = imagecreatetruecolor(200, 80);
        imagealphablending($im, false);
        imagesavealpha($im, true);
        imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 127));
        ob_start();
        imagepng($im);
        $blankDataUrl = 'data:image/png;base64,' . base64_encode(ob_get_clean());

        $response = $this->from('/signup')->post('/signup', [
            'student_id' => '21-00126',
            'first_name' => 'Blank',
            'last_name' => 'Signature',
            'email' => 'blank@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Technology',
            'year' => '1st',
            'block' => '1',
            'username' => 'blanksig',
            'avatar_seed' => 'Felix',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'terms' => '1',
            'signature' => $blankDataUrl,
            'signature_consent' => '1',
        ]);

        $response->assertRedirect('/signup');
        $response->assertSessionHasErrors(['signature']);
        $this->assertDatabaseMissing('students', ['email' => 'blank@sorsu.edu.ph']);
        $this->assertDatabaseMissing('users', ['email' => 'blank@sorsu.edu.ph']);

        // Malformed string
        $malformedResponse = $this->from('/signup')->post('/signup', [
            'student_id' => '21-00127',
            'first_name' => 'Malformed',
            'last_name' => 'Signature',
            'email' => 'malformed@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Technology',
            'year' => '1st',
            'block' => '1',
            'username' => 'malformedsig',
            'avatar_seed' => 'Felix',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'terms' => '1',
            'signature' => 'not-a-valid-base64-data-url',
            'signature_consent' => '1',
        ]);

        $malformedResponse->assertRedirect('/signup');
        $malformedResponse->assertSessionHasErrors(['signature']);
    }

    public function test_duplicate_student_id_is_rejected(): void
    {
        Student::create([
            'student_number' => '21-00999',
            'first_name' => 'Existing',
            'last_name' => 'Student',
            'email' => 'existing@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Technology',
            'year_level' => '1st Year',
            'block' => '1',
        ]);

        $response = $this->from('/signup')->post('/signup', [
            'student_id' => '21-00999',
            'first_name' => 'Another',
            'last_name' => 'Student',
            'email' => 'another@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Computer Science',
            'year' => '1st',
            'block' => '1',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'terms' => '1',
            'signature' => $this->validSignatureDataUrl(),
            'signature_consent' => '1',
        ]);

        $response->assertRedirect('/signup');
        $response->assertSessionHasErrors(['student_id']);
    }

    public function test_invalid_program_or_year_or_block_is_rejected(): void
    {
        $response = $this->from('/signup')->post('/signup', [
            'student_id' => '21-00456',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@sorsu.edu.ph',
            'program' => 'Bachelor of Fake Arts',
            'year' => '5th',
            'block' => '9',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'terms' => '1',
            'signature' => $this->validSignatureDataUrl(),
            'signature_consent' => '1',
        ]);

        $response->assertRedirect('/signup');
        $response->assertSessionHasErrors(['program', 'year', 'block']);
    }

    public function test_password_confirmation_mismatch_is_rejected(): void
    {
        $response = $this->from('/signup')->post('/signup', [
            'student_id' => '21-00789',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'mismatch@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Systems',
            'year' => '2nd',
            'block' => '1',
            'password' => 'SecurePass123',
            'password_confirmation' => 'DifferentPassword123',
            'terms' => '1',
            'signature' => $this->validSignatureDataUrl(),
            'signature_consent' => '1',
        ]);

        $response->assertRedirect('/signup');
        $response->assertSessionHasErrors(['password']);
    }

    public function test_student_id_with_more_than_eight_digits_is_rejected(): void
    {
        $response = $this->from('/signup')->post('/signup', [
            'student_id' => '2024-12345',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan.test@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Technology',
            'year' => '1st',
            'block' => '1',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'terms' => '1',
            'signature' => $this->validSignatureDataUrl(),
            'signature_consent' => '1',
        ]);

        $response->assertRedirect('/signup');
        $response->assertSessionHasErrors(['student_id']);
    }

    public function test_student_id_with_invalid_characters_is_rejected(): void
    {
        $response = $this->from('/signup')->post('/signup', [
            'student_id' => '21-0012A',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan.letters@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Technology',
            'year' => '1st',
            'block' => '1',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'terms' => '1',
            'signature' => $this->validSignatureDataUrl(),
            'signature_consent' => '1',
        ]);

        $response->assertRedirect('/signup');
        $response->assertSessionHasErrors(['student_id']);
    }

    public function test_student_id_with_eight_digits_and_leading_zeros_is_accepted(): void
    {
        AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'is_active' => true,
        ]);

        $response = $this->post('/signup', [
            'student_id' => '0024-0001',
            'first_name' => 'Zero',
            'last_name' => 'Lead',
            'email' => 'zero.lead@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Technology',
            'year' => '1st',
            'block' => '1',
            'username' => 'zerolead',
            'avatar_seed' => 'Felix',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'terms' => '1',
            'signature' => $this->validSignatureDataUrl(),
            'signature_consent' => '1',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('students', [
            'student_number' => '0024-0001',
        ]);
        $this->assertDatabaseHas('users', [
            'username' => 'zerolead',
            'avatar_seed' => 'Felix',
        ]);
    }

    public function test_registration_requires_username_and_avatar(): void
    {
        $response = $this->from('/signup')->post('/signup', [
            'student_id' => '21-00777',
            'first_name' => 'No',
            'last_name' => 'Avatar',
            'email' => 'no.avatar@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Technology',
            'year' => '1st',
            'block' => '1',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'terms' => '1',
            'signature' => $this->validSignatureDataUrl(),
            'signature_consent' => '1',
        ]);

        $response->assertRedirect('/signup');
        $response->assertSessionHasErrors(['username', 'avatar_seed']);
    }

    public function test_registration_rejects_reserved_and_invalid_usernames(): void
    {
        $response = $this->from('/signup')->post('/signup', [
            'student_id' => '21-00888',
            'first_name' => 'Admin',
            'last_name' => 'Imposter',
            'email' => 'imposter@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Technology',
            'year' => '1st',
            'block' => '1',
            'username' => 'admin',
            'avatar_seed' => 'Felix',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'terms' => '1',
            'signature' => $this->validSignatureDataUrl(),
            'signature_consent' => '1',
        ]);

        $response->assertRedirect('/signup');
        $response->assertSessionHasErrors(['username']);
    }

    public function test_guest_can_check_username_availability_endpoint(): void
    {
        $response = $this->getJson('/signup/check-username?username=brand_new_student');
        $response->assertOk();
        $response->assertJson([
            'available' => true,
        ]);
    }

    public function test_newly_registered_student_signature_works_in_attendance_sheet_export(): void
    {
        $sigData = $this->validSignatureDataUrl(300, 100);

        $this->post('/signup', [
            'student_id' => '26-09876',
            'first_name' => 'Erika',
            'last_name' => 'ExportTest',
            'middle_name' => 'M',
            'email' => 'erika.export@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Technology',
            'year' => '2nd',
            'block' => '1',
            'username' => 'erikaexport',
            'avatar_seed' => 'Felix',
            'password' => 'SecurePass123',
            'password_confirmation' => 'SecurePass123',
            'terms' => '1',
            'signature' => $sigData,
            'signature_consent' => '1',
        ]);

        $user = User::where('email', 'erika.export@sorsu.edu.ph')->first();
        $student = Student::where('email', 'erika.export@sorsu.edu.ph')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($student);

        // Create an event with attendance recorded for Erika
        $ay = AcademicYear::firstOrCreate(['label' => '2026-2027'], ['year_start' => 2026, 'year_end' => 2027, 'is_active' => true]);
        $event = Event::create([
            'academic_year_id' => $ay->id,
            'created_by' => $user->id,
            'name' => 'General Assembly 2026',
            'event_type' => 'assembly',
            'organizer' => 'Computing Society',
            'event_date' => '2026-11-05',
            'starts_at' => '2026-11-05 08:00:00',
            'ends_at' => '2026-11-05 17:00:00',
            'venue_name' => 'Sorsogon Campus',
            'venue_address' => 'Bulan, Sorsogon',
            'is_published' => true,
            'status' => 'published',
            'attendance_enabled' => true,
        ]);

        $session = AttendanceSession::create([
            'event_id' => $event->id,
            'type' => 'morning_in',
            'opens_at' => '08:00',
            'closes_at' => '12:00',
        ]);

        AttendanceRecord::create([
            'event_id' => $event->id,
            'attendance_session_id' => $session->id,
            'qr_token' => bin2hex(random_bytes(24)),
            'participant_type' => 'user',
            'participant_id' => $user->id,
            'user_id' => $user->id,
            'action' => 'in',
            'status' => 'present',
            'scanned_at' => now(),
        ]);

        // Generate attendance sheet using AttendanceSheetService
        $service = app(AttendanceSheetService::class);
        $check = $service->check($event);
        $this->assertEquals(1, $check['rows']->count());
        $this->assertEquals(0, $check['missing']->count());
        $this->assertTrue($check['signatures']->has($user->id));

        [$issuance, $pdf, $missing] = $service->issue($event, $user);
        $this->assertNotEmpty($pdf);
        $this->assertEquals(1, $issuance->attendee_count);
        $this->assertEquals(0, $issuance->missing_signature_count);
        $this->assertStringStartsWith('%PDF', $pdf);
    }

    public function test_signature_storage_failure_rolls_back_account_creation(): void
    {
        // Mock SignatureVaultService to throw during store()
        $this->mock(SignatureVaultService::class, function ($mock) {
            $mock->shouldReceive('normalize')->andReturn(['bytes' => 'pngdata', 'width' => 100, 'height' => 40]);
            $mock->shouldReceive('store')->andThrow(new \RuntimeException('Vault disk failure'));
        });

        try {
            $this->post('/signup', [
                'student_id' => '21-00991',
                'first_name' => 'Failed',
                'last_name' => 'Persistence',
                'email' => 'failed.persistence@sorsu.edu.ph',
                'program' => 'Bachelor of Science in Information Technology',
                'year' => '1st',
                'block' => '1',
                'username' => 'failedpersist',
                'avatar_seed' => 'Felix',
                'password' => 'SecurePass123',
                'password_confirmation' => 'SecurePass123',
                'terms' => '1',
                'signature' => $this->validSignatureDataUrl(),
                'signature_consent' => '1',
            ]);
        } catch (\RuntimeException $e) {
            $this->assertEquals('Vault disk failure', $e->getMessage());
        }

        // Entire registration rolled back, leaving no orphaned student or user account
        $this->assertDatabaseMissing('students', ['email' => 'failed.persistence@sorsu.edu.ph']);
        $this->assertDatabaseMissing('users', ['email' => 'failed.persistence@sorsu.edu.ph']);
        $this->assertDatabaseMissing('student_signatures', ['user_id' => 99999]);
    }
}
