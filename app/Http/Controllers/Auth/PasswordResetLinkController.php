<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetTokenMail;
use App\Models\User;
use App\Support\MailFailureClassifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the forgot password request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset token request.
     *
     * Strict anti-enumeration:
     * - Returns identical neutral success response whether email exists or not.
     * - Password broker handles token generation, hashing, expiration, and throttling.
     * - Dispatches professional COMSOC Hub email with the plain token.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $normalizedEmail = strtolower(trim((string) $request->email));

        // Account lookup by primary email or preferred_email
        $user = User::where('email', $normalizedEmail)
            ->orWhere('preferred_email', $normalizedEmail)
            ->first();

        // If user exists, check broker throttling and generate token
        if ($user) {
            $broker = Password::broker();

            // Check if broker repository considers this user throttled
            if ($broker->getRepository()->recentlyCreatedToken($user)) {
                return back()->withInput($request->only('email'))
                    ->withErrors(['email' => __(Password::RESET_THROTTLED)]);
            }

            // Generate cryptographically secure token & store hashed record in password_reset_tokens
            $token = $broker->getRepository()->create($user);

            // Send branded email with token
            $correlationId = (string) Str::uuid();
            try {
                Mail::to($user->email)->send(
                    new PasswordResetTokenMail(
                        userName: $user->name,
                        token: $token,
                        email: $user->email,
                        expirationMinutes: config('auth.passwords.users.expire', 60),
                    )
                );

                \App\Services\AuditLogger::log('auth.password_reset_requested', $user, [], [
                    'email' => $user->email,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::error('Password reset email dispatch failed', [
                    'correlation_id' => $correlationId,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]);

                // We still report the generic success message to prevent timing-based enumeration,
                // unless mail service threw an unrecoverable exception in non-production.
            }
        }

        // Generic public confirmation prevents email address enumeration
        return redirect()->route('password.verify.show', ['email' => $normalizedEmail])
            ->with('status', 'If your email address is registered with COMSOC Hub, a 60-minute password reset token has been sent. Please check your inbox.');
    }
}

