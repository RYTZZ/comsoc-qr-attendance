<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\EmailVerificationOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationOtpController extends Controller
{
    /**
     * Validate a submitted six-digit email verification OTP.
     */
    public function store(Request $request, EmailVerificationOtpService $otpService): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        $rawCode = (string) $request->input('code', '');
        if ($rawCode === '' && is_array($request->input('digits'))) {
            $rawCode = implode('', array_map('strval', $request->input('digits')));
        }

        $request->merge(['code' => trim($rawCode)]);

        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
        ], [
            'code.required' => 'Please enter the 6-digit verification code sent to your email.',
            'code.size' => 'The verification code must be exactly 6 digits.',
            'code.regex' => 'The verification code must contain only numeric digits (0–9).',
        ]);

        $outcome = $otpService->verify($user, $validated['code'], $request->ip());

        if (! $outcome['verified']) {
            return back()
                ->withErrors(['code' => $outcome['message']])
                ->with('error', $outcome['message']);
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1')
            ->with('status', 'email-verified')
            ->with('success', $outcome['message']);
    }
}

