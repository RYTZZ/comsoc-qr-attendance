<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Event;
use App\Models\Kiosk;
use App\Models\User;
use App\Services\EventEvaluationService;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public function __construct(
        private QrCodeService $qrService,
        private RewardService $rewardService,
        private EventEvaluationService $evaluationService
    ) {}

    public function scan(string $token, Event $event, AttendanceSession $session, Kiosk $kiosk, User $staff): array
    {
        $participant = $this->qrService->resolveParticipant($token);

        if (!$participant) {
            $inactiveQr = \App\Models\QrCode::where('token', $token)->first();
            if ($inactiveQr) {
                return [
                    'success' => false,
                    'message' => 'This membership QR code is ' . ($inactiveQr->status ?? 'inactive') . '.',
                    'code' => 'inactive_qr'
                ];
            }

            $reg = \App\Models\EventRegistration::where('qr_token', $token)->first();
            if ($reg) {
                if ($reg->status !== 'approved') {
                    return [
                        'success' => false,
                        'message' => 'Event registration is ' . $reg->status . '.',
                        'code' => 'inactive_qr'
                    ];
                }
                if ($reg->qr_expires_at && now()->gte($reg->qr_expires_at)) {
                    return [
                        'success' => false,
                        'message' => 'This event QR code has expired.',
                        'code' => 'expired_qr'
                    ];
                }
            }

            return ['success' => false, 'message' => 'Invalid or unrecognized QR code.', 'code' => 'invalid_qr'];
        }

        if ($session->event_id !== $event->id) {
            return ['success' => false, 'message' => 'Invalid attendance session for this event.', 'code' => 'session_mismatch'];
        }

        if (isset($participant['registration'])) {
            if ($participant['registration']->event_id !== $event->id) {
                return [
                    'success' => false,
                    'message' => 'This QR pass is registered for a different event.',
                    'code' => 'event_mismatch'
                ];
            }
        }

        $action = $session->isInSession() ? 'in' : 'out';

        if ($action === 'out') {
            $inType = str_replace('_out', '_in', $session->type);
            $inSession = AttendanceSession::where('event_id', $event->id)->where('type', $inType)->first();
            if ($inSession) {
                $hasIn = AttendanceRecord::where('attendance_session_id', $inSession->id)
                    ->where('qr_token', $token)
                    ->where('action', 'in')
                    ->exists();
                if (!$hasIn) {
                    return ['success' => false, 'message' => 'Cannot scan OUT without a prior IN record.', 'code' => 'no_in_record'];
                }
            }

            // Enforce mandatory evaluation requirement for Afternoon OUT
            if ($session->type === 'afternoon_out') {
                $participantUser = null;
                if ($participant['type'] === 'user' && !empty($participant['user'])) {
                    $participantUser = $participant['user'];
                } elseif ($participant['type'] === 'student' && !empty($participant['membership']?->student)) {
                    $participantUser = User::where('student_id', $participant['membership']->student->id)->first();
                }

                if ($participantUser) {
                    $evalCheck = $this->evaluationService->verifyEvaluationForTimeOut($event, $participantUser, $session);
                    if (!$evalCheck['allowed']) {
                        return [
                            'success' => false,
                            'message' => $evalCheck['message'],
                            'code' => $evalCheck['code'],
                        ];
                    }
                } elseif ($event->evaluation_template_id) {
                    // Non-user student or unmapped account without user record
                    return [
                        'success' => false,
                        'message' => 'Mandatory Event Evaluation Required: Please complete the event evaluation from your dashboard before scanning out.',
                        'code' => 'evaluation_required',
                    ];
                }
            }
        }

        $existing = AttendanceRecord::where('attendance_session_id', $session->id)
            ->where(function ($q) use ($participant, $token) {
                $q->where('qr_token', $token)
                    ->orWhere(function ($sub) use ($participant) {
                        $sub->where('participant_type', $participant['type'])
                            ->where('participant_id', $participant['id']);
                    });
            })
            ->first();

        if ($existing) {
            $scannedAt = $existing->scanned_at ? $existing->scanned_at->format('g:i A') : 'earlier';
            return [
                'success' => false,
                'message' => "Already scanned for {$session->name} at {$scannedAt}.",
                'code' => 'duplicate'
            ];
        }

        $scanTime = now()->format('H:i:s');
        $status = $session->determineStatus($scanTime);

        try {
            $record = DB::transaction(function () use ($token, $event, $session, $participant, $action, $status, $kiosk, $staff) {
                return AttendanceRecord::create([
                    'event_id' => $event->id,
                    'attendance_session_id' => $session->id,
                    'qr_token' => $token,
                    'participant_type' => $participant['type'],
                    'participant_id' => $participant['id'],
                    'action' => $action,
                    'status' => $status,
                    'kiosk_id' => $kiosk->id,
                    'scanned_by' => $staff->id,
                    'scanned_at' => now(),
                ]);
            });

            $studentNumber = null;
            if ($participant['type'] === 'student' && isset($participant['membership']->student)) {
                $studentNumber = $participant['membership']->student->student_number;
                $user = User::where('student_id', $participant['membership']->student->id)->first();
                if ($user) {
                    $this->rewardService->awardForEventAttendance($user, $event, $session->id);
                    \App\Services\NotificationService::send(
                        $user,
                        'attendance_confirmed',
                        'Attendance Confirmed: ' . $event->name,
                        'Your attendance was scanned for ' . $session->name . ' (' . ucfirst($action) . ' - ' . ucfirst($status) . ').',
                        route('events.show', $event)
                    );
                }
            }

            return [
                'success' => true,
                'message' => ucfirst($action) . ' recorded — ' . ucfirst($status),
                'action' => $action,
                'status' => $status,
                'participant_type' => $participant['type'],
                'name' => $participant['name'],
                'student_number' => $studentNumber,
                'record' => $record,
            ];
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return ['success' => false, 'message' => 'Already scanned for this session.', 'code' => 'duplicate'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Scan failed. Please try again.', 'code' => 'error'];
        }
    }
}
