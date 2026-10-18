<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Student;
use App\Models\User;
use App\Services\DynamicQrService;
use App\Services\EventAttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventSpecificDynamicQrSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $organizer;
    protected User $studentUser;
    protected Student $student;
    protected User $unregisteredStudentUser;
    protected Student $unregisteredStudent;
    protected AcademicYear $ay;
    protected Event $eventA;
    protected Event $eventB;
    protected AttendanceSession $sessionMorningIn;
    protected AttendanceSession $sessionMorningOut;
    protected DynamicQrService $dynamicQrService;
    protected EventAttendanceService $attendanceService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dynamicQrService = app(DynamicQrService::class);
        $this->attendanceService = app(EventAttendanceService::class);

        $this->ay = AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'is_active' => true,
        ]);

        $this->superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'is_eligible' => true,
        ]);

        $this->organizer = User::factory()->create([
            'role' => 'event_staff',
            'is_active' => true,
            'is_eligible' => true,
        ]);

        $this->student = Student::create([
            'student_number' => '21-00991',
            'first_name' => 'Carla',
            'last_name' => 'Reyes',
            'email' => 'carla@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Technology',
            'year_level' => '3rd Year',
        ]);

        $this->studentUser = User::factory()->create([
            'role' => 'student',
            'name' => 'Carla Reyes',
            'email' => 'carla@sorsu.edu.ph',
            'student_id' => $this->student->id,
            'student_number' => '21-00991',
            'is_active' => true,
            'is_eligible' => true,
        ]);

        $this->unregisteredStudent = Student::create([
            'student_number' => '21-00992',
            'first_name' => 'Mark',
            'last_name' => 'Santos',
            'email' => 'mark@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Computer Science',
            'year_level' => '2nd Year',
        ]);

        $this->unregisteredStudentUser = User::factory()->create([
            'role' => 'student',
            'name' => 'Mark Santos',
            'email' => 'mark@sorsu.edu.ph',
            'student_id' => $this->unregisteredStudent->id,
            'student_number' => '21-00992',
            'is_active' => true,
            'is_eligible' => true,
        ]);

        $this->eventA = Event::create([
            'academic_year_id' => $this->ay->id,
            'created_by' => $this->superAdmin->id,
            'name' => 'Tech Innovation Summit 2026',
            'event_type' => 'Summit',
            'event_date' => now()->toDateString(),
            'starts_at' => now()->setTime(8, 0),
            'ends_at' => now()->setTime(17, 0),
            'venue_name' => 'Grand Hall',
            'is_published' => true,
            'status' => 'published',
            'requires_registration' => true,
            'reward_xp' => 100,
            'reward_points' => 50,
        ]);

        $this->eventB = Event::create([
            'academic_year_id' => $this->ay->id,
            'created_by' => $this->superAdmin->id,
            'name' => 'Cybersecurity Workshop 2026',
            'event_type' => 'Workshop',
            'event_date' => now()->toDateString(),
            'starts_at' => now()->setTime(8, 0),
            'ends_at' => now()->setTime(17, 0),
            'venue_name' => 'Lab B',
            'is_published' => true,
            'status' => 'published',
            'requires_registration' => true,
            'reward_xp' => 80,
            'reward_points' => 40,
        ]);

        // Attach organizer to Event A
        $this->eventA->organizers()->attach($this->organizer->id);

        // Morning IN session for Event A (always open during test)
        $this->sessionMorningIn = AttendanceSession::create([
            'event_id' => $this->eventA->id,
            'name' => 'Morning Check-In',
            'type' => 'morning_in',
            'opens_at' => '00:00:00',
            'closes_at' => '23:59:59',
            'is_active' => true,
        ]);

        // Morning OUT session for Event A
        $this->sessionMorningOut = AttendanceSession::create([
            'event_id' => $this->eventA->id,
            'name' => 'Morning Check-Out',
            'type' => 'morning_out',
            'opens_at' => '00:00:00',
            'closes_at' => '23:59:59',
            'is_active' => true,
        ]);
    }

    public function test_only_registered_or_joined_students_can_obtain_event_pass(): void
    {
        // 1. Registered student has ticket
        $ticket = EventRegistration::create([
            'event_id' => $this->eventA->id,
            'user_id' => $this->studentUser->id,
            'full_name' => $this->studentUser->name,
            'email' => $this->studentUser->email,
            'status' => 'approved',
            'ticket_code' => 'TCK-REG1234',
        ]);

        $responseRegistered = $this->actingAs($this->studentUser)->get(route('tickets.show', $ticket));
        $responseRegistered->assertOk();
        $responseRegistered->assertSee('Event Dynamic Attendance Pass');
        $responseRegistered->assertSee('Manual Token');
        $responseRegistered->assertDontSee('Save QR Image');
        $responseRegistered->assertDontSee('Download QR Pass');

        // 2. Unregistered student cannot access this ticket
        $responseUnregistered = $this->actingAs($this->unregisteredStudentUser)->get(route('tickets.show', $ticket));
        $responseUnregistered->assertForbidden();
    }

    public function test_unregistered_student_qr_scan_is_rejected_on_server(): void
    {
        // Generate dynamic token without registration
        $tokenData = $this->dynamicQrService->generateTokenForUser($this->unregisteredStudentUser, $this->eventA);

        $result = $this->attendanceService->processScan(
            event: $this->eventA,
            session: $this->sessionMorningIn,
            qrTokenPayload: $tokenData['qr_payload'],
            organizer: $this->organizer
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('not_registered', $result['code']);
        $this->assertEquals('Ineligible', $result['result_type']);
        $this->assertDatabaseMissing('attendance_records', [
            'event_id' => $this->eventA->id,
            'user_id' => $this->unregisteredStudentUser->id,
        ]);
    }

    public function test_passes_cannot_be_used_for_another_event(): void
    {
        // Student registers for Event B, NOT Event A
        EventRegistration::create([
            'event_id' => $this->eventB->id,
            'user_id' => $this->studentUser->id,
            'full_name' => $this->studentUser->name,
            'email' => $this->studentUser->email,
            'status' => 'approved',
            'ticket_code' => 'TCK-EVTB1234',
        ]);

        // Token issued for Event B
        $tokenDataEventB = $this->dynamicQrService->generateTokenForUser($this->studentUser, $this->eventB);

        // Attempt scan at Event A
        $result = $this->attendanceService->processScan(
            event: $this->eventA,
            session: $this->sessionMorningIn,
            qrTokenPayload: $tokenDataEventB['qr_payload'],
            organizer: $this->organizer
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('event_mismatch', $result['code']);
        $this->assertEquals('Wrong event', $result['result_type']);
        $this->assertDatabaseMissing('attendance_records', [
            'event_id' => $this->eventA->id,
            'user_id' => $this->studentUser->id,
        ]);
    }

    public function test_tokens_expire_after_30_second_policy_and_are_rejected(): void
    {
        EventRegistration::create([
            'event_id' => $this->eventA->id,
            'user_id' => $this->studentUser->id,
            'full_name' => $this->studentUser->name,
            'email' => $this->studentUser->email,
            'status' => 'approved',
            'ticket_code' => 'TCK-EXP1234',
        ]);

        $tokenData = $this->dynamicQrService->generateTokenForUser($this->studentUser, $this->eventA);

        // Fast-forward past 30 seconds + 5s clock skew grace window (36 seconds)
        Carbon::setTestNow(now()->addSeconds(36));

        $result = $this->attendanceService->processScan(
            event: $this->eventA,
            session: $this->sessionMorningIn,
            qrTokenPayload: $tokenData['qr_payload'],
            organizer: $this->organizer
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('expired_token', $result['code']);
        $this->assertEquals('Expired', $result['result_type']);
        $this->assertDatabaseMissing('attendance_records', [
            'event_id' => $this->eventA->id,
            'user_id' => $this->studentUser->id,
        ]);

        Carbon::setTestNow(); // Reset time
    }

    public function test_scanning_does_not_permanently_disable_pass_and_next_token_refreshes_normally(): void
    {
        $ticket = EventRegistration::create([
            'event_id' => $this->eventA->id,
            'user_id' => $this->studentUser->id,
            'full_name' => $this->studentUser->name,
            'email' => $this->studentUser->email,
            'status' => 'approved',
            'ticket_code' => 'TCK-CYCLE123',
        ]);

        // First scan check-in
        $token1 = $this->dynamicQrService->generateTokenForUser($this->studentUser, $this->eventA);
        $result1 = $this->attendanceService->processScan(
            event: $this->eventA,
            session: $this->sessionMorningIn,
            qrTokenPayload: $token1['qr_payload'],
            organizer: $this->organizer
        );

        $this->assertTrue($result1['success']);
        $this->assertDatabaseHas('attendance_records', [
            'event_id' => $this->eventA->id,
            'attendance_session_id' => $this->sessionMorningIn->id,
            'user_id' => $this->studentUser->id,
            'action' => 'in',
        ]);

        // Student requests refresh 30 seconds later for check-out
        $refreshRes = $this->actingAs($this->studentUser)->post(route('tickets.refresh_qr', $ticket));
        $refreshRes->assertOk();
        $refreshData = $refreshRes->json();
        $this->assertTrue($refreshData['success']);
        $this->assertNotEmpty($refreshData['payload']);
        $this->assertNotEquals($token1['qr_payload'], $refreshData['payload']);

        // Check-out scan with next token succeeds
        $result2 = $this->attendanceService->processScan(
            event: $this->eventA,
            session: $this->sessionMorningOut,
            qrTokenPayload: $refreshData['payload'],
            organizer: $this->organizer
        );

        $this->assertTrue($result2['success']);
        $this->assertDatabaseHas('attendance_records', [
            'event_id' => $this->eventA->id,
            'attendance_session_id' => $this->sessionMorningOut->id,
            'user_id' => $this->studentUser->id,
            'action' => 'out',
        ]);
    }

    public function test_repeated_scans_do_not_duplicate_attendance_or_rewards(): void
    {
        EventRegistration::create([
            'event_id' => $this->eventA->id,
            'user_id' => $this->studentUser->id,
            'full_name' => $this->studentUser->name,
            'email' => $this->studentUser->email,
            'status' => 'approved',
            'ticket_code' => 'TCK-DUP123',
        ]);

        $token1 = $this->dynamicQrService->generateTokenForUser($this->studentUser, $this->eventA);

        // First scan succeeds
        $result1 = $this->attendanceService->processScan(
            event: $this->eventA,
            session: $this->sessionMorningIn,
            qrTokenPayload: $token1['qr_payload'],
            organizer: $this->organizer
        );
        $this->assertTrue($result1['success']);

        // Replay of identical token is rejected (already consumed)
        $replayResult = $this->attendanceService->processScan(
            event: $this->eventA,
            session: $this->sessionMorningIn,
            qrTokenPayload: $token1['qr_payload'],
            organizer: $this->organizer
        );
        $this->assertFalse($replayResult['success']);
        $this->assertEquals('already_used', $replayResult['code']);

        // New token scanned for same session is rejected as duplicate session check-in
        $token2 = $this->dynamicQrService->generateTokenForUser($this->studentUser, $this->eventA);
        $duplicateResult = $this->attendanceService->processScan(
            event: $this->eventA,
            session: $this->sessionMorningIn,
            qrTokenPayload: $token2['qr_payload'],
            organizer: $this->organizer
        );
        $this->assertFalse($duplicateResult['success']);
        $this->assertEquals('duplicate_scan', $duplicateResult['code']);

        // Only 1 record created
        $this->assertEquals(1, AttendanceRecord::where('event_id', $this->eventA->id)->where('user_id', $this->studentUser->id)->count());
    }

    public function test_check_out_requires_valid_check_in_first(): void
    {
        EventRegistration::create([
            'event_id' => $this->eventA->id,
            'user_id' => $this->studentUser->id,
            'full_name' => $this->studentUser->name,
            'email' => $this->studentUser->email,
            'status' => 'approved',
            'ticket_code' => 'TCK-SEQ123',
        ]);

        $token = $this->dynamicQrService->generateTokenForUser($this->studentUser, $this->eventA);

        // Attempting Morning Out without Morning In
        $result = $this->attendanceService->processScan(
            event: $this->eventA,
            session: $this->sessionMorningOut,
            qrTokenPayload: $token['qr_payload'],
            organizer: $this->organizer
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('no_time_in_record', $result['code']);
    }

    public function test_cancelled_or_ineligible_registrations_are_rejected(): void
    {
        // Cancelled registration
        EventRegistration::create([
            'event_id' => $this->eventA->id,
            'user_id' => $this->studentUser->id,
            'full_name' => $this->studentUser->name,
            'email' => $this->studentUser->email,
            'status' => 'cancelled',
            'ticket_code' => 'TCK-CAN123',
        ]);

        $token = $this->dynamicQrService->generateTokenForUser($this->studentUser, $this->eventA);

        $result = $this->attendanceService->processScan(
            event: $this->eventA,
            session: $this->sessionMorningIn,
            qrTokenPayload: $token['qr_payload'],
            organizer: $this->organizer
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('not_registered', $result['code']);
    }

    public function test_tampered_or_counterfeit_token_is_rejected(): void
    {
        $tamperedPayload = base64_encode(json_encode([
            'v' => 1,
            'uid' => $this->studentUser->id,
            'eid' => $this->eventA->id,
            'exp' => now()->addMinutes(10)->timestamp,
            'hash' => 'fake_hash_12345',
        ]) . '::fake_signature');

        $result = $this->attendanceService->processScan(
            event: $this->eventA,
            session: $this->sessionMorningIn,
            qrTokenPayload: $tamperedPayload,
            organizer: $this->organizer
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('signature_mismatch', $result['code']);
    }

    public function test_member_dynamic_qr_view_and_refresh_respect_event_registration_states(): void
    {
        // Case A: Unregistered student viewing event-scoped QR
        $resNotJoined = $this->actingAs($this->unregisteredStudentUser)->get(route('member.dynamic_qr', ['event_id' => $this->eventA->id]));
        $resNotJoined->assertOk();
        $resNotJoined->assertSee('No Event Attendance QR Available');
        $resNotJoined->assertSee("You haven't joined any eligible events or activities yet", false);
        $resNotJoined->assertSee('Browse Events & Activities', false);
        $resNotJoined->assertDontSee('id="qrcode-canvas"', false);

        // Case A refresh call is rejected
        $refreshNotJoined = $this->actingAs($this->unregisteredStudentUser)->postJson(route('member.dynamic_qr.refresh'), [
            'event_id' => $this->eventA->id,
        ]);
        $refreshNotJoined->assertStatus(403);
        $refreshNotJoined->assertJsonFragment([
            'message' => 'Join this event to access your Dynamic Attendance QR Code.',
        ]);

        // Case B: Registered and approved student
        EventRegistration::create([
            'event_id' => $this->eventA->id,
            'user_id' => $this->studentUser->id,
            'full_name' => $this->studentUser->name,
            'email' => $this->studentUser->email,
            'status' => 'approved',
            'ticket_code' => 'TCK-APPR123',
        ]);

        $resApproved = $this->actingAs($this->studentUser)->get(route('member.dynamic_qr', ['event_id' => $this->eventA->id]));
        $resApproved->assertOk();
        $resApproved->assertSee('Event Dynamic Attendance Pass');
        $resApproved->assertSee($this->eventA->name);
        $resApproved->assertSee('QR refreshes in');

        $refreshApproved = $this->actingAs($this->studentUser)->postJson(route('member.dynamic_qr.refresh'), [
            'event_id' => $this->eventA->id,
        ]);
        $refreshApproved->assertOk();
        $this->assertTrue($refreshApproved->json('success'));
        $this->assertNotEmpty($refreshApproved->json('payload'));
        $this->assertNotEmpty($refreshApproved->json('manual_token'));

        // Case C: Cancelled registration
        $cancelledUser = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'is_eligible' => true,
        ]);
        EventRegistration::create([
            'event_id' => $this->eventA->id,
            'user_id' => $cancelledUser->id,
            'full_name' => $cancelledUser->name,
            'email' => $cancelledUser->email,
            'status' => 'cancelled',
            'ticket_code' => 'TCK-CANC456',
        ]);

        $resCancelled = $this->actingAs($cancelledUser)->get(route('member.dynamic_qr', ['event_id' => $this->eventA->id]));
        $resCancelled->assertOk();
        $resCancelled->assertSee('Event Pass Inactive');
        $resCancelled->assertSee('Your registration for this event has been cancelled or rejected, or your member account is not in good standing.');
        $resCancelled->assertDontSee('id="qrcode-canvas"', false);

        $refreshCancelled = $this->actingAs($cancelledUser)->postJson(route('member.dynamic_qr.refresh'), [
            'event_id' => $this->eventA->id,
        ]);
        $refreshCancelled->assertStatus(403);
    }
}
