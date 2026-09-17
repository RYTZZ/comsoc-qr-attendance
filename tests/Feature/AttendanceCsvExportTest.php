<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Kiosk;
use App\Models\Membership;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCsvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_csv_export_returns_valid_csv_and_sorted_data(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $ay = AcademicYear::create([
            'label' => '2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'is_active' => true,
        ]);

        $event = Event::create([
            'academic_year_id' => $ay->id,
            'created_by' => $admin->id,
            'name' => 'CICT Congress 2027',
            'event_type' => 'congress',
            'event_date' => now()->toDateString(),
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

        $kiosk = Kiosk::create([
            'name' => 'Main Gate Kiosk',
            'identifier' => 'KIOSK-MAIN-1',
            'type' => 'attendance',
            'is_active' => true,
            'assigned_staff_id' => $admin->id,
            'assigned_event_id' => $event->id,
        ]);

        $studentA = Student::create([
            'student_number' => '2023-0002',
            'first_name' => 'Charlie',
            'last_name' => 'Brown',
            'program' => 'BSIT',
            'year_level' => '2nd Year',
        ]);

        $studentB = Student::create([
            'student_number' => '2023-0001',
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'program' => 'BSIT',
            'year_level' => '1st Year',
        ]);

        $studentC = Student::create([
            'student_number' => '2023-0003',
            'first_name' => 'David',
            'last_name' => 'Adams',
            'program' => 'BSCS',
            'year_level' => '1st Year',
        ]);

        $guest = EventRegistration::create([
            'event_id' => $event->id,
            'full_name' => 'Zach Guest',
            'email' => 'zach@example.com',
            'program' => 'BSIS',
            'year_level' => '3rd Year',
            'status' => 'approved',
            'qr_token' => str_repeat('G', 48),
        ]);

        AttendanceRecord::create([
            'event_id' => $event->id,
            'attendance_session_id' => $session->id,
            'qr_token' => str_repeat('A', 48),
            'participant_type' => 'student',
            'participant_id' => $studentA->id,
            'action' => 'in',
            'status' => 'present',
            'kiosk_id' => $kiosk->id,
            'scanned_by' => $admin->id,
            'scanned_at' => now()->subMinutes(10),
        ]);

        AttendanceRecord::create([
            'event_id' => $event->id,
            'attendance_session_id' => $session->id,
            'qr_token' => str_repeat('B', 48),
            'participant_type' => 'student',
            'participant_id' => $studentB->id,
            'action' => 'in',
            'status' => 'present',
            'kiosk_id' => $kiosk->id,
            'scanned_by' => $admin->id,
            'scanned_at' => now()->subMinutes(20),
        ]);

        AttendanceRecord::create([
            'event_id' => $event->id,
            'attendance_session_id' => $session->id,
            'qr_token' => str_repeat('C', 48),
            'participant_type' => 'student',
            'participant_id' => $studentC->id,
            'action' => 'in',
            'status' => 'late',
            'kiosk_id' => $kiosk->id,
            'scanned_by' => $admin->id,
            'scanned_at' => now()->subMinutes(5),
        ]);

        AttendanceRecord::create([
            'event_id' => $event->id,
            'attendance_session_id' => $session->id,
            'qr_token' => str_repeat('G', 48),
            'participant_type' => 'non_student',
            'participant_id' => $guest->id,
            'action' => 'in',
            'status' => 'present',
            'kiosk_id' => $kiosk->id,
            'scanned_by' => $admin->id,
            'scanned_at' => now()->subMinutes(1),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.attendance', [
            'event_id' => $event->id,
            'export' => 'csv',
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('CICT_Congress_2027.csv', $response->headers->get('Content-Disposition'));

        $content = $response->streamedContent();

        $this->assertStringContainsString('"Student ID"', $content);
        $this->assertStringContainsString('"Full Name"', $content);
        $this->assertStringContainsString('Program', $content);
        $this->assertStringContainsString('"Year Level"', $content);

        $posBSCS = strpos($content, 'BSCS');
        $posBSIS = strpos($content, 'BSIS');
        $posBSIT_1st = strpos($content, 'Smith, Alice');
        $posBSIT_2nd = strpos($content, 'Brown, Charlie');

        $this->assertNotFalse($posBSCS);
        $this->assertNotFalse($posBSIS);
        $this->assertNotFalse($posBSIT_1st);
        $this->assertNotFalse($posBSIT_2nd);

        $this->assertTrue($posBSCS < $posBSIS);
        $this->assertTrue($posBSIS < $posBSIT_1st);
        $this->assertTrue($posBSIT_1st < $posBSIT_2nd);
    }
}
