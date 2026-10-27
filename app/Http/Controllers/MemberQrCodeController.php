<?php

namespace App\Http\Controllers;

use App\Services\DynamicQrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberQrCodeController extends Controller
{
    public function __construct(
        private DynamicQrService $dynamicQrService
    ) {}

    /**
     * Dedicated full-screen dynamic QR pass view for members.
     * Enforces event-specific pass requirement:
     * - No eligible joined events: passState = 'no_events' (or 'pending'/'revoked' if registrations exist)
     * - Exactly one eligible joined event: passState = 'active'
     * - Multiple eligible joined events: allows selecting an event, or defaults to selected/first
     */
    public function show(Request $request): View
    {
        $user = $request->user();
        $student = $user->student;

        // Fetch all registrations for this user
        $allRegistrations = \App\Models\EventRegistration::with(['event'])
            ->where(function ($q) use ($user, $student) {
                $q->where('user_id', $user->id)
                  ->orWhere('email', strtolower($user->email));
                if ($student) {
                    $q->orWhere('email', strtolower($student->email));
                }
            })
            ->get();

        // Filter approved registrations with active, non-ended, published events
        $approvedRegistrations = $allRegistrations->filter(function ($reg) {
            return $reg->status === 'approved'
                && $reg->event
                && ($reg->event->is_published || $reg->event->status === 'published' || $reg->event->status === 'ongoing')
                && !in_array($reg->event->status, ['draft', 'archived', 'cancelled'])
                && !$reg->event->isEnded();
        });

        $pendingRegistrations = $allRegistrations->filter(fn($reg) => $reg->status === 'pending');
        $revokedRegistrations = $allRegistrations->filter(fn($reg) => in_array($reg->status, ['rejected', 'cancelled', 'revoked']));

        $requestedEventId = $request->input('event_id');
        $event = null;
        $eventRegistration = null;
        $passState = 'no_events'; // 'active', 'no_events', 'pending', 'revoked', 'ended'

        if ($requestedEventId) {
            $event = \App\Models\Event::where('id', $requestedEventId)->first();
            $eventRegistration = $allRegistrations->firstWhere('event_id', $requestedEventId);

            if (!$eventRegistration) {
                $passState = 'no_events';
            } elseif ($event && $event->isEnded()) {
                $passState = 'ended';
            } elseif ($eventRegistration->status === 'pending') {
                $passState = 'pending';
            } elseif (in_array($eventRegistration->status, ['rejected', 'cancelled', 'revoked'])) {
                $passState = 'revoked';
            } elseif ($eventRegistration->status === 'approved') {
                if (!$user->is_active || !$user->is_eligible) {
                    $passState = 'revoked';
                } else {
                    $passState = 'active';
                }
            }
        } else {
            // No event_id parameter specified in query string:
            // Prefer active, non-ended approved events first
            if ($approvedRegistrations->isNotEmpty()) {
                $eventRegistration = $approvedRegistrations->first();
                $event = $eventRegistration->event;

                if (!$user->is_active || !$user->is_eligible) {
                    $passState = 'revoked';
                } else {
                    $passState = 'active';
                }
            } else {
                // Check if user has an approved registration for an event that has ended
                $endedRegistration = $allRegistrations->first(function ($reg) {
                    return $reg->status === 'approved' && $reg->event && $reg->event->isEnded();
                });

                if ($endedRegistration) {
                    $eventRegistration = $endedRegistration;
                    $event = $endedRegistration->event;
                    $passState = 'ended';
                } elseif ($pendingRegistrations->isNotEmpty()) {
                    $eventRegistration = $pendingRegistrations->first();
                    $event = $eventRegistration->event;
                    $passState = 'pending';
                } elseif ($revokedRegistrations->isNotEmpty()) {
                    $eventRegistration = $revokedRegistrations->first();
                    $event = $eventRegistration->event;
                    $passState = 'revoked';
                } else {
                    $passState = 'no_events';
                }
            }
        }

        // Only generate valid token if state is active and an approved non-ended event exists
        $tokenData = null;
        if ($passState === 'active' && $event && !$event->isEnded()) {
            $tokenData = $this->dynamicQrService->generateTokenForUser($user, $event);
        }

        return view('member.dynamic_qr', [
            'user' => $user,
            'event' => $event,
            'eventRegistration' => $eventRegistration,
            'approvedRegistrations' => $approvedRegistrations,
            'pendingRegistrations' => $pendingRegistrations,
            'passState' => $passState,
            'initialQrPayload' => $tokenData['qr_payload'] ?? '',
            'initialManualToken' => $tokenData['manual_token'] ?? '',
            'initialFormattedManualToken' => $tokenData['formatted_manual_token'] ?? '',
            'initialExpiresAt' => $tokenData['expires_at'] ?? now()->addSeconds(DynamicQrService::TOKEN_TTL_SECONDS)->toIso8601String(),
            'ttl' => DynamicQrService::TOKEN_TTL_SECONDS,
        ]);
    }

    /**
     * API endpoint called every ~30 seconds by Alpine.js to refresh the rolling token.
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $student = $user->student;

        if (!$user->is_active || !$user->is_eligible) {
            return response()->json([
                'success' => false,
                'code' => 'ATTENDANCE_NOT_ELIGIBLE',
                'message' => 'Account is deactivated or not in good standing.',
            ], 403);
        }

        $eventId = $request->input('event_id');
        $event = null;

        if ($eventId) {
            $event = \App\Models\Event::where('id', $eventId)->first();
        } else {
            // Find user's first approved active, non-ended event
            $firstApproved = \App\Models\EventRegistration::with('event')
                ->where('status', 'approved')
                ->where(function ($q) use ($user, $student) {
                    $q->where('user_id', $user->id)
                      ->orWhere('email', strtolower($user->email));
                    if ($student) {
                        $q->orWhere('email', strtolower($student->email));
                    }
                })
                ->whereHas('event', function ($eq) {
                    $eq->where(function ($sub) {
                        $sub->where('is_published', true)->orWhere('status', 'published')->orWhere('status', 'ongoing');
                    })->whereNotIn('status', ['draft', 'archived', 'cancelled', 'completed']);
                })
                ->latest()
                ->get()
                ->first(function ($reg) {
                    return $reg->event && !$reg->event->isEnded();
                });

            $event = $firstApproved?->event;
        }

        if (!$event) {
            return response()->json([
                'success' => false,
                'code' => 'EVENT_NOT_OPEN',
                'message' => 'Join an event through Events & Activities to access its Dynamic Attendance QR Code.',
            ], 403);
        }

        if ($event->isEnded()) {
            return response()->json([
                'success' => false,
                'code' => 'EVENT_ENDED',
                'message' => 'This event has ended. The attendance pass is no longer available.',
                'event_ended' => true,
            ], 403);
        }

        if (in_array($event->status, ['cancelled', 'archived', 'draft'])) {
            return response()->json([
                'success' => false,
                'code' => 'EVENT_CANCELLED',
                'message' => 'This event has been cancelled or is inactive.',
            ], 403);
        }

        // Verify registration for this event is approved
        $registration = \App\Models\EventRegistration::where('event_id', $event->id)
            ->where('status', 'approved')
            ->where(function ($q) use ($user, $student) {
                $q->where('user_id', $user->id)
                  ->orWhere('email', strtolower($user->email));
                if ($student) {
                    $q->orWhere('email', strtolower($student->email));
                }
            })
            ->first();

        if (!$registration) {
            return response()->json([
                'success' => false,
                'code' => 'REGISTRATION_REQUIRED',
                'message' => 'Join this event to access your Dynamic Attendance QR Code.',
            ], 403);
        }

        $tokenData = $this->dynamicQrService->generateTokenForUser($user, $event);

        return response()->json([
            'success' => true,
            'payload' => $tokenData['qr_payload'],
            'manual_token' => $tokenData['manual_token'],
            'formatted_manual_token' => $tokenData['formatted_manual_token'],
            'expires_at' => $tokenData['expires_at'],
            'ttl' => $tokenData['ttl'],
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
