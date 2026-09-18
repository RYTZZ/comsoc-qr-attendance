<?php

namespace App\Http\Controllers\Kiosk;

use App\Http\Controllers\Controller;
use App\Models\Kiosk;
use App\Models\SnackInventory;
use App\Models\SnackSession;
use App\Services\SnackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SnackKioskController extends Controller
{
    public function __construct(private SnackService $snackService) {}

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

        $query = SnackSession::where('is_active', true)->with(['event', 'inventories']);
        if ($kiosk->assigned_event_id) {
            $query->where('event_id', $kiosk->assigned_event_id);
        } else {
            $query->whereHas('event', fn($q) => $q->where('event_date', now()->toDateString()));
        }
        $sessions = $query->get();

        return view('kiosk.snack', compact('kiosk', 'sessions'));
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
            'snack_session_id' => 'required|exists:snack_sessions,id',
            'snack_inventory_id' => 'required|exists:snack_inventories,id',
        ]);

        $session = SnackSession::findOrFail($request->snack_session_id);

        if ($kiosk->assigned_event_id && $kiosk->assigned_event_id !== $session->event_id) {
            return response()->json(['success' => false, 'message' => 'This kiosk is only authorized for its assigned event.'], 403);
        }

        $inventory = SnackInventory::findOrFail($request->snack_inventory_id);
        $staff = auth()->user() ?? $kiosk->assignedStaff ?? $kiosk->user;

        if (!$staff) {
            return response()->json(['success' => false, 'message' => 'No staff assigned to kiosk.'], 403);
        }

        $kiosk->update(['last_activity_at' => now()]);
        if (auth()->check()) {
            auth()->user()->update(['last_activity_at' => now()]);
        }

        $result = $this->snackService->claim($request->token, $session, $inventory, $kiosk, $staff);

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
