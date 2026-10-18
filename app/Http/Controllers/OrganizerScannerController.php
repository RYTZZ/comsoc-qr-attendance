<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Event;
use App\Services\EventAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizerScannerController extends Controller
{
    public function __construct(
        private EventAttendanceService $attendanceService
    ) {}

    /**
     * Mobile-optimized workspace for Event Organizers.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Super Admin & COMSOC Admin see all active events; Event Organizers see assigned only
        $eventsQuery = Event::with('attendanceSessions')
            ->where('status', '!=', 'cancelled')
            ->orderBy('event_date', 'desc');

        if (!$user->isSuperAdmin() && !$user->isComsocAdmin()) {
            $eventsQuery->whereHas('organizers', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        $events = $eventsQuery->get();
        $selectedEvent = null;
        $activeSession = null;

        if ($request->filled('event_id')) {
            $selectedEvent = $events->firstWhere('id', $request->input('event_id'));
        } elseif ($events->isNotEmpty()) {
            $selectedEvent = $events->first();
        }

        if ($selectedEvent) {
            if ($request->filled('session_id')) {
                $activeSession = $selectedEvent->attendanceSessions->firstWhere('id', $request->input('session_id'));
            } else {
                $activeSession = $selectedEvent->attendanceSessions->first();
            }
        }

        // Attendance stats for selected session
        $attendanceStats = [
            'total_in' => 0,
            'total_out' => 0,
            'present' => 0,
            'late' => 0,
        ];

        if ($selectedEvent) {
            $attendanceStats['total_in'] = AttendanceRecord::where('event_id', $selectedEvent->id)->where('action', 'in')->count();
            $attendanceStats['total_out'] = AttendanceRecord::where('event_id', $selectedEvent->id)->where('action', 'out')->count();
            if ($activeSession) {
                $attendanceStats['present'] = AttendanceRecord::where('attendance_session_id', $activeSession->id)->where('status', 'present')->count();
                $attendanceStats['late'] = AttendanceRecord::where('attendance_session_id', $activeSession->id)->where('status', 'late')->count();
            }
        }

        return view('organizer.scanner', [
            'events' => $events,
            'selectedEvent' => $selectedEvent,
            'activeSession' => $activeSession,
            'stats' => $attendanceStats,
        ]);
    }

    /**
     * API endpoint to process camera scan submission.
     */
    public function scan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_id' => 'required|uuid|exists:events,id',
            'session_id' => 'required|uuid|exists:attendance_sessions,id',
            'qr_payload' => 'required|string',
        ]);

        $event = Event::findOrFail($validated['event_id']);
        $session = AttendanceSession::findOrFail($validated['session_id']);
        $organizer = $request->user();

        $result = $this->attendanceService->processScan(
            event: $event,
            session: $session,
            qrTokenPayload: $validated['qr_payload'],
            organizer: $organizer
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Submit missing time-out correction.
     */
    public function correctMissingTimeOut(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'record_id' => 'required|uuid|exists:attendance_records,id',
            'reason' => 'required|string|min:5|max:500',
        ]);

        $inRecord = AttendanceRecord::with(['event', 'attendanceSession', 'user'])->findOrFail($validated['record_id']);
        $organizer = $request->user();

        $result = $this->attendanceService->correctMissingTimeOut($inRecord, $validated['reason'], $organizer);

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
