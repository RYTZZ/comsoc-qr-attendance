<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\DynamicQrService;
use App\Services\ExpLedgerService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventAttendanceService
{
    public function __construct(
        private DynamicQrService $dynamicQrService,
        private ExpLedgerService $expLedgerService
    ) {}

    /**
     * Process an organizer scan of a dynamic member QR code for an assigned event session.
     */
    public function processScan(Event $event, AttendanceSession $session, string $qrTokenPayload, User $organizer): array
    {
        // 1. Verify organizer authorization for this specific event
        if (!$organizer->isSuperAdmin() && !$organizer->isComsocAdmin()) {
            $isAssigned = $event->organizers()->where('users.id', $organizer->id)->exists();
            if (!$isAssigned) {
                return [
                    'success' => false,
                    'code' => 'unauthorized_organizer',
                    'message' => 'You are not an authorized organizer for this event.',
                    'result_type' => 'Wrong event',
                ];
            }
        }

        // 2. Validate session belongs to event and is active
        if ($session->event_id !== $event->id) {
            return [
                'success' => false,
                'code' => 'session_mismatch',
                'message' => 'The selected attendance session does not belong to this event.',
                'result_type' => 'Wrong event',
            ];
        }

        // 3. Authenticate dynamic QR token & check single-use anti-replay (scoped to event)
        $tokenValidation = $this->dynamicQrService->validateAndConsumeToken($qrTokenPayload, $organizer, $event);
        if (!$tokenValidation['valid']) {
            $resultType = match ($tokenValidation['code']) {
                'expired_token' => 'Expired',
                'already_used' => 'Duplicate',
                'ineligible_member' => 'Ineligible',
                'event_mismatch' => 'Wrong event',
                default => 'Invalid token',
            };

            return [
                'success' => false,
                'code' => $tokenValidation['code'],
                'message' => $tokenValidation['message'],
                'result_type' => $resultType,
            ];
        }

        /** @var User $member */
        $member = $tokenValidation['user'];

        // 3b. Verify event registration eligibility: Student must be registered or joined for this event
        $student = $member->student;
        $isRegistered = \App\Models\EventRegistration::where('event_id', $event->id)
            ->where('status', 'approved')
            ->where(function ($q) use ($member, $student) {
                $q->where('user_id', $member->id)
                  ->orWhere('email', strtolower($member->email));
                if ($student) {
                    $q->orWhere('email', strtolower($student->email));
                }
            })
            ->exists();

        if ($event->requires_registration && !$isRegistered) {
            return [
                'success' => false,
                'code' => 'not_registered',
                'message' => "Registration Required: {$member->display_name} has not registered for this event.",
                'result_type' => 'Ineligible',
                'member' => [
                    'name' => $member->display_name,
                    'student_number' => $member->student_number,
                ],
            ];
        }

        $action = $session->isInSession() ? 'in' : 'out';

        // 4. Validate time-in / time-out sequencing:
        // A member must have recorded a valid time-in before recording a time-out.
        if ($action === 'out') {
            $inSessionType = str_replace('_out', '_in', $session->type);
            $inSession = AttendanceSession::where('event_id', $event->id)->where('type', $inSessionType)->first();
            
            if ($inSession) {
                $hasTimeIn = AttendanceRecord::where('event_id', $event->id)
                    ->where('attendance_session_id', $inSession->id)
                    ->where('user_id', $member->id)
                    ->where('action', 'in')
                    ->exists();

                if (!$hasTimeIn) {
                    return [
                        'success' => false,
                        'code' => 'no_time_in_record',
                        'message' => 'Missing Time-In: A valid time-in record is required before recording time-out.',
                        'result_type' => 'Invalid token',
                        'member' => [
                            'name' => $member->display_name,
                            'student_number' => $member->student_number,
                        ],
                    ];
                }
            }
        }

        // 5. Check duplicate scan for this exact session
        $existingRecord = AttendanceRecord::where('attendance_session_id', $session->id)
            ->where('user_id', $member->id)
            ->first();

        if ($existingRecord) {
            $scannedAtStr = $existingRecord->scanned_at ? $existingRecord->scanned_at->format('g:i A') : 'earlier';
            return [
                'success' => false,
                'code' => 'duplicate_scan',
                'message' => "Duplicate: Already recorded for {$session->label} at {$scannedAtStr}.",
                'result_type' => 'Duplicate',
                'member' => [
                    'name' => $member->display_name,
                    'student_number' => $member->student_number,
                ],
            ];
        }

        // 6. Record attendance in transaction
        return DB::transaction(function () use ($event, $session, $member, $organizer, $action, $tokenValidation) {
            $scanTime = now()->format('H:i:s');
            $status = $session->determineStatus($scanTime);

            $record = AttendanceRecord::create([
                'event_id' => $event->id,
                'attendance_session_id' => $session->id,
                'qr_token' => $tokenValidation['token_record']->token_hash,
                'participant_type' => 'user',
                'participant_id' => $member->id,
                'user_id' => $member->id,
                'action' => $action,
                'status' => $status,
                'scanned_by' => $organizer->id,
                'scanned_at' => now(),
                'calculated_exp' => 0,
            ]);

            $expAwarded = 0;
            $expMessage = '';

            // 7. Check if attendance cycle is complete (Normal attendance EXP requires both a valid time-in and time-out)
            if ($action === 'out') {
                $expResult = $this->evaluateAndAwardAttendanceExp($event, $session, $member, $organizer);
                if ($expResult['awarded']) {
                    $expAwarded = $expResult['amount'];
                    $record->update(['calculated_exp' => $expAwarded]);
                    $expMessage = " +{$expAwarded} EXP awarded!";
                }
            }

            AuditLogger::log('attendance.scan', $record, [], [
                'event' => $event->name,
                'session' => $session->label,
                'member' => $member->name,
                'action' => $action,
                'status' => $status,
                'exp_awarded' => $expAwarded,
                'scanned_by' => $organizer->name,
            ]);

            return [
                'success' => true,
                'code' => 'accepted',
                'message' => "Accepted: {$session->label} recorded successfully for {$member->display_name}.{$expMessage}",
                'result_type' => 'Accepted',
                'action' => $action,
                'status' => $status,
                'exp_awarded' => $expAwarded,
                'member' => [
                    'name' => $member->display_name,
                    'student_number' => $member->student_number,
                ],
            ];
        });
    }

    /**
     * Calculate and award EXP when attendance criteria are satisfied (both valid time-in & time-out).
     */
    public function evaluateAndAwardAttendanceExp(Event $event, AttendanceSession $outSession, User $member, User $actor): array
    {
        $inSessionType = str_replace('_out', '_in', $outSession->type);
        $inSession = AttendanceSession::where('event_id', $event->id)->where('type', $inSessionType)->first();

        $inRecord = AttendanceRecord::where('event_id', $event->id)
            ->where('attendance_session_id', $inSession?->id)
            ->where('user_id', $member->id)
            ->where('action', 'in')
            ->first();

        $outRecord = AttendanceRecord::where('event_id', $event->id)
            ->where('attendance_session_id', $outSession->id)
            ->where('user_id', $member->id)
            ->where('action', 'out')
            ->first();

        if (!$inRecord || !$outRecord) {
            return ['awarded' => false, 'reason' => 'incomplete_pair'];
        }

        // Idempotency check: Ensure EXP isn't awarded more than once for this event session pair
        $idempotencyKey = "event_{$event->id}_session_{$outSession->id}_user_{$member->id}";

        // Formula Calculation
        $baseExp = $event->exp_base_amount ?? ($event->reward_xp ?: 50);
        $durationHours = 1;

        if ($inRecord->scanned_at && $outRecord->scanned_at) {
            $durationMinutes = $inRecord->scanned_at->diffInMinutes($outRecord->scanned_at);
            $durationHours = max(1, round($durationMinutes / 60, 1));
        }

        $ratePerHour = $event->exp_duration_rate ?? 10;
        $calculatedExp = (int) ($baseExp + ($durationHours * $ratePerHour));

        // Deduct late penalty if time-in was marked late
        if ($inRecord->status === 'late') {
            $penaltyPercent = $event->late_penalty_percent ?? 20;
            $penaltyAmount = (int) round(($calculatedExp * $penaltyPercent) / 100);
            $calculatedExp = max(10, $calculatedExp - $penaltyAmount);
        }

        // Award through authoritative ExpLedgerService
        $expirationDays = $event->exp_expiration_days; // null = non-expiring

        $creditResult = $this->expLedgerService->creditExp(
            user: $member,
            amount: $calculatedExp,
            sourceType: 'attendance',
            sourceId: $event->id,
            description: "Event Attendance: {$event->name} ({$outSession->label})",
            expirationDays: $expirationDays,
            idempotencyKey: $idempotencyKey,
            actor: $actor
        );

        // Also credit configured event points (if any) to point ledger and season stats
        $pointsAmount = (int) ($event->reward_points ?? 0);
        if ($creditResult['awarded'] && $pointsAmount > 0) {
            $pointsKey = $idempotencyKey . '_points';
            $existingPt = \App\Models\PointTransaction::where('idempotency_key', $pointsKey)->first();
            if (!$existingPt) {
                $activeSeason = \App\Models\Season::where('is_active', true)->first();
                \App\Models\PointTransaction::create([
                    'user_id' => $member->id,
                    'season_id' => $activeSeason?->id,
                    'amount' => $pointsAmount,
                    'transaction_type' => 'event_attendance',
                    'source_type' => Event::class,
                    'source_id' => $event->id,
                    'description' => "Event Attendance Points: {$event->name} ({$outSession->label})",
                    'idempotency_key' => $pointsKey,
                    'created_by' => $actor?->id,
                ]);

                if ($activeSeason) {
                    $stat = \App\Models\MemberSeasonStat::firstOrCreate(
                        ['user_id' => $member->id, 'season_id' => $activeSeason->id],
                        ['season_xp' => 0, 'redeemable_points' => 0, 'current_level' => 1]
                    );
                    $stat->increment('redeemable_points', $pointsAmount);
                }
            }
        }

        return $creditResult;
    }

    /**
     * Submit or approve missing time-out correction within 24 hours.
     */
    public function correctMissingTimeOut(AttendanceRecord $inRecord, string $reason, User $organizer): array
    {
        // 1. Validate authorization
        if (!$organizer->isSuperAdmin() && !$organizer->isComsocAdmin()) {
            $isAssigned = $inRecord->event->organizers()->where('users.id', $organizer->id)->exists();
            if (!$isAssigned) {
                return ['success' => false, 'message' => 'Unauthorized: You are not assigned to this event.'];
            }
        }

        // 2. Validate within 24 hours of session scan
        $recordTime = $inRecord->scanned_at ?? $inRecord->created_at;
        if (now()->diffInHours($recordTime) > 24) {
            return ['success' => false, 'message' => 'Correction window expired: Corrections are permitted within 24 hours only.'];
        }

        // 3. Find corresponding OUT session
        $inSession = $inRecord->attendanceSession;
        $outSessionType = str_replace('_in', '_out', $inSession->type);
        $outSession = AttendanceSession::where('event_id', $inRecord->event_id)->where('type', $outSessionType)->first();

        if (!$outSession) {
            return ['success' => false, 'message' => 'No corresponding OUT session found for this event.'];
        }

        // Check if out record already exists
        $existingOut = AttendanceRecord::where('attendance_session_id', $outSession->id)
            ->where('user_id', $inRecord->user_id)
            ->first();

        if ($existingOut) {
            return ['success' => false, 'message' => 'An OUT record already exists for this member.'];
        }

        return DB::transaction(function () use ($inRecord, $outSession, $reason, $organizer) {
            $correctedTime = $outSession->closes_at ? Carbon::parse($outSession->closes_at) : now();

            $outRecord = AttendanceRecord::create([
                'event_id' => $inRecord->event_id,
                'attendance_session_id' => $outSession->id,
                'qr_token' => 'CORRECTED_' . Str::random(24),
                'participant_type' => $inRecord->participant_type,
                'participant_id' => $inRecord->participant_id,
                'user_id' => $inRecord->user_id,
                'action' => 'out',
                'status' => 'present',
                'scanned_by' => $organizer->id,
                'scanned_at' => $correctedTime,
                'is_corrected' => true,
                'corrected_by' => $organizer->id,
                'corrected_at' => now(),
                'correction_reason' => $reason,
                'calculated_exp' => 0,
            ]);

            // Re-evaluate EXP award
            $expResult = $this->evaluateAndAwardAttendanceExp($inRecord->event, $outSession, $inRecord->user, $organizer);
            if ($expResult['awarded']) {
                $outRecord->update(['calculated_exp' => $expResult['amount']]);
            }

            AuditLogger::log('attendance.correction', $outRecord, [
                'original_out' => null,
            ], [
                'corrected_out' => $correctedTime->toIso8601String(),
                'reason' => $reason,
                'approved_by' => $organizer->name,
                'member' => $inRecord->user->name,
            ]);

            return [
                'success' => true,
                'message' => 'Missing Time-Out corrected and audited successfully.',
                'record' => $outRecord,
            ];
        });
    }
}
