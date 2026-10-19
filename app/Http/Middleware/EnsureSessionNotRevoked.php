<?php

namespace App\Http\Middleware;

use App\Models\UserSession;
use App\Services\SessionSecurityService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionNotRevoked
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && $request->hasSession()) {
            $sessionId = $request->session()->getId();
            $sessionIdHash = hash('sha256', $sessionId);

            // Check if this session has been revoked
            $userSession = UserSession::where('session_id_hash', $sessionIdHash)->first();

            if ($userSession && $userSession->isRevoked()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->wantsJson()) {
                    return response()->json(['message' => 'Your session has been revoked.'], 401);
                }

                return redirect()->route('login')->withErrors([
                    'email' => 'Your session has been revoked. Please log in again.',
                ]);
            }

            // Update session activity asynchronously / throttled
            if ($userSession) {
                app(SessionSecurityService::class)->touchActivity($sessionId);
            }
        }

        return $next($request);
    }
}
