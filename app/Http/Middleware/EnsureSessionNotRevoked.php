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
            $user = Auth::user();
            $sessionId = $request->session()->getId();
            $sessionIdHash = hash('sha256', $sessionId);
            $storedSessionHash = $request->session()->get('current_session_hash');

            // Check if this session has been displaced or explicitly revoked
            // A session matches if user's active hash equals the request session ID hash OR stored session hash
            $matchesActive = ($user->active_session_hash === null) 
                || ($user->active_session_hash === $sessionIdHash) 
                || ($storedSessionHash && $user->active_session_hash === $storedSessionHash);

            $isDisplaced = !$matchesActive;

            $userSession = UserSession::where('session_id_hash', $sessionIdHash)
                ->orWhere(function ($q) use ($storedSessionHash) {
                    if ($storedSessionHash) {
                        $q->where('session_id_hash', $storedSessionHash);
                    }
                })
                ->first();
            $isExplicitlyRevoked = $userSession && $userSession->isRevoked();

            if ($isDisplaced || $isExplicitlyRevoked) {
                // If the user is specifically trying to log out, let the logout request proceed cleanly to end the session
                if ($request->is('logout')) {
                    return $next($request);
                }

                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson() || $request->is('*/poll*') || $request->is('api/*')) {
                    return response()->json([
                        'session_replaced' => true,
                        'message' => 'Your ComSoc Hub account has been logged in on another device. For your security, this session is no longer active.',
                    ], 401);
                }

                return redirect()->route('login')->withErrors([
                    'email' => 'Your ComSoc Hub account has been logged in on another device. For your security, this session is no longer active.',
                ]);
            }

            // Update session activity asynchronously / throttled for genuine user requests (exclude background poll)
            if ($userSession && !$request->is('*/poll*')) {
                app(SessionSecurityService::class)->touchActivity($sessionId);
            }
        }

        return $next($request);
    }
}
