<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityAttendance;
use App\Models\ActivitySession;
use App\Services\ActivityAttendanceRewardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizerActivityScannerController extends Controller
{
    public function __construct(
        private ActivityAttendanceRewardService $attendanceService
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $activitiesQuery = Activity::with('sessions')
            ->whereNotIn('status', ['draft', 'archived'])
            ->orderByDesc('starts_at');

        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            $activitiesQuery->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhereHas('organizers', fn ($oq) => $oq->where('users.id', $user->id));
            });
        }

        $activities = $activitiesQuery->get();
        $selectedActivity = null;
        $selectedSession = null;

        if ($request->filled('activity_id')) {
            $selectedActivity = $activities->firstWhere('id', $request->input('activity_id'));
        } elseif ($activities->isNotEmpty()) {
            $selectedActivity = $activities->first();
        }

        if ($selectedActivity) {
            if ($request->filled('session_id')) {
                $selectedSession = $selectedActivity->sessions->firstWhere('id', $request->input('session_id'));
            } else {
                $selectedSession = $selectedActivity->sessions->first();
            }
        }

        $recentAttendances = collect();
        if ($selectedSession) {
            $recentAttendances = ActivityAttendance::with('user')
                ->where('activity_session_id', $selectedSession->id)
                ->latest('updated_at')
                ->limit(25)
                ->get();
        }

        return view('organizer.activity-scanner', compact(
            'activities',
            'selectedActivity',
            'selectedSession',
            'recentAttendances'
        ));
    }

    public function scan(Request $request): JsonResponse
    {
        $payload = $request->input('qr_payload') ?? $request->input('token') ?? $request->input('manual_token');
        $request->merge(['qr_payload' => $payload]);

        $validated = $request->validate([
            'activity_id' => ['required', 'uuid', 'exists:activities,id'],
            'session_id' => ['required', 'uuid', 'exists:activity_sessions,id'],
            'qr_payload' => ['required', 'string'],
            'mode' => ['nullable', 'in:auto,in,out'],
            'allow_walk_in' => ['nullable', 'boolean'],
        ]);

        $activity = Activity::findOrFail($validated['activity_id']);
        $session = ActivitySession::findOrFail($validated['session_id']);

        $result = $this->attendanceService->processSessionQrScan(
            activity: $activity,
            session: $session,
            qrTokenPayload: $validated['qr_payload'],
            organizer: $request->user(),
            mode: $validated['mode'] ?? 'auto',
            allowWalkInOverride: (bool) ($validated['allow_walk_in'] ?? false)
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}

