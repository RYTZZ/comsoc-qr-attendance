<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\AnnouncementReceipt;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\CollaborationProject;
use App\Models\Event;
use App\Models\EventFeedback;
use App\Models\EventRegistration;
use App\Models\ProjectApplication;
use App\Models\ProjectMember;
use App\Models\ProjectMilestone;
use App\Models\Student;
use App\Models\SupportTicket;
use App\Models\SupportTicketHistory;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Models\VolunteerApplication;
use App\Models\VolunteerOpportunity;
use App\Services\ExpLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComsocSixModulesIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $memberA;
    private User $memberB;
    private User $admin;
    private AcademicYear $academicYear;
    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->academicYear = AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'is_active' => true,
        ]);

        $studentA = Student::create([
            'student_number' => '21-00101',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@sorsu.edu.ph',
            'program' => 'BSIT',
            'year_level' => '3',
            'block' => 'A',
        ]);

        $studentB = Student::create([
            'student_number' => '21-00102',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'maria@sorsu.edu.ph',
            'program' => 'BSIS',
            'year_level' => '2',
            'block' => 'B',
        ]);

        $this->memberA = User::create([
            'name' => 'Juan Dela Cruz',
            'username' => 'juandc',
            'email' => 'juan@sorsu.edu.ph',
            'password' => bcrypt('Secret123!'),
            'role' => 'student',
            'student_id' => $studentA->id,
            'is_active' => true,
        ]);

        $this->memberB = User::create([
            'name' => 'Maria Santos',
            'username' => 'mariabs',
            'email' => 'maria@sorsu.edu.ph',
            'password' => bcrypt('Secret123!'),
            'role' => 'student',
            'student_id' => $studentB->id,
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'COMSOC Officer',
            'username' => 'admin_officer',
            'email' => 'officer@sorsu.edu.ph',
            'password' => bcrypt('Secret123!'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->event = Event::create([
            'academic_year_id' => $this->academicYear->id,
            'created_by' => $this->admin->id,
            'name' => 'COMSOC Annual Hackathon 2026',
            'event_type' => 'Competition',
            'event_date' => now()->addDays(5),
            'starts_at' => now()->addDays(5)->setHour(8),
            'ends_at' => now()->addDays(5)->setHour(17),
            'is_published' => true,
            'max_participants' => 50,
            'attendance_enabled' => true,
        ]);
    }

    // MODULE 1 TESTS: PERSONAL DASHBOARD
    public function test_member_dashboard_renders_with_real_authenticated_data_and_shortcuts(): void
    {
        $response = $this->actingAs($this->memberA)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Juan');
        $response->assertSee('BSIT • Year 3 (Block A)');
        $response->assertSee('Show My QR');
        $response->assertSee('Browse Events');
        $response->assertSee('EXP History');
        $response->assertSee('Browse Rewards');
        $response->assertSee('Submit Support Request');
        $response->assertSee('Explore Projects');
        $response->assertSee('Volunteer Board');
    }

    // MODULE 2 TESTS: HELP DESK & DISPUTE CENTER
    public function test_member_can_submit_support_ticket_with_attachment_and_receive_ticket_number(): void
    {
        $file = UploadedFile::fake()->create('proof.png', 500, 'image/png');

        $response = $this->actingAs($this->memberA)->post(route('helpdesk.store'), [
            'category' => 'attendance',
            'subject' => 'Morning session scan not reflected in ledger',
            'description' => 'I scanned at Kiosk terminal 2 at 8:30 AM but my attendance record is not visible.',
            'priority' => 'high',
            'event_id' => $this->event->id,
            'attachment' => $file,
        ]);

        $ticket = SupportTicket::where('user_id', $this->memberA->id)->first();
        $this->assertNotNull($ticket);
        $this->assertStringStartsWith('TKT-', $ticket->ticket_number);
        $this->assertEquals('open', $ticket->status);
        $response->assertRedirect(route('helpdesk.show', $ticket));

        $this->assertDatabaseHas('support_ticket_messages', [
            'support_ticket_id' => $ticket->id,
            'user_id' => $this->memberA->id,
            'is_internal_note' => false,
        ]);

        $this->assertDatabaseHas('support_ticket_histories', [
            'support_ticket_id' => $ticket->id,
            'action' => 'created',
        ]);
    }

    public function test_unauthorized_member_cannot_view_another_members_ticket(): void
    {
        $ticket = SupportTicket::create([
            'ticket_number' => 'TKT-2026-TEST01',
            'user_id' => $this->memberA->id,
            'category' => 'account',
            'subject' => 'Private Student Issue',
            'description' => 'Confidential member question',
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->memberB)->get(route('helpdesk.show', $ticket));
        $response->assertStatus(403);
    }

    public function test_staff_can_add_internal_note_and_resolve_support_ticket(): void
    {
        $ticket = SupportTicket::create([
            'ticket_number' => 'TKT-2026-TEST02',
            'user_id' => $this->memberA->id,
            'category' => 'attendance',
            'subject' => 'Scan Issue',
            'description' => 'Discrepancy in scan records',
            'status' => 'open',
        ]);

        // Staff adds internal note
        $noteResponse = $this->actingAs($this->admin)->post(route('admin.support.reply', $ticket), [
            'message' => 'Checked physical logsheet: confirmed student was present.',
            'is_internal_note' => '1',
            'shift_status' => 'in_review',
        ]);
        $noteResponse->assertRedirect();

        $ticket->refresh();
        $this->assertEquals('in_review', $ticket->status);

        // Verify ordinary member cannot see internal note
        $memberView = $this->actingAs($this->memberA)->get(route('helpdesk.show', $ticket));
        $memberView->assertDontSee('Checked physical logsheet: confirmed student was present.');

        // Staff resolves ticket
        $resolveResponse = $this->actingAs($this->admin)->post(route('admin.support.resolve', $ticket), [
            'resolution_code' => 'ATTENDANCE_VERIFIED',
            'resolution_summary' => 'Attendance confirmed via coordinator roster and record updated.',
        ]);
        $resolveResponse->assertRedirect();

        $ticket->refresh();
        $this->assertEquals('resolved', $ticket->status);
        $this->assertEquals('ATTENDANCE_VERIFIED', $ticket->resolution_code);
    }

    // MODULE 3 TESTS: EVENT ANALYTICS & IMPACT REPORTS
    public function test_event_analytics_calculates_evidence_based_metrics_and_exports_csv(): void
    {
        // Create 2 confirmed registrations
        EventRegistration::create([
            'event_id' => $this->event->id,
            'user_id' => $this->memberA->id,
            'full_name' => 'Juan Dela Cruz',
            'email' => 'juan@sorsu.edu.ph',
            'status' => 'confirmed',
            'ticket_code' => 'TCK-001',
        ]);
        EventRegistration::create([
            'event_id' => $this->event->id,
            'user_id' => $this->memberB->id,
            'full_name' => 'Maria Santos',
            'email' => 'maria@sorsu.edu.ph',
            'status' => 'confirmed',
            'ticket_code' => 'TCK-002',
        ]);

        // 1 actual attendee
        $session = AttendanceSession::create([
            'event_id' => $this->event->id,
            'type' => 'morning_in',
            'opens_at' => '08:00:00',
            'closes_at' => '10:00:00',
            'is_active' => true,
        ]);

        AttendanceRecord::create([
            'event_id' => $this->event->id,
            'attendance_session_id' => $session->id,
            'participant_type' => Student::class,
            'participant_id' => $this->memberA->student_id,
            'qr_token' => 'QR-ATT-001',
            'scanned_by' => $this->admin->id,
            'scanned_at' => now(),
            'action' => 'in',
            'status' => 'present',
        ]);

        // Feedback
        EventFeedback::create([
            'event_id' => $this->event->id,
            'user_id' => $this->memberA->id,
            'rating' => 5,
            'organization_rating' => 5,
            'content_rating' => 4,
            'positive_feedback' => 'Exceptional workshop presentations!',
        ]);

        $analyticsResponse = $this->actingAs($this->admin)->get(route('admin.analytics.index', ['event_id' => $this->event->id]));
        $analyticsResponse->assertOk();
        $analyticsResponse->assertSee('50.0%'); // 1 attendee / 2 confirmed registrations = 50%
        $analyticsResponse->assertSee('Exceptional workshop presentations!');

        // CSV Export
        $exportResponse = $this->actingAs($this->admin)->get(route('admin.analytics.export.csv', ['event_id' => $this->event->id]));
        $exportResponse->assertOk();
        $this->assertStringStartsWith('text/csv', strtolower($exportResponse->headers->get('Content-Type')));
    }

    // MODULE 4 TESTS: OFFICIAL ANNOUNCEMENTS & READ RECEIPTS
    public function test_announcement_audience_targeting_and_member_acknowledgment(): void
    {
        // Announcement targeted ONLY to BSIT students
        $targetedAnn = Announcement::create([
            'title' => 'BSIT Special Symposium Notice',
            'body' => 'Important requirement for all 3rd year BSIT students.',
            'priority' => 'urgent',
            'category' => 'general',
            'target_audience_type' => 'program',
            'target_audience_values' => ['BSIT'],
            'requires_acknowledgment' => true,
            'published_at' => now()->subMinute(),
            'is_active' => true,
            'posted_by' => $this->admin->id,
        ]);

        // Member A (BSIT) can view and acknowledge
        $this->actingAs($this->memberA)->get(route('announcements.show', $targetedAnn))->assertOk();
        $this->assertDatabaseHas('announcement_receipts', [
            'announcement_id' => $targetedAnn->id,
            'user_id' => $this->memberA->id,
        ]);

        // Acknowledge directive
        $ackResponse = $this->actingAs($this->memberA)->post(route('announcements.acknowledge', $targetedAnn));
        $ackResponse->assertRedirect();
        $this->assertNotNull(AnnouncementReceipt::where('announcement_id', $targetedAnn->id)->where('user_id', $this->memberA->id)->value('acknowledged_at'));

        // Member B (BSIS) should not see this targeted announcement in their feed
        $feedResponse = $this->actingAs($this->memberB)->get(route('announcements.index'));
        $feedResponse->assertDontSee('BSIT Special Symposium Notice');
    }

    // MODULE 5 TESTS: PROJECT COLLABORATION & TEAM FINDER
    public function test_project_creation_application_and_capacity_enforcement(): void
    {
        // Member A posts project with capacity 2 (Lead + 1 member)
        $project = CollaborationProject::create([
            'owner_id' => $this->memberA->id,
            'title' => 'Campus Lost & Found Mobile App',
            'problem_statement' => 'Students lose personal items with no central tracker.',
            'description' => 'React Native mobile app with real-time push alerts.',
            'category' => 'mobile',
            'team_size_target' => 2,
            'stage' => 'idea',
            'expected_commitment' => '4 hrs/wk',
            'status' => 'recruiting',
            'is_recruiting' => true,
        ]);

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $this->memberA->id,
            'role' => 'Project Lead',
            'is_active' => true,
        ]);

        // Member B applies
        $applyResponse = $this->actingAs($this->memberB)->post(route('projects.apply', $project), [
            'preferred_role' => 'Mobile UI Designer',
            'motivation' => 'Passionate about mobile interaction design and Figma workflows.',
        ]);
        $applyResponse->assertRedirect();

        $app = ProjectApplication::where('project_id', $project->id)->where('user_id', $this->memberB->id)->first();
        $this->assertNotNull($app);

        // Owner accepts Member B
        $decideResponse = $this->actingAs($this->memberA)->post(route('projects.applications.decide', $app), [
            'action' => 'accept',
        ]);
        $decideResponse->assertRedirect();

        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'user_id' => $this->memberB->id,
            'role' => 'Mobile UI Designer',
        ]);

        // Capacity is now full (2/2). Another member cannot be accepted beyond target
        $extraUser = User::create([
            'name' => 'Carlos Extra',
            'username' => 'carlosextra',
            'email' => 'carlos@sorsu.edu.ph',
            'password' => bcrypt('Secret123!'),
            'role' => 'student',
            'is_active' => true,
        ]);

        $appExtra = ProjectApplication::create([
            'project_id' => $project->id,
            'user_id' => $extraUser->id,
            'preferred_role' => 'Tester',
            'motivation' => 'Quality assurance interest.',
            'status' => 'pending',
        ]);

        $failResponse = $this->actingAs($this->memberA)->post(route('projects.applications.decide', $appExtra), [
            'action' => 'accept',
        ]);
        $failResponse->assertSessionHasErrors('message');
        $this->assertEquals('pending', $appExtra->fresh()->status);
    }

    // MODULE 6 TESTS: VOLUNTEER OPPORTUNITIES & EXP LEDGER SERVICE
    public function test_volunteer_application_and_authoritative_exp_award(): void
    {
        $opportunity = VolunteerOpportunity::create([
            'title' => 'Kiosk Registration Assistance Desk',
            'description' => 'Guide attendees through QR pass scanning.',
            'category' => 'registration',
            'responsibilities' => 'Verify badges and distribute snack tickets.',
            'location_type' => 'onsite',
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addHours(4),
            'capacity' => 2,
            'expected_hours' => 4.00,
            'exp_reward_per_hour' => 50,
            'coordinator_id' => $this->admin->id,
            'status' => 'open',
            'is_active' => true,
        ]);

        // Member B applies
        $applyResponse = $this->actingAs($this->memberB)->post(route('volunteers.apply', $opportunity), [
            'motivation_statement' => 'Glad to support the organization with check-in operations.',
            'assigned_shift' => 'Morning Shift',
        ]);
        $applyResponse->assertRedirect();

        $volApp = VolunteerApplication::where('volunteer_opportunity_id', $opportunity->id)->where('user_id', $this->memberB->id)->first();
        $this->assertNotNull($volApp);

        // Coordinator accepts application
        $acceptResponse = $this->actingAs($this->admin)->post(route('volunteers.applications.decide', $volApp), [
            'action' => 'accept',
        ]);
        $acceptResponse->assertRedirect();

        // Coordinator approves completed 4.0 hours -> should award 4 * 50 = 200 EXP via ExpLedgerService
        $approveResponse = $this->actingAs($this->admin)->post(route('volunteers.applications.approve-hours', $volApp), [
            'approved_service_hours' => 4.0,
            'hours_approval_reason' => 'Completed shift duties with excellence.',
        ]);
        $approveResponse->assertRedirect();

        $volApp->refresh();
        $this->assertEquals('completed', $volApp->status);
        $this->assertEquals(200, $volApp->awarded_exp);

        // Verify authoritative EXP transaction was registered in ledger
        $this->assertDatabaseHas('xp_transactions', [
            'user_id' => $this->memberB->id,
            'amount' => 200,
            'source_type' => 'contribution',
            'idempotency_key' => "vol_hours_{$volApp->id}",
        ]);
    }
}
