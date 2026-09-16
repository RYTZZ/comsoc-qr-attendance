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

        $redirect = match ($user->role) {
            'student' => route('student.dashboard'),
            default => route('admin.dashboard'),
        };

        return redirect()->intended($redirect);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            \App\Services\AuditLogger::log('auth.logout', $user, [], [
                'name' => $user->name,
                'role' => $user->role,
            ]);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
