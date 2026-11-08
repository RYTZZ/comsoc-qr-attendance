<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\EmailVerificationOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new six-digit email verification OTP notification.
     */
    public function store(Request $request, EmailVerificationOtpService $otpService): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $result = $otpService->issueAndSend($user, $request->ip(), enforceCooldown: true);

        if (! $result['sent']) {
            return back()
                ->withErrors(['resend' => $result['message']])
                ->with('error', $result['message'])
                ->with('cooldown_seconds', $result['cooldown_seconds'] ?? 0);
        }

        return back()
            ->with('status', 'verification-link-sent')
            ->with('success', $result['message'])
            ->with('cooldown_seconds', $result['cooldown_seconds'] ?? EmailVerificationOtpService::RESEND_COOLDOWN_SECONDS);
    }
}

