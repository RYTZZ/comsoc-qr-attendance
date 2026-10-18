<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\DynamicQrToken;
use App\Models\Event;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Services\DynamicQrService;
use App\Services\EventAttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicAttendancePassAndDirectoryTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->academicYear = AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'semester' => '1st',
            'is_active' => true,
        ]);
    }

    /**
     * Test ISSUE 1: Dynamic Attendance Pass generates unambiguous, human-friendly manual token.
     */
    public function test_dynamic_qr_pass_generates_and_displays_manual_token(): void
    {
        $student = Student::create([
            'student_number' => '2026-IT-0099',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'program' => 'BSIT',
            'year_level' => '3rd Year',
            'block' => 'A',
        ]);

        $user = User::factory()->create([
            'role' => 'student',
            'student_id' => $student->id,
            'is_active' => true,
            'is_eligible' => true,
        ]);

        $admin = User::factory()->create(['role' => 'super_admin']);
        $event = Event::create([
            'academic_year_id' => $this->academicYear->id,
            'created_by' => $admin->id,
            'name' => 'General Assembly 2026',
            'status' => 'published',
            'event_date' => Carbon::today()->toDateString(),
        ]);

        \App\Models\EventRegistration::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'full_name' => $user->name,
            'email' => $user->email,
            'status' => 'approved',
            'registered_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('member.dynamic_qr'));
        $response->assertStatus(200);

        // Check required prompt & labels
        $response->assertSee('Event Dynamic Attendance Pass');
        $response->assertSee('Manual Attendance Token');
        $response->assertSee("Enter this token in the manual attendance lookup.");
        $response->assertSee('Copy');

        // Check token generation in database
        $tokenRecord = DynamicQrToken::where('user_id', $user->id)->latest()->first();
        $this->assertNotNull($tokenRecord);
        $this->assertNotNull($tokenRecord->manual_token);
        $this->assertEquals(6, strlen($tokenRecord->manual_token));
        // Verify unambiguous characters (no 0, O, 1, I)
        $this->assertDoesNotMatch(['0', 'O', '1', 'I'], $tokenRecord->manual_token);

        // Formatted with hyphen e.g. ABC-DEF
        $formatted = substr($tokenRecord->manual_token, 0, 3) . '-' . substr($tokenRecord->manual_token, 3, 3);
        $response->assertSee($formatted);
    }

    /**
     * Test dynamic QR shows no pass notification when student has no joined events.
     */
    public function test_dynamic_qr_shows_no_pass_notification_when_no_joined_events(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'is_eligible' => true,
        ]);

        $response = $this->actingAs($user)->get(route('member.dynamic_qr'));
        $response->assertStatus(200);
        $response->assertSee('No Event Attendance QR Available');
        $response->assertSee("You haven't joined any eligible events or activities yet", false);
        $response->assertSee(route('events.index'));

        // Refresh endpoint must reject unregistered student
        $refreshRes = $this->actingAs($user)->postJson(route('member.dynamic_qr.refresh'));
        $refreshRes->assertStatus(403);
    }

    /**
     * Test dynamic QR refresh returns synchronized payload and manual token for registered student.
     */
    public function test_dynamic_qr_refresh_returns_synchronized_manual_token(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $event = Event::create([
            'academic_year_id' => $this->academicYear->id,
            'created_by' => $admin->id,
            'name' => 'General Assembly 2026',
            'status' => 'published',
            'event_date' => Carbon::today()->toDateString(),
        ]);

        $user = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'is_eligible' => true,
        ]);

        \App\Models\EventRegistration::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'full_name' => $user->name,
            'email' => $user->email,
            'status' => 'approved',
            'registered_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson(route('member.dynamic_qr.refresh'), [
            'event_id' => $event->id,
        ]);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'payload',
            'expires_at',
            'ttl',
            'manual_token',
            'formatted_manual_token',
        ]);

        $data = $response->json();
        $this->assertEquals(6, strlen($data['manual_token']));
        $this->assertEquals(7, strlen($data['formatted_manual_token']));
        $this->assertStringContainsString('-', $data['formatted_manual_token']);

        // Verify database holds this manual token
        $this->assertDatabaseHas('dynamic_qr_tokens', [
            'user_id' => $user->id,
            'manual_token' => $data['manual_token'],
            'is_consumed' => false,
        ]);
    }

    /**
     * Test organizer can validate attendance using manual token (with/without hyphen, lowercase).
     */
    public function test_organizer_can_validate_manual_attendance_token_with_various_formats(): void
    {
        $organizer = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'is_eligible' => true,
        ]);

        $studentUser = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'is_eligible' => true,
        ]);

        $event = Event::create([
            'academic_year_id' => $this->academicYear->id,
            'created_by' => $organizer->id,
            'name' => 'General Assembly 2026',
            'status' => 'published',
            'event_date' => Carbon::today()->toDateString(),
        ]);
        $event->organizers()->attach($organizer->id);

        $session = AttendanceSession::create([
            'event_id' => $event->id,
            'label' => 'Morning IN',
            'type' => 'morning_in',
            'opens_at' => Carbon::now()->subMinutes(10)->toTimeString(),
            'closes_at' => Carbon::now()->addMinutes(20)->toTimeString(),
            'late_threshold' => Carbon::now()->addMinutes(10)->toTimeString(),
            'exp_awarded' => 50,
        ]);

        // Generate dynamic token for student
        $qrService = app(DynamicQrService::class);
        $tokenData = $qrService->generateTokenForUser($studentUser);
        $rawManual = $tokenData['manual_token'];
        $hyphenated = $tokenData['formatted_manual_token'];

        // Scan via OrganizerScannerController using hyphenated format
        $response1 = $this->actingAs($organizer)->postJson(route('organizer.scanner.scan'), [
            'event_id' => $event->id,
            'session_id' => $session->id,
            'qr_payload' => $hyphenated,
        ]);

        $response1->assertStatus(200);
        $response1->assertJson([
            'success' => true,
            'result_type' => 'Accepted',
        ]);

        // Verify Attendance Record was created
        $this->assertDatabaseHas('attendance_records', [
            'event_id' => $event->id,
            'attendance_session_id' => $session->id,
            'user_id' => $studentUser->id,
            'action' => 'in',
            'status' => 'present',
        ]);

        // Verify token is now consumed (anti-replay)
        $this->assertDatabaseHas('dynamic_qr_tokens', [
            'manual_token' => $rawManual,
            'is_consumed' => true,
        ]);

        // Attempting to reuse same token must be rejected as Duplicate
        $response2 = $this->actingAs($organizer)->postJson(route('organizer.scanner.scan'), [
            'event_id' => $event->id,
            'session_id' => $session->id,
            'qr_payload' => $rawManual,
        ]);

        $response2->assertStatus(422);
        $response2->assertJson([
            'success' => false,
            'result_type' => 'Duplicate',
        ]);
    }

    /**
     * Test expired manual tokens are rejected.
     */
    public function test_expired_manual_attendance_token_is_rejected(): void
    {
        $organizer = User::factory()->create(['role' => 'super_admin', 'is_active' => true, 'is_eligible' => true]);
        $studentUser = User::factory()->create(['role' => 'student', 'is_active' => true, 'is_eligible' => true]);

        $event = Event::create([
            'academic_year_id' => $this->academicYear->id,
            'created_by' => $organizer->id,
            'name' => 'General Assembly 2026',
            'status' => 'published',
            'event_date' => Carbon::today()->toDateString(),
        ]);
        $session = AttendanceSession::create([
            'event_id' => $event->id,
            'label' => 'Morning IN',
            'type' => 'morning_in',
            'opens_at' => Carbon::now()->subMinutes(10)->toTimeString(),
            'closes_at' => Carbon::now()->addMinutes(20)->toTimeString(),
        ]);

        // Create expired token record
        $expiredToken = DynamicQrToken::create([
            'user_id' => $studentUser->id,
            'token_hash' => hash('sha256', 'test-expired'),
            'manual_token' => '7K9MP4',
            'expires_at' => Carbon::now()->subMinutes(5),
            'is_consumed' => false,
        ]);

        $response = $this->actingAs($organizer)->postJson(route('organizer.scanner.scan'), [
            'event_id' => $event->id,
            'session_id' => $session->id,
            'qr_payload' => '7K9-MP4',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'result_type' => 'Expired',
        ]);
    }

    /**
     * Test ISSUE 2: Member Directory loads successfully with Programs schema.
     */
    public function test_member_directory_loads_successfully_without_database_error(): void
    {
        $viewer = User::factory()->create(['role' => 'student', 'is_active' => true]);

        // Create programs in DB (matches real table schema: id, name, abbreviation, display_name, is_active, sort_order)
        Program::create([
            'name' => 'Bachelor of Science in Information Technology',
            'abbreviation' => 'BSIT',
            'display_name' => 'BS Information Technology',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        Program::create([
            'name' => 'Bachelor of Science in Computer Science',
            'abbreviation' => 'BSCS',
            'display_name' => 'BS Computer Science',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $response = $this->actingAs($viewer)->get(route('directory.index'));

        $response->assertStatus(200);
        $response->assertSee('Member Directory');
        $response->assertSee('BS Information Technology');
        $response->assertSee('BS Computer Science');
    }

    /**
     * Test Member Directory with empty results does not crash.
     */
    public function test_member_directory_handles_empty_member_results_cleanly(): void
    {
        $viewer = User::factory()->create(['role' => 'student', 'is_active' => true]);

        // Query with non-matching search
        $response = $this->actingAs($viewer)->get(route('directory.index', ['search' => 'NonExistentXYZ999']));

        $response->assertStatus(200);
        $response->assertSee('No Members Found');
    }

    private function assertDoesNotMatch(array $needles, string $haystack): void
    {
        foreach ($needles as $n) {
            $this->assertStringNotContainsString((string)$n, $haystack);
        }
    }
}
