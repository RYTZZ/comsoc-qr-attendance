<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Kiosk;
use App\Models\Membership;
use App\Models\QrCode;
use App\Models\Season;
use App\Models\Student;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\QrCodeService;
use App\Services\RewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberEventsAndTicketsTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_view_events_catalog_and_filter(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
        ]);

        $ay = AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'is_active' => true,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $event = Event::create([
            'academic_year_id' => $ay->id,
            'created_by' => $admin->id,
            'name' => 'COMSOC Tech Summit 2026',
            'event_type' => 'Summit',
            'event_date' => now()->addDays(5)->toDateString(),
            'venue_name' => 'SSU Bulan Auditorium',
            'is_published' => true,
            'status' => 'registration_open',
            'reward_xp' => 100,
            'reward_points' => 30,
        ]);

        $response = $this->actingAs($user)->get(route('events.index'));
        $response->assertStatus(200);
        $response->assertSee('COMSOC Tech Summit 2026');
        $response->assertSee('SSU Bulan Auditorium');
        $response->assertDontSee('<select', false);
    }

    public function test_member_can_view_event_details_and_register(): void
    {
        $ay = AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'is_active' => true,
        ]);

        $student = Student::create([
            'student_number' => '21-00888',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'maria@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Technology',
            'year_level' => '3rd Year',
            'block' => '1',
        ]);

        $user = User::factory()->create([
            'role' => 'student',
            'email' => 'maria@sorsu.edu.ph',
            'student_id' => $student->id,
            'is_active' => true,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $event = Event::create([
            'academic_year_id' => $ay->id,
            'created_by' => $admin->id,
            'name' => 'Annual Coding Hackathon',
            'event_type' => 'Competition',
            'event_date' => now()->addDays(3)->toDateString(),
            'venue_name' => 'SSU Computer Lab 1',
            'is_published' => true,
            'status' => 'registration_open',
            'reward_xp' => 150,
            'reward_points' => 50,
        ]);

        $detailResponse = $this->actingAs($user)->get(route('events.show', $event));
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('Annual Coding Hackathon');

        $joinResponse = $this->actingAs($user)->post(route('events.join', $event), [
            'tshirt_size' => 'M',
            'food_restrictions' => 'None',
        ]);

        $joinResponse->assertRedirect();
        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'approved',
            'tshirt_size' => 'M',
        ]);

        $ticket = EventRegistration::where('event_id', $event->id)->first();
        $this->assertNotNull($ticket->ticket_code);
        $this->assertNotNull($ticket->qr_token);

        $ticketPageResponse = $this->actingAs($user)->get(route('tickets.show', $ticket));
        $ticketPageResponse->assertStatus(200);
        $ticketPageResponse->assertSee($ticket->ticket_code);
    }

    public function test_member_cannot_register_twice_for_same_event(): void
    {
        $ay = AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $event = Event::create([
            'academic_year_id' => $ay->id,
            'created_by' => $admin->id,
            'name' => 'Git & GitHub Workshop',
            'event_date' => now()->addDays(2)->toDateString(),
            'is_published' => true,
            'status' => 'registration_open',
        ]);

        EventRegistration::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'full_name' => $user->name,
            'email' => $user->email,
            'status' => 'approved',
            'tshirt_size' => 'L',
            'food_restrictions' => 'None',
        ]);

        $secondJoin = $this->actingAs($user)->post(route('events.join', $event), [
            'tshirt_size' => 'L',
        ]);

        $secondJoin->assertSessionHas('error');
        $this->assertEquals(1, EventRegistration::where('event_id', $event->id)->count());
    }

    public function test_qr_attendance_scan_awards_xp_and_points_to_member(): void
    {
        $season = Season::create([
            'name' => 'Season 2026-2027',
            'academic_year_label' => 'AY 2026-2027',
            'start_date' => '2026-08-01',
            'end_date' => '2027-07-31',
            'is_active' => true,
        ]);

        $ay = AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'is_active' => true,
        ]);

        $student = Student::create([
            'student_number' => '21-00555',
            'first_name' => 'Carlos',
            'last_name' => 'Mendoza',
            'email' => 'carlos@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Technology',
            'year_level' => '1st Year',
            'block' => '2',
        ]);

        $memberUser = User::factory()->create([
            'role' => 'student',
            'email' => 'carlos@sorsu.edu.ph',
            'student_id' => $student->id,
            'is_active' => true,
        ]);

        $staffUser = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $membership = Membership::create([
            'student_id' => $student->id,
            'academic_year_id' => $ay->id,
            'status' => 'active',
        ]);

        $qr = QrCode::create([
            'membership_id' => $membership->id,
            'token' => 'sample_token_student_123',
            'status' => 'active',
            'batch_number' => 1,
            'activated_at' => now(),
        ]);

        $event = Event::create([
            'academic_year_id' => $ay->id,
            'created_by' => $staffUser->id,
            'name' => 'General Assembly 2026',
            'event_date' => now()->toDateString(),
            'is_published' => true,
            'status' => 'ongoing',
            'reward_xp' => 75,
            'reward_points' => 25,
        ]);

        $session = AttendanceSession::create([
            'event_id' => $event->id,
            'name' => 'Morning In',
            'type' => 'morning_in',
            'opens_at' => '07:00',
            'closes_at' => '23:59',
            'order' => 1,
            'is_active' => true,
        ]);

        $kiosk = Kiosk::create([
            'name' => 'Main Gate Kiosk',
            'identifier' => 'KIOSK-MAIN',
            'status' => 'active',
        ]);

        $attendanceService = app(AttendanceService::class);
        $result = $attendanceService->scan($qr->token, $event, $session, $kiosk, $staffUser);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('attendance_records', [
            'event_id' => $event->id,
            'attendance_session_id' => $session->id,
            'participant_id' => $student->id,
            'action' => 'in',
        ]);

        $this->assertDatabaseHas('xp_transactions', [
            'user_id' => $memberUser->id,
            'amount' => 75,
            'activity_type' => 'event_attendance',
        ]);

        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $memberUser->id,
            'amount' => 25,
            'transaction_type' => 'event_attendance',
        ]);

        $this->assertDatabaseHas('member_season_stats', [
            'user_id' => $memberUser->id,
            'season_id' => $season->id,
            'season_xp' => 75,
            'redeemable_points' => 25,
        ]);
    }

    public function test_ticket_qr_code_can_be_scanned_for_attendance(): void
    {
        $ay = AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'is_active' => true,
        ]);

        $student = Student::create([
            'student_number' => '21-00777',
            'first_name' => 'Elena',
            'last_name' => 'Reyes',
            'email' => 'elena@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Computer Science',
            'year_level' => '2nd Year',
            'block' => '1',
        ]);

        $user = User::factory()->create([
            'role' => 'student',
            'email' => 'elena@sorsu.edu.ph',
            'student_id' => $student->id,
            'is_active' => true,
        ]);

        $staffUser = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $event = Event::create([
            'academic_year_id' => $ay->id,
            'created_by' => $staffUser->id,
            'name' => 'Web Dev Bootcamp',
            'event_date' => now()->toDateString(),
            'is_published' => true,
            'status' => 'ongoing',
            'reward_xp' => 60,
            'reward_points' => 20,
        ]);

        $session = AttendanceSession::create([
            'event_id' => $event->id,
            'name' => 'Morning In',
            'type' => 'morning_in',
            'opens_at' => '07:00',
            'closes_at' => '23:59',
            'order' => 1,
            'is_active' => true,
        ]);

        $kiosk = Kiosk::create([
            'name' => 'Auditorium Kiosk',
            'identifier' => 'KIOSK-AUD',
            'status' => 'active',
        ]);

        $ticket = EventRegistration::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'full_name' => $user->name,
            'email' => $user->email,
            'status' => 'approved',
            'qr_token' => 'ticket_qr_token_special_456',
            'qr_expires_at' => now()->addDays(2),
        ]);

        $attendanceService = app(AttendanceService::class);
        $result = $attendanceService->scan($ticket->qr_token, $event, $session, $kiosk, $staffUser);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('attendance_records', [
            'event_id' => $event->id,
            'qr_token' => $ticket->qr_token,
        ]);

        $duplicateResult = $attendanceService->scan($ticket->qr_token, $event, $session, $kiosk, $staffUser);
        $this->assertFalse($duplicateResult['success']);
        $this->assertEquals('duplicate', $duplicateResult['code']);
    }
}
