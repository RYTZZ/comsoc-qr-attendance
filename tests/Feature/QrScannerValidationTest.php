<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Kiosk;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\QrCode;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrScannerValidationTest extends TestCase
{
    use RefreshDatabase;

    private function createEvent(User $creator): Event
    {
        $ay = AcademicYear::first() ?? AcademicYear::create([
            'label' => '2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'is_active' => true,
        ]);

        return Event::create([
            'academic_year_id' => $ay->id,
            'created_by' => $creator->id,
            'name' => 'General Assembly',
            'event_type' => 'assembly',
            'event_date' => now()->toDateString(),
            'is_published' => true,
            'status' => 'published',
            'attendance_enabled' => true,
        ]);
    }

    private function createSession(string $eventId): AttendanceSession
    {
        return AttendanceSession::create([
            'event_id' => $eventId,
            'type' => 'morning_in',
            'opens_at' => now()->subHour()->format('H:i:s'),
            'closes_at' => now()->addHour()->format('H:i:s'),
        ]);
    }

    public function test_scans_student_membership_qr_successfully(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $event = $this->createEvent($staff);
        $session = $this->createSession($event->id);

        $kiosk = Kiosk::create([
            'name' => 'Main Gate Kiosk',
            'identifier' => 'KIOSK-GATE-1',
            'type' => 'attendance',
            'is_active' => true,
            'assigned_staff_id' => $staff->id,
            'assigned_event_id' => $event->id,
        ]);

        $student = Student::create([
            'student_number' => '2023-0001',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'program' => 'BSCS',
            'year_level' => '2nd Year',
        ]);
        $ay = AcademicYear::first();
        $membership = Membership::create([
            'student_id' => $student->id,
            'academic_year_id' => $ay->id,
            'status' => 'active',
            'fee_paid' => true,
        ]);
        $qr = QrCode::create([
            'membership_id' => $membership->id,
            'token' => str_repeat('A', 48),
            'status' => 'active',
            'batch_number' => 1,
            'activated_at' => now(),
        ]);

        $response = $this->actingAs($staff)->postJson(route('kiosk.scan', $kiosk), [
            'token' => $qr->token,
            'event_id' => $event->id,
            'session_id' => $session->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'name' => 'Maria Santos',
            'participant_type' => 'student',
            'student_number' => '2023-0001',
        ]);

        $this->assertDatabaseHas('attendance_records', [
            'event_id' => $event->id,
            'attendance_session_id' => $session->id,
            'qr_token' => $qr->token,
            'participant_type' => 'student',
        ]);
    }

    public function test_scans_event_registration_qr_successfully(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $event = $this->createEvent($staff);
        $session = $this->createSession($event->id);

        $kiosk = Kiosk::create([
            'name' => 'Auditorium Kiosk',
            'identifier' => 'KIOSK-AUD-1',
            'type' => 'attendance',
            'is_active' => true,
            'assigned_staff_id' => $staff->id,
            'assigned_event_id' => $event->id,
        ]);

        $token = str_repeat('B', 48);
        $reg = EventRegistration::create([
            'event_id' => $event->id,
            'full_name' => 'John Guest',
            'email' => 'guest@example.com',
            'status' => 'approved',
            'qr_token' => $token,
            'qr_expires_at' => now()->addDays(2),
        ]);

        $response = $this->actingAs($staff)->postJson(route('kiosk.scan', $kiosk), [
            'token' => "https://example.com/checkin?token={$token}",
            'event_id' => $event->id,
            'session_id' => $session->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'name' => 'John Guest',
            'participant_type' => 'non_student',
        ]);

        $this->assertDatabaseHas('attendance_records', [
            'event_id' => $event->id,
            'attendance_session_id' => $session->id,
            'qr_token' => $token,
            'participant_type' => 'non_student',
        ]);
    }

    public function test_detects_duplicate_scan(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $event = $this->createEvent($staff);
        $session = $this->createSession($event->id);

        $kiosk = Kiosk::create([
            'name' => 'Auditorium Kiosk',
            'identifier' => 'KIOSK-AUD-2',
            'type' => 'attendance',
            'is_active' => true,
            'assigned_staff_id' => $staff->id,
            'assigned_event_id' => $event->id,
        ]);

        $token = str_repeat('C', 48);
        EventRegistration::create([
            'event_id' => $event->id,
            'full_name' => 'Duplicate Tester',
            'email' => 'dup@example.com',
            'status' => 'approved',
            'qr_token' => $token,
            'qr_expires_at' => now()->addDays(2),
        ]);

        $this->actingAs($staff)->postJson(route('kiosk.scan', $kiosk), [
            'token' => $token,
            'event_id' => $event->id,
            'session_id' => $session->id,
        ])->assertStatus(200);

        $secondResponse = $this->actingAs($staff)->postJson(route('kiosk.scan', $kiosk), [
            'token' => $token,
            'event_id' => $event->id,
            'session_id' => $session->id,
        ]);

        $secondResponse->assertStatus(422);
        $secondResponse->assertJson([
            'success' => false,
            'code' => 'duplicate',
        ]);
    }

    public function test_rejects_invalid_qr(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $event = $this->createEvent($staff);
        $session = $this->createSession($event->id);

        $kiosk = Kiosk::create([
            'name' => 'Auditorium Kiosk',
            'identifier' => 'KIOSK-AUD-3',
            'type' => 'attendance',
            'is_active' => true,
            'assigned_staff_id' => $staff->id,
            'assigned_event_id' => $event->id,
        ]);

        $response = $this->actingAs($staff)->postJson(route('kiosk.scan', $kiosk), [
            'token' => str_repeat('X', 48),
            'event_id' => $event->id,
            'session_id' => $session->id,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'code' => 'invalid_qr',
        ]);
    }

    public function test_rejects_scan_from_unauthorized_kiosk_terminal(): void
    {
        $kioskUserA = User::factory()->create(['role' => 'kiosk']);
        $kioskUserB = User::factory()->create(['role' => 'kiosk']);
        $event = $this->createEvent($kioskUserA);
        $session = $this->createSession($event->id);

        $kioskA = Kiosk::create([
            'user_id' => $kioskUserA->id,
            'name' => 'Kiosk A',
            'identifier' => 'KIOSK-A',
            'type' => 'attendance',
            'is_active' => true,
            'assigned_staff_id' => $kioskUserA->id,
            'assigned_event_id' => $event->id,
        ]);

        $kioskB = Kiosk::create([
            'user_id' => $kioskUserB->id,
            'name' => 'Kiosk B',
            'identifier' => 'KIOSK-B',
            'type' => 'attendance',
            'is_active' => true,
            'assigned_staff_id' => $kioskUserB->id,
            'assigned_event_id' => $event->id,
        ]);

        $response = $this->actingAs($kioskUserA)->postJson(route('kiosk.scan', $kioskB), [
            'token' => str_repeat('X', 48),
            'event_id' => $event->id,
            'session_id' => $session->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_students_cannot_access_kiosk_scan_endpoint(): void
    {
        $studentUser = User::factory()->create(['role' => 'student']);
        $event = $this->createEvent($studentUser);
        $session = $this->createSession($event->id);

        $kiosk = Kiosk::create([
            'name' => 'Kiosk Live',
            'identifier' => 'KIOSK-LIVE',
            'type' => 'attendance',
            'is_active' => true,
            'assigned_staff_id' => $studentUser->id,
            'assigned_event_id' => $event->id,
        ]);

        $response = $this->actingAs($studentUser)->postJson(route('kiosk.scan', $kiosk), [
            'token' => str_repeat('X', 48),
            'event_id' => $event->id,
            'session_id' => $session->id,
        ]);

        $response->assertStatus(403);
    }
}
