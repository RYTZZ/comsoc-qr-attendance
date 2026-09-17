<?php

namespace App\Http\Controllers\Kiosk;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use App\Models\Event;
use App\Models\Kiosk;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceKioskController extends Controller
{
    public function __construct(private AttendanceService $attendanceService) {}

    public function show(Kiosk $kiosk): View
    {
        abort_unless($kiosk->is_active, 404);

        $user = auth()->user();
        if ($user && $user->role === 'kiosk' && $user->kiosk && $user->kiosk->id !== $kiosk->id) {
            abort(403, 'Unauthorized kiosk terminal.');
        }

        if ($user) {
            $user->update(['last_activity_at' => now()]);
        }
        $kiosk->update(['last_activity_at' => now()]);

        $query = Event::where('is_published', true)->with('attendanceSessions');
        if ($kiosk->assigned_event_id) {
            $query->where('id', $kiosk->assigned_event_id);
        } else {
            $query->orderByDesc('event_date')->orderByDesc('created_at');
        }
        $events = $query->get();

        if ($events->isEmpty() && $kiosk->assignedEvent) {
            $events = collect([$kiosk->assignedEvent->load('attendanceSessions')]);
        }

        return view('kiosk.attendance', compact('kiosk', 'events'));
    }

    public function scan(Request $request, Kiosk $kiosk): JsonResponse
    {
        abort_unless($kiosk->is_active, 404);

        $token = trim($request->input('token', ''));
        if (preg_match('/[A-Za-z0-9]{48}/', $token, $matches)) {
            $token = $matches[0];
        }

        $request->merge(['token' => $token]);

        $request->validate([
            'token' => 'required|string|size:48',
            'event_id' => 'required|exists:events,id',
            'session_id' => 'required|exists:attendance_sessions,id',
        ]);

        $event = Event::findOrFail($request->event_id);
        $session = AttendanceSession::findOrFail($request->session_id);
        $staff = auth()->user() ?? $kiosk->assignedStaff ?? $kiosk->user;

        if (!$staff) {
            return response()->json(['success' => false, 'message' => 'No staff assigned to kiosk.'], 403);
        }

        $kiosk->update(['last_activity_at' => now()]);
        if (auth()->check()) {
            auth()->user()->update(['last_activity_at' => now()]);
        }

        $result = $this->attendanceService->scan(
            $request->token,
            $event,
            $session,
            $kiosk,
            $staff
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function healthCheck(): JsonResponse
    {
        return response()->json(['status' => 'online', 'timestamp' => now()->toISOString()]);
    }
}
