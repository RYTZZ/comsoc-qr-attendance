<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\EmailVerificationOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new six-digit email verification OTP notification.
     */
    public function store(Request $request, EmailVerificationOtpService $otpService): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $result = $otpService->issueAndSend($user, $request->ip(), enforceCooldown: true);

        if (! $result['sent']) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'cooldown_seconds' => $result['cooldown_seconds'] ?? 0,
                    'cooldown_expires_at' => ($result['cooldown_seconds'] ?? 0) > 0 ? now()->addSeconds($result['cooldown_seconds'])->getTimestamp() : null,
                    'server_time' => now()->getTimestamp(),
                ], 429);
            }

            return back()
                ->withErrors(['resend' => $result['message']])
                ->with('error', $result['message'])
                ->with('cooldown_seconds', $result['cooldown_seconds'] ?? 0)
                ->with('cooldown_expires_at', ($result['cooldown_seconds'] ?? 0) > 0 ? now()->addSeconds($result['cooldown_seconds'])->getTimestamp() : null);
        }

        $cooldown = $result['cooldown_seconds'] ?? EmailVerificationOtpService::RESEND_COOLDOWN_SECONDS;
        $expiresAt = now()->addSeconds($cooldown)->getTimestamp();

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'cooldown_seconds' => $cooldown,
                'cooldown_expires_at' => $expiresAt,
                'server_time' => now()->getTimestamp(),
            ]);
        }

        return back()
            ->with('status', 'verification-link-sent')
            ->with('success', $result['message'])
            ->with('cooldown_seconds', $cooldown)
            ->with('cooldown_expires_at', $expiresAt);
    }
}

