<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Event;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\Student;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\XpTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSupportInvestigationTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $studentUser;
    private Student $student;
    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $ay = AcademicYear::create([
            'label' => 'A.Y. 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'name' => 'Super Admin Officer',
            'email' => 'superadmin@sorsu.edu.ph',
            'password' => bcrypt('Password123!'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->student = Student::create([
            'student_number' => '2026-0001',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'program' => 'BS Information Technology',
            'year_level' => '3rd Year',
            'academic_year_id' => $ay->id,
            'is_active' => true,
        ]);

        $this->studentUser = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane@sorsu.edu.ph',
            'password' => bcrypt('Password123!'),
            'role' => 'member',
            'student_id' => $this->student->id,
            'is_active' => true,
        ]);

        $this->event = Event::create([
            'name' => 'General Assembly 2026',
            'academic_year_id' => $ay->id,
            'event_date' => now()->toDateString(),
            'status' => 'published',
            'is_published' => true,
            'created_by' => $this->superAdmin->id,
        ]);
    }

    public function test_super_admin_can_open_and_investigate_student_ticket_with_all_relations(): void
    {
        $session = AttendanceSession::create([
            'event_id' => $this->event->id,
            'type' => 'morning_in',
            'opens_at' => now()->subHour(),
            'closes_at' => now()->addHours(2),
            'is_active' => true,
        ]);

        $record = AttendanceRecord::create([
            'event_id' => $this->event->id,
            'attendance_session_id' => $session->id,
            'qr_token' => 'QR-ATTENDANCE-TEST-001',
            'participant_type' => 'student',
            'participant_id' => $this->student->id,
            'user_id' => $this->studentUser->id,
            'action' => 'in',
            'status' => 'present',
            'scanned_at' => now(),
        ]);

        $xp = XpTransaction::create([
            'user_id' => $this->studentUser->id,
            'amount' => 50,
            'activity_type' => 'event_attendance',
            'description' => 'Attended General Assembly',
        ]);

        $reward = Reward::create([
            'title' => 'Computing Society Lanyard',
            'description' => 'Official Lanyard',
            'points_cost' => 100,
            'category' => 'merchandise',
            'is_active' => true,
        ]);

        $redemption = RewardRedemption::create([
            'user_id' => $this->studentUser->id,
            'reward_id' => $reward->id,
            'points_spent' => 100,
            'claim_code' => 'CLM-TEST-001',
            'status' => 'pending',
            'item_title_snapshot' => $reward->title,
        ]);

        $ticket = SupportTicket::create([
            'ticket_number' => 'TKT-2026-INV01',
            'user_id' => $this->studentUser->id,
            'category' => 'attendance',
            'subject' => 'Dispute on Scan Record',
            'description' => 'My scan record is missing from the event.',
            'status' => 'open',
            'priority' => 'high',
            'event_id' => $this->event->id,
            'attendance_record_id' => $record->id,
            'xp_transaction_id' => $xp->id,
            'reward_redemption_id' => $redemption->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.support.show', $ticket));
        $response->assertStatus(200);
        $response->assertSee('TKT-2026-INV01');
        $response->assertSee('Dispute on Scan Record');
        $response->assertSee('Computing Society Lanyard');
    }
}
