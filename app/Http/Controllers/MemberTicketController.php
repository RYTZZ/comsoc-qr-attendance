<?php

namespace App\Http\Controllers;

use App\Models\EventRegistration;
use App\Services\DynamicQrService;
use App\Services\QrPngRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberTicketController extends Controller
{
    public function __construct(
        private DynamicQrService $dynamicQrService
    ) {}

    public function index(): View
    {
        $user = auth()->user();
        $student = $user->student;

        $tickets = EventRegistration::with(['event.attendanceSessions'])
            ->where(function ($q) use ($user, $student) {
                $q->where('user_id', $user->id)
                  ->orWhere('email', strtolower($user->email));
                if ($student) {
                    $q->orWhere('email', strtolower($student->email));
                }
            })
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('tickets.index', compact('tickets'));
    }

    public function show(EventRegistration $ticket): View
    {
        $user = auth()->user();
        $student = $user->student;

        $canView = $ticket->user_id === $user->id
            || strtolower($ticket->email) === strtolower($user->email)
            || ($student && strtolower($ticket->email) === strtolower($student->email))
            || $user->isAdmin();

        abort_unless($canView, 403);

        $event = $ticket->event;
        $attendanceSessions = $event->attendanceSessions()->where('is_active', true)->orderBy('opens_at')->get();

        $attendanceRecords = collect();
        if ($ticket->qr_token || $ticket->user_id) {
            $attendanceRecords = $event->attendanceRecords()
                ->where(function ($q) use ($ticket, $student) {
                    if ($ticket->qr_token) {
                        $q->where('qr_token', $ticket->qr_token);
                    }
                    if ($ticket->user_id) {
                        $q->orWhere('user_id', $ticket->user_id);
                    }
                    $q->orWhere(function ($sub) use ($ticket) {
                        $sub->where('participant_type', 'non_student')
                            ->where('participant_id', $ticket->id);
                    });
                    if ($student) {
                        $subQueryStudent = function ($sub2) use ($student) {
                            $sub2->where('participant_type', 'student')
                                 ->where('participant_id', $student->id);
                        };
                        $q->orWhere($subQueryStudent);
                    }
                })
                ->get();
        }

        // Generate event-specific dynamic rolling QR token for approved ticket holders
        $dynamicPass = null;
        if ($ticket->status === 'approved' && $user->is_active && $user->is_eligible) {
            $tokenData = $this->dynamicQrService->generateTokenForUser($user, $event);
            $dynamicPass = [
                'payload' => $tokenData['qr_payload'],
                'manual_token' => $tokenData['manual_token'],
                'formatted_manual_token' => $tokenData['formatted_manual_token'],
                'expires_at' => $tokenData['expires_at'],
                'ttl' => DynamicQrService::TOKEN_TTL_SECONDS,
            ];
        }

        return view('tickets.show', compact('ticket', 'event', 'attendanceSessions', 'attendanceRecords', 'dynamicPass'));
    }

    /**
     * API endpoint called every 30 seconds to refresh the event-specific rolling dynamic QR pass.
     */
    public function refreshQr(EventRegistration $ticket, Request $request): JsonResponse
    {
        $user = $request->user();
        $student = $user->student;

        $canView = $ticket->user_id === $user->id
            || strtolower($ticket->email) === strtolower($user->email)
            || ($student && strtolower($ticket->email) === strtolower($student->email))
            || $user->isAdmin();

        if (!$canView) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this event pass.',
            ], 403);
        }

        if ($ticket->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Registration pass is not active or approved.',
            ], 403);
        }

        if (!$user->is_active || !$user->is_eligible) {
            return response()->json([
                'success' => false,
                'message' => 'Member account is inactive or ineligible.',
            ], 403);
        }

        $event = $ticket->event;
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

    public function downloadQr(EventRegistration $ticket)
    {
        // Direct static download is discontinued for security; redirect to dynamic pass view.
        return redirect()->route('tickets.show', $ticket)
            ->with('info', 'Static QR pass downloads have been replaced with the secure 30-second Dynamic QR Pass.');
    }
}
