<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\Event;
use App\Models\Level;
use App\Models\Raffle;
use App\Models\RaffleEntry;
use App\Models\Reward;
use App\Models\User;
use App\Services\MarketplaceService;
use App\Services\RaffleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ComsocGovernanceAndPrivacyRemediationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed level requirements for testing
        Level::create(['level' => 1, 'min_xp' => 0, 'title' => 'Novice']);
        Level::create(['level' => 2, 'min_xp' => 100, 'title' => 'Apprentice']);
        Level::create(['level' => 5, 'min_xp' => 500, 'title' => 'Expert']);
    }

    public function test_student_self_registration_with_institutional_email_assigns_permanent_member_id(): void
    {
        $response = $this->post(route('signup.store'), [
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'middle_name' => 'Cruz',
            'student_id' => '2026-0001',
            'email' => 'maria.santos@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Technology',
            'year' => '1st',
            'block' => '1',
            'username' => 'mariasantos',
            'avatar_seed' => 'Felix',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('status');

        $user = User::where('email', 'maria.santos@sorsu.edu.ph')->first();
        $this->assertNotNull($user);
        $this->assertEquals('student', $user->role);
        $this->assertFalse((bool) $user->is_registrar_imported);
        $this->assertNotNull($user->member_id);
        $this->assertStringStartsWith('CMS-', $user->member_id);
        $this->assertNotNull($user->identity_verified_at);
        $this->assertEquals('institutional_email', $user->identity_verification_method);
    }

    public function test_self_registration_never_grants_officer_role(): void
    {
        $this->post(route('signup.store'), [
            'first_name' => 'Privilege',
            'last_name' => 'Escort',
            'student_id' => '2026-9999',
            'email' => 'attacker@sorsu.edu.ph',
            'role' => 'super_admin', // Attempted injection
            'program' => 'Bachelor of Science in Computer Science',
            'year' => '2nd',
            'block' => '2',
            'username' => 'privilege_escort',
            'avatar_seed' => 'Felix',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms' => '1',
        ]);

        $user = User::where('email', 'attacker@sorsu.edu.ph')->first();
        $this->assertNotNull($user);
        $this->assertEquals('student', $user->role);
    }

    public function test_duplicate_registration_masks_existing_account_enumeration(): void
    {
        User::factory()->create([
            'email' => 'existing.student@sorsu.edu.ph',
            'role' => 'student',
        ]);

        $response = $this->post(route('signup.store'), [
            'first_name' => 'Existing',
            'last_name' => 'Student',
            'student_id' => '2026-1234',
            'email' => 'existing.student@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Technology',
            'year' => '1st',
            'block' => '1',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms' => '1',
        ]);

        $response->assertSessionHasErrors(['email']);
        $errorMsg = session('errors')->first('email');
        // Must be anti-enumeration generic message
        $this->assertStringContainsString('could not be processed', strtolower($errorMsg));
    }

    public function test_event_approval_workflow_enforces_president_or_superadmin_approval_before_publishing(): void
    {
        $president = User::factory()->create(['role' => 'president']);
        $execSec = User::factory()->create(['role' => 'executive_secretary']);
        $ay = AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'semester' => '1st',
            'is_active' => true,
        ]);

        $event = Event::create([
            'name' => 'Annual Assembly',
            'academic_year_id' => $ay->id,
            'created_by' => $execSec->id,
            'status' => 'draft',
            'is_published' => false,
            'event_date' => now()->addDays(7),
        ]);

        // 1. Executive Secretary cannot publish unapproved draft event directly
        $response = $this->actingAs($execSec)
            ->from(route('admin.events.index'))
            ->post(route('admin.events.publish', $event));
        $response->assertSessionHas('error');
        $this->assertFalse($event->fresh()->is_published);

        // 2. President approves event
        $this->actingAs($president)
            ->from(route('admin.events.index'))
            ->post(route('admin.events.approve', $event), ['notes' => 'Approved for student participation.'])
            ->assertSessionHas('success');

        $event->refresh();
        $this->assertEquals('approved', $event->status);
        $this->assertEquals($president->id, $event->approved_by);
        $this->assertNotNull($event->approved_at);

        // 3. Executive Secretary can now publish approved event
        $this->actingAs($execSec)
            ->from(route('admin.events.index'))
            ->post(route('admin.events.publish', $event))
            ->assertSessionHas('success');

        $event->refresh();
        $this->assertEquals('published', $event->status);
        $this->assertTrue($event->is_published);

        // 4. Transition to completed
        $this->actingAs($president)
            ->from(route('admin.events.index'))
            ->post(route('admin.events.complete', $event))
            ->assertSessionHas('success');

        $event->refresh();
        $this->assertEquals('completed', $event->status);
    }

    public function test_raffle_grants_exactly_one_entry_per_verified_attendee_and_records_draw(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $student1 = User::factory()->create(['role' => 'student']);
        $student2 = User::factory()->create(['role' => 'student']);

        $ay = AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'semester' => '1st',
            'is_active' => true,
        ]);

        $event = Event::create([
            'name' => 'COMSOC Hackathon',
            'academic_year_id' => $ay->id,
            'created_by' => $superAdmin->id,
            'status' => 'published',
            'is_published' => true,
            'event_date' => now(),
        ]);

        $session = \App\Models\AttendanceSession::create([
            'event_id' => $event->id,
            'type' => 'morning_in',
            'opens_at' => now()->subHour(),
            'closes_at' => now()->addHours(2),
            'late_threshold' => now(),
            'is_active' => true,
        ]);

        // Verified attendance for 2 students
        AttendanceRecord::create([
            'event_id' => $event->id,
            'attendance_session_id' => $session->id,
            'user_id' => $student1->id,
            'participant_type' => 'student',
            'participant_id' => $student1->id,
            'status' => 'present',
            'action' => 'in',
            'qr_token' => 'TOKEN_1',
            'scanned_at' => now(),
        ]);
        // Duplicate attendance record for student1 (e.g. afternoon session)
        AttendanceRecord::create([
            'event_id' => $event->id,
            'attendance_session_id' => $session->id,
            'user_id' => $student1->id,
            'participant_type' => 'student',
            'participant_id' => $student1->id,
            'status' => 'present',
            'action' => 'in',
            'qr_token' => 'TOKEN_1_AFTERNOON',
            'scanned_at' => now()->addHours(2),
        ]);
        AttendanceRecord::create([
            'event_id' => $event->id,
            'attendance_session_id' => $session->id,
            'user_id' => $student2->id,
            'participant_type' => 'student',
            'participant_id' => $student2->id,
            'status' => 'present',
            'action' => 'in',
            'qr_token' => 'TOKEN_2',
            'scanned_at' => now(),
        ]);

        $raffleService = app(RaffleService::class);
        $raffle = $raffleService->createRaffle($event, [
            'title' => 'Grand Tech Giveaway',
            'prize_name' => 'Wireless Mechanical Keyboard',
            'points_bonus' => 150,
            'winner_count' => 1,
        ], $superAdmin);

        // Sync entries
        $count = $raffleService->syncEntriesFromAttendance($raffle);
        $this->assertEquals(2, $count); // Exactly 2 unique students, duplicate scans collapsed
        $this->assertEquals(2, $raffle->entries()->count());

        // Draw winners
        $winners = $raffleService->executeDraw($raffle, $superAdmin);
        $this->assertCount(1, $winners);
        $this->assertTrue($raffle->fresh()->isDrawn());

        $winner = $winners[0];
        $this->assertEquals(150, $winner->points_awarded);
        $this->assertFalse($winner->is_claimed);

        // Verify redeem points ledger entry created
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $winner->user_id,
            'amount' => 150,
            'transaction_type' => 'raffle_win',
        ]);
    }

    public function test_auditor_role_has_read_only_access_and_cannot_modify_balances_or_events(): void
    {
        $auditor = User::factory()->create(['role' => 'auditor']);
        $ay = AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'semester' => '1st',
            'is_active' => true,
        ]);
        $event = Event::create([
            'name' => 'Tech Day',
            'academic_year_id' => $ay->id,
            'created_by' => $auditor->id,
            'status' => 'draft',
            'is_published' => false,
            'event_date' => now(),
        ]);

        // Auditor can view reports and audit logs
        $this->actingAs($auditor)
            ->get(route('admin.reports.index'))
            ->assertStatus(200);

        // Auditor CANNOT adjust member economy balances (403 forbidden)
        $this->actingAs($auditor)
            ->post(route('admin.economy.adjust'), [
                'user_id' => $auditor->id,
                'spendable_delta' => 100,
                'lifetime_delta' => 100,
                'reason' => 'Unauthorized audit test',
            ])
            ->assertStatus(403);

        // Auditor CANNOT approve events
        $this->actingAs($auditor)
            ->post(route('admin.events.approve', $event))
            ->assertStatus(403);
    }
}
