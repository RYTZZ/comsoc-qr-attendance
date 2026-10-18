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
     * Optionally scoped to an event (via ?event_id=...).
     */
    public function show(Request $request): View
    {
        $user = $request->user();
        $student = $user->student;
        $eventId = $request->input('event_id');
        $event = null;
        $eventRegistration = null;
        $passState = 'active'; // 'active', 'not_joined', 'pending', 'revoked'

        if ($eventId) {
            $event = \App\Models\Event::where('id', $eventId)->first();
            if ($event) {
                $eventRegistration = \App\Models\EventRegistration::where('event_id', $event->id)
                    ->where(function ($q) use ($user, $student) {
                        $q->where('user_id', $user->id)
                          ->orWhere('email', strtolower($user->email));
                        if ($student) {
                            $q->orWhere('email', strtolower($student->email));
                        }
                    })
                    ->first();

                if (!$eventRegistration) {
                    $passState = 'not_joined';
                } elseif ($eventRegistration->status === 'pending') {
                    $passState = 'pending';
                } elseif (in_array($eventRegistration->status, ['rejected', 'cancelled', 'revoked'])) {
                    $passState = 'revoked';
                } elseif (!$user->is_active || !$user->is_eligible) {
                    $passState = 'revoked';
                }
            }
        }

        // Only generate valid token if state is active
        $tokenData = null;
        if ($passState === 'active') {
            $tokenData = $this->dynamicQrService->generateTokenForUser($user, $event);
        }

        return view('member.dynamic_qr', [
            'user' => $user,
            'event' => $event,
            'eventRegistration' => $eventRegistration,
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
                'message' => 'Account is deactivated or not in good standing.',
            ], 403);
        }

        $eventId = $request->input('event_id');
        $event = null;
        if ($eventId) {
            $event = \App\Models\Event::where('id', $eventId)->first();
            if ($event) {
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
                        'message' => 'Join this event to access your Dynamic Attendance QR Code.',
                    ], 403);
                }
            }
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
