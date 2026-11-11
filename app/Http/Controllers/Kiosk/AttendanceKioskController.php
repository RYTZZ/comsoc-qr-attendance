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

        $user = auth()->user();
        if ($user && $user->role === 'kiosk' && $user->kiosk && $user->kiosk->id !== $kiosk->id) {
            abort(403, 'Unauthorized kiosk terminal.');
        }

        $rawToken = trim((string) $request->input('token', ''));
        $token = $rawToken;

        if (str_starts_with($rawToken, '{') && str_ends_with($rawToken, '}')) {
            $decodedJson = json_decode($rawToken, true);
            if (is_array($decodedJson) && !empty($decodedJson['token'])) {
                $token = trim((string) $decodedJson['token']);
            }
        }

        if (preg_match('/[A-Za-z0-9]{40,64}/', $token, $matches)) {
            $token = $matches[0];
        } elseif (str_contains($token, 'token=')) {
            $parsedUrl = parse_url($token);
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
                if (!empty($queryParams['token'])) {
                    $token = trim((string) $queryParams['token']);
                }
            }
        }

        $request->merge(['token' => $token]);

        $request->validate([
            'token' => 'required|string|min:16|max:64',
            'event_id' => 'required|exists:events,id',
            'session_id' => 'required|exists:attendance_sessions,id',
        ]);

        if ($kiosk->assigned_event_id && $kiosk->assigned_event_id !== $request->event_id) {
            return response()->json(['success' => false, 'message' => 'This kiosk is only authorized for its assigned event.'], 403);
        }

        $event = Event::findOrFail($request->event_id);
        $session = AttendanceSession::findOrFail($request->session_id);

        if ($session->event_id !== $event->id) {
            return response()->json(['success' => false, 'message' => 'Invalid attendance session for this event.', 'code' => 'session_mismatch'], 422);
        }

        if (!$session->is_active) {
            return response()->json(['success' => false, 'message' => 'This attendance session is inactive.', 'code' => 'session_inactive'], 422);
        }

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

    public function manualLookup(Request $request, Kiosk $kiosk): JsonResponse
    {
        abort_unless($kiosk->is_active, 404);

        $query = trim((string) $request->input('query', ''));
        if (strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $students = \App\Models\Student::where(function ($q) use ($query) {
                $q->whereLike('student_number', "%{$query}%")
                  ->orWhereLike('first_name', "%{$query}%")
                  ->orWhereLike('last_name', "%{$query}%");
            })
            ->with(['memberships.activeQrCode'])
            ->limit(8)
            ->get()
            ->map(function ($s) {
                $activeMem = $s->memberships->firstWhere('status', 'active');
                $token = $activeMem?->activeQrCode?->token;
                return [
                    'id' => $s->id,
                    'name' => $s->full_name,
                    'student_number' => $s->student_number,
                    'program' => $s->program,
                    'year_level' => $s->year_level,
                    'has_active_qr' => !empty($token),
                    'token' => $token,
                ];
            });

        return response()->json(['results' => $students]);
    }

    public function healthCheck(): JsonResponse
    {
        return response()->json(['status' => 'online', 'timestamp' => now()->toISOString()]);
    }

    /**
     * Preload event manifest for offline scanning validation
     */
    public function offlineManifest(Request $request, Kiosk $kiosk): JsonResponse
    {
        abort_unless($kiosk->is_active, 404);

        $eventId = $request->input('event_id', $kiosk->assigned_event_id);
        $event = $eventId ? Event::find($eventId) : null;

        // Fetch active students with membership tokens
        $students = \App\Models\Student::with(['memberships.activeQrCode', 'user'])
            ->get()
            ->map(function ($s) {
                $activeMem = $s->memberships->firstWhere('status', 'active');
                $token = $activeMem?->activeQrCode?->token;
                $user = $s->user;
                return [
                    'id' => $s->id,
                    'token' => $token ?: $s->id,
                    'student_number' => $s->student_number,
                    'name' => $s->first_name . ' ' . $s->last_name,
                    'program' => $s->program,
                    'year_level' => $s->year_level,
                    'avatar' => $user ? \App\Services\AvatarService::getUrl($user->avatar_seed, $user->avatar_style ?? 'adventurer') : null,
                ];
            });

        return response()->json([
            'event_id' => $event?->id,
            'event_name' => $event?->name,
            'total_attendees' => $students->count(),
            'manifest' => $students,
            'generated_at' => now()->toISOString(),
        ]);
    }

    /**
     * Batch-sync attendance scans recorded during offline operation
     */
    public function batchSync(Request $request, Kiosk $kiosk): JsonResponse
    {
        abort_unless($kiosk->is_active, 404);

        $validated = $request->validate([
            'scans' => ['required', 'array', 'min:1', 'max:500'],
            'scans.*.token' => ['required', 'string'],
            'scans.*.event_id' => ['required', 'uuid', 'exists:events,id'],
            'scans.*.session_id' => ['nullable', 'uuid', 'exists:attendance_sessions,id'],
            'scans.*.timestamp' => ['nullable', 'string'],
        ]);

        $staff = auth()->user();
        $processed = [];
        $successCount = 0;
        $failedCount = 0;

        foreach ($validated['scans'] as $scanData) {
            $event = Event::find($scanData['event_id']);
            $session = !empty($scanData['session_id']) ? AttendanceSession::find($scanData['session_id']) : null;

            if (!$event) {
                $processed[] = ['token' => $scanData['token'], 'success' => false, 'message' => 'Event not found'];
                $failedCount++;
                continue;
            }

            if (!$session) {
                $session = $event->activeAttendanceSession();
            }

            try {
                $res = $this->attendanceService->scan(
                    $scanData['token'],
                    $event,
                    $session,
                    $kiosk,
                    $staff
                );

                if ($res['success']) {
                    $successCount++;
                } else {
                    $failedCount++;
                }

                $processed[] = [
                    'token' => $scanData['token'],
                    'success' => $res['success'],
                    'message' => $res['message'] ?? '',
                ];
            } catch (\Throwable $e) {
                $failedCount++;
                $processed[] = [
                    'token' => $scanData['token'],
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'synced_count' => $successCount,
            'failed_count' => $failedCount,
            'results' => $processed,
            'synced_at' => now()->toISOString(),
        ]);
    }
}
