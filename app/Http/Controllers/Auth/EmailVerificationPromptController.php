<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\EmailVerificationOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt with six-digit OTP state.
     */
    public function __invoke(Request $request, EmailVerificationOtpService $otpService): RedirectResponse|View
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $cooldownSeconds = $otpService->remainingResendCooldownSeconds($user);

        return view('auth.verify-email', [
            'user' => $user,
            'cooldownSeconds' => $cooldownSeconds,
            'expirationMinutes' => EmailVerificationOtpService::EXPIRATION_MINUTES,
            'maxAttempts' => EmailVerificationOtpService::MAX_ATTEMPTS,
        ]);
    }
}

