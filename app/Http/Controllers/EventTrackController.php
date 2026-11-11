<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventTrack;
use App\Models\EventTrackAttendance;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventTrackController extends Controller
{
    /**
     * List all sub-sessions/tracks for an event with attendance & remaining seats
     */
    public function index(Event $event): JsonResponse
    {
        $viewer = auth()->user();

        $tracks = $event->tracks()->where('is_active', true)->orderBy('starts_at')->get()->map(function ($t) use ($viewer) {
            $userAttendance = $viewer ? $t->attendances()->where('user_id', $viewer->id)->first() : null;

            return [
                'id' => $t->id,
                'title' => $t->title,
                'track_category' => $t->track_category,
                'description' => $t->description,
                'speaker_name' => $t->speaker_name,
                'room_or_venue' => $t->room_or_venue,
                'starts_at' => $t->starts_at?->toIso8601String(),
                'ends_at' => $t->ends_at?->toIso8601String(),
                'starts_at_formatted' => $t->starts_at?->format('h:i A'),
                'ends_at_formatted' => $t->ends_at?->format('h:i A'),
                'capacity_limit' => $t->capacity_limit,
                'seats_remaining' => $t->seatsRemaining(),
                'is_full' => $t->isFull(),
                'exp_points' => $t->exp_points,
                'user_status' => $userAttendance?->status, // 'registered', 'attended', null
            ];
        });

        return response()->json([
            'event_id' => $event->id,
            'event_name' => $event->name,
            'tracks' => $tracks,
        ]);
    }

    /**
     * Pre-register or reserve a seat in a track/workshop
     */
    public function register(Request $request, EventTrack $track): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($track->isFull()) {
            return response()->json(['message' => 'This workshop or track session is already at full capacity.'], 422);
        }

        $existing = EventTrackAttendance::where('event_track_id', $track->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing && $existing->status !== 'cancelled') {
            return response()->json(['message' => 'You are already registered for this session.'], 422);
        }

        $record = EventTrackAttendance::updateOrCreate(
            ['event_track_id' => $track->id, 'user_id' => $user->id],
            ['status' => 'registered']
        );

        AuditLogger::log('event.track.registered', $track, ['user_id' => $user->id]);

        return response()->json([
            'success' => true,
            'message' => 'Seat successfully reserved for ' . $track->title,
            'attendance' => $record,
            'seats_remaining' => $track->fresh()->seatsRemaining(),
        ]);
    }

    /**
     * Check-in / scan attendance for a specific session/track
     */
    public function scan(Request $request, EventTrack $track): JsonResponse
    {
        $officer = $request->user();
        if (!$officer || !$officer->isAdmin()) {
            return response()->json(['message' => 'Unauthorized scanner.'], 403);
        }

        $validated = $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
        ]);

        $attendee = User::findOrFail($validated['user_id']);

        $attendance = EventTrackAttendance::updateOrCreate(
            ['event_track_id' => $track->id, 'user_id' => $attendee->id],
            [
                'status' => 'attended',
                'attended_at' => now(),
                'scanned_by' => $officer->id,
            ]
        );

        AuditLogger::log('event.track.checked_in', $track, [
            'attendee_id' => $attendee->id,
            'officer_id' => $officer->id,
            'exp_awarded' => $track->exp_points,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Checked into ' . $track->title,
            'attendee' => [
                'name' => $attendee->name,
                'role' => $attendee->role,
            ],
            'attendance' => $attendance,
        ]);
    }
}
