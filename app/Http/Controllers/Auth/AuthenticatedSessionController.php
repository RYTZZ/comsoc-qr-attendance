<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = Auth::user();

        if (!$user->is_active) {
            \App\Services\AuditLogger::log('auth.login_blocked_inactive', $user, [], ['email' => $user->email]);
            Auth::logout();
            return back()->withErrors(['email' => 'Your account has been deactivated.']);
        }

        $user->update(['last_activity_at' => now()]);

        $userSession = app(\App\Services\SessionSecurityService::class)->handleLogin($user, $request);
        $request->session()->put('current_session_hash', $userSession->session_id_hash);

        if ($user->role === 'kiosk' && $user->kiosk) {
            $user->kiosk->update(['last_activity_at' => now()]);
        }

        \App\Services\AuditLogger::log('auth.login', $user, [], [
            'name' => $user->name,
            'role' => $user->role,
            'ip' => $request->ip(),
        ]);

        if ($user->role === 'kiosk') {
            $kiosk = $user->kiosk;
            if ($kiosk) {
                return redirect()->route('kiosk.attendance', $kiosk);
            }
            return redirect()->route('kiosk.hub');
        }

        if (in_array($user->role, ['super_admin', 'president', 'executive_secretary', 'admin', 'treasurer', 'staff', 'event_staff', 'auditor'])) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            \App\Services\AuditLogger::log('auth.logout', $user, [], [
                'name' => $user->name,
                'role' => $user->role,
            ]);

            $sessionId = $request->session()->getId();
            $sessionIdHash = hash('sha256', $sessionId);
            $storedSessionHash = $request->session()->get('current_session_hash');

            // If the logging-out session is the user's currently registered active session, clear it.
            // If this was an already-displaced session logging out, do NOT clear the new device's active session!
            if ($user->active_session_hash === $sessionIdHash || ($storedSessionHash && $user->active_session_hash === $storedSessionHash)) {
                $user->update(['active_session_hash' => null]);
            }

            // Mark this specific session revoked
            \App\Models\UserSession::where('user_id', $user->id)
                ->where(function ($query) use ($sessionIdHash, $storedSessionHash) {
                    $query->where('session_id_hash', $sessionIdHash);
                    if ($storedSessionHash) {
                        $query->orWhere('session_id_hash', $storedSessionHash);
                    }
                })
                ->update(['revoked_at' => now()]);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
