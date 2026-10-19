<?php

namespace App\Http\Controllers;

use App\Models\UserSession;
use App\Services\SessionSecurityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserSessionController extends Controller
{
    public function __construct(
        protected SessionSecurityService $sessionService
    ) {}

    /**
     * Display the active sessions for the authenticated user.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $currentSessionId = $request->session()->getId();

        $sessions = UserSession::where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->orderByDesc('last_active_at')
            ->get();

        return view('profile.sessions', compact('sessions', 'currentSessionId'));
    }

    /**
     * Poll for session validity and recent security notifications for the authenticated user.
     */
    public function pollAlerts(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->session()->getId();
        $sessionIdHash = hash('sha256', $sessionId);

        // Verification: Is this session active and valid?
        $isValidSession = ($user->active_session_hash === null || $user->active_session_hash === $sessionIdHash);
        $userSession = UserSession::where('session_id_hash', $sessionIdHash)->first();
        if ($userSession && $userSession->isRevoked()) {
            $isValidSession = false;
        }

        if (!$isValidSession) {
            return response()->json([
                'valid' => false,
                'session_replaced' => true,
                'message' => 'Your ComSoc Hub account has been logged in on another device. For your security, this session is no longer active.',
            ], 401);
        }

        // Get unread security alerts created in the last 24 hours
        $alerts = $user->appNotifications()
            ->where('type', 'security_alert')
            ->whereNull('read_at')
            ->where('created_at', '>=', now()->subHours(24))
            ->latest()
            ->get();

        return response()->json([
            'valid' => true,
            'session_replaced' => false,
            'unread_alerts' => $alerts,
            'unread_count' => $user->appNotifications()->whereNull('read_at')->count(),
        ]);
    }

    /**
     * Revoke a specific session belonging to the authenticated user.
     */
    public function destroy(Request $request, UserSession $session): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        // Enforce server-side ownership
        if ($session->user_id !== $user->id) {
            abort(403, 'Unauthorized access to session record.');
        }

        $currentSessionId = $request->session()->getId();
        $result = $this->sessionService->revokeSession($user, $session, $currentSessionId);

        if ($result['is_current']) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'redirect' => route('login'),
                    'message' => $result['message'],
                ]);
            }

            return redirect()->route('login')->with('status', 'Current session revoked. You have been logged out.');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
            ]);
        }

        return back()->with('status', $result['message']);
    }
}
