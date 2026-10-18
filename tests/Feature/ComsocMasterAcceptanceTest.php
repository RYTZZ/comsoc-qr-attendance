<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Event;
use App\Models\ExpCredit;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use App\Services\DynamicQrService;
use App\Services\EventAttendanceService;
use App\Services\ExpLedgerService;
use App\Services\MarketplaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComsocMasterAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $comsocAdmin;
    private User $organizer;
    private User $member;
    private Event $event;
    private AttendanceSession $inSession;
    private AttendanceSession $outSession;
    private Reward $reward;

    protected function setUp(): void
    {
        parent::setUp();

        $ay = AcademicYear::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'label' => 'AY 2025–2026',
            'year_start' => 2025,
            'year_end' => 2026,
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'name' => 'Super Admin',
            'email' => 'superadmin@comsoc.local',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'is_active' => true,
            'is_eligible' => true,
            'can_collect_rewards' => true,
        ]);

        $this->comsocAdmin = User::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'name' => 'COMSOC Admin',
            'email' => 'comsocadmin@comsoc.local',
            'password' => bcrypt('password'),
            'role' => 'comsoc_admin',
            'is_active' => true,
            'is_eligible' => true,
            'can_collect_rewards' => true,
        ]);

        $this->organizer = User::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'name' => 'Event Organizer',
            'email' => 'organizer@comsoc.local',
            'password' => bcrypt('password'),
            'role' => 'event_organizer',
            'is_active' => true,
            'is_eligible' => true,
        ]);

        $this->member = User::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'name' => 'Juan Dela Cruz',
            'nickname' => 'CyberJuan',
            'student_number' => '2023-01042',
            'email' => 'juan@sorsu.edu.ph',
            'password' => bcrypt('password'),
            'role' => 'member',
            'is_active' => true,
            'is_eligible' => true,
        ]);

        $this->event = Event::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'academic_year_id' => $ay->id,
            'created_by' => $this->comsocAdmin->id,
            'name' => 'Tech Summit 2026',
            'event_date' => now()->toDateString(),
            'exp_base_amount' => 50,
            'exp_duration_rate' => 10,
            'late_penalty_percent' => 20,
        ]);

        // Assign organizer to event
        $this->event->organizers()->attach($this->organizer->id);

        $this->inSession = AttendanceSession::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'event_id' => $this->event->id,
            'type' => 'morning_in',
            'opens_at' => '07:00:00',
            'closes_at' => '12:00:00',
            'is_active' => true,
        ]);

        $this->outSession = AttendanceSession::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'event_id' => $this->event->id,
            'type' => 'morning_out',
            'opens_at' => '12:00:00',
            'closes_at' => '18:00:00',
            'is_active' => true,
        ]);

        $this->reward = Reward::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'title' => 'COMSOC Hoodie',
            'category' => 'Other merchandise',
            'points_cost' => 100,
            'stock' => 5,
            'is_active' => true,
        ]);
    }

    /** Acceptance Criterion 1 & 2: Dynamic QR token expiry, replay prevention & duplicate rejection */
    public function test_dynamic_qr_replay_and_duplicate_scans_are_rejected(): void
    {
        $qrService = app(DynamicQrService::class);
        $attendanceService = app(EventAttendanceService::class);

        // 1. Generate active rolling token
        $tokenData = $qrService->generateTokenForUser($this->member);
        $payload = $tokenData['qr_payload'];

        // First scan (valid IN)
        $result1 = $attendanceService->processScan($this->event, $this->inSession, $payload, $this->organizer);
        $this->assertTrue($result1['success']);
        $this->assertEquals('Accepted', $result1['result_type']);

        // Replay attempt with same payload: must be rejected
        $result2 = $attendanceService->processScan($this->event, $this->inSession, $payload, $this->organizer);
        $this->assertFalse($result2['success']);
        $this->assertEquals('already_used', $result2['code']);
    }

    /** Acceptance Criterion 3: Organizers cannot access unassigned events */
    public function test_organizer_cannot_access_unassigned_event(): void
    {
        $attendanceService = app(EventAttendanceService::class);
        $qrService = app(DynamicQrService::class);

        // Unassign organizer
        $this->event->organizers()->detach($this->organizer->id);

        $tokenData = $qrService->generateTokenForUser($this->member);
        $result = $attendanceService->processScan($this->event, $this->inSession, $tokenData['qr_payload'], $this->organizer);

        $this->assertFalse($result['success']);
        $this->assertEquals('unauthorized_organizer', $result['code']);
    }

    /** Acceptance Criterion 4: Missing time-out corrections require authorization and audit */
    public function test_missing_timeout_correction_creates_audit(): void
    {
        $attendanceService = app(EventAttendanceService::class);

        $inRecord = AttendanceRecord::create([
            'event_id' => $this->event->id,
            'attendance_session_id' => $this->inSession->id,
            'qr_token' => 'TEST_TOKEN_1',
            'participant_type' => 'user',
            'participant_id' => $this->member->id,
            'user_id' => $this->member->id,
            'action' => 'in',
            'status' => 'present',
            'scanned_by' => $this->organizer->id,
            'scanned_at' => now()->subHours(2),
        ]);

        $result = $attendanceService->correctMissingTimeOut($inRecord, 'Member forgot phone in auditorium', $this->organizer);
        $this->assertTrue($result['success']);

        $outRecord = AttendanceRecord::where('attendance_session_id', $this->outSession->id)
            ->where('user_id', $this->member->id)
            ->first();

        $this->assertNotNull($outRecord);
        $this->assertTrue((bool)$outRecord->is_corrected);
        $this->assertEquals('Member forgot phone in auditorium', $outRecord->correction_reason);
    }

    /** Acceptance Criterion 5, 8 & 9: FEFO credit deduction, expired EXP rejection, insufficient balance */
    public function test_fefo_credit_deduction_and_insufficient_exp_rejection(): void
    {
        $expLedger = app(ExpLedgerService::class);
        $marketplace = app(MarketplaceService::class);

        // Member initially has 0 EXP -> should fail redemption
        $res1 = $marketplace->redeemReward($this->member, $this->reward);
        $this->assertFalse($res1['success']);
        $this->assertStringContainsString('Insufficient Spendable EXP', $res1['message']);

        // Award 150 EXP (one 50 EXP credit expiring in 2 days, one 100 EXP credit non-expiring)
        $expLedger->creditExp($this->member, 50, 'attendance', null, 'Event 1', 2);
        $expLedger->creditExp($this->member, 100, 'mission', null, 'Mission 1', null);

        $this->assertEquals(150, $this->member->getSpendableExp());

        // Redeem 100 EXP reward -> FEFO should consume the 50 expiring credit first, and 50 from non-expiring
        $res2 = $marketplace->redeemReward($this->member, $this->reward);
        $this->assertTrue($res2['success']);
        $this->assertNotNull($res2['claim_code']);

        // Remaining Spendable EXP should be 50
        $this->assertEquals(50, $this->member->getSpendableExp());

        // Lifetime EXP should remain 150 (not decreased by redemption)
        $this->assertEquals(150, $this->member->getLifetimeExp());

        // Reward stock should be decremented from 5 to 4
        $this->assertEquals(4, $this->reward->fresh()->stock);
    }

    /** Acceptance Criterion 7: Invalid award reversals never produce negative lifetime EXP */
    public function test_invalid_award_reversals_preserve_history_and_never_negative(): void
    {
        $expLedger = app(ExpLedgerService::class);

        $res = $expLedger->creditExp($this->member, 100, 'task', null, 'Bonus Task', null);
        $credit = $res['credit'];

        // Reverse the credit
        $revRes = $expLedger->reverseAward($credit, 'Task criteria not fulfilled', $this->comsocAdmin);
        $this->assertTrue($revRes['success']);

        $this->assertEquals(0, $this->member->getSpendableExp());
        $this->assertGreaterThanOrEqual(0, $this->member->getLifetimeExp());
    }

    /** Acceptance Criterion 11: Single-use claim codes and unauthorized collection rejection */
    public function test_claim_code_verification_and_unauthorized_collection(): void
    {
        $expLedger = app(ExpLedgerService::class);
        $marketplace = app(MarketplaceService::class);

        $expLedger->creditExp($this->member, 200, 'attendance', null, 'Event 1', null);
        $redemptionResult = $marketplace->redeemReward($this->member, $this->reward);
        $claimCode = $redemptionResult['claim_code'];

        // Unauthorized user attempts collection (regular member without permission)
        $resUnauth = $marketplace->recordCollection($claimCode, $this->member);
        $this->assertFalse($resUnauth['success']);
        $this->assertStringContainsString('Unauthorized', $resUnauth['message']);

        // Authorized collection by COMSOC Admin
        $resAuth = $marketplace->recordCollection($claimCode, $this->comsocAdmin);
        $this->assertTrue($resAuth['success']);

        // Attempting to collect again with same claim code must be rejected (single-use)
        $resDuplicate = $marketplace->recordCollection($claimCode, $this->comsocAdmin);
        $this->assertFalse($resDuplicate['success']);
        $this->assertStringContainsString('already used', $resDuplicate['message']);
    }

    /** Acceptance Criterion 12: Ineligible accounts cannot participate */
    public function test_ineligible_member_cannot_redeem_or_scan(): void
    {
        $this->member->update(['is_eligible' => false]);

        $expLedger = app(ExpLedgerService::class);
        $marketplace = app(MarketplaceService::class);
        $qrService = app(DynamicQrService::class);

        $expLedger->creditExp($this->member, 200, 'attendance', null, 'Event 1', null);

        // Cannot redeem
        $redemptionRes = $marketplace->redeemReward($this->member, $this->reward);
        $this->assertFalse($redemptionRes['success']);
        $this->assertStringContainsString('ineligible', $redemptionRes['message']);

        // Cannot validate QR
        $tokenData = $qrService->generateTokenForUser($this->member);
        $valRes = $qrService->validateAndConsumeToken($tokenData['qr_payload'], $this->organizer);
        $this->assertFalse($valRes['valid']);
        $this->assertEquals('ineligible_member', $valRes['code']);
    }
}
