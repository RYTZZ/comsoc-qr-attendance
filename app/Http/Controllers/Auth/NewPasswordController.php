<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordChangedConfirmationMail;
use App\Models\User;
use App\Models\UserSession;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the token verification view.
     */
    public function showVerify(Request $request): View
    {
        return view('auth.verify-reset-token', [
            'email' => $request->query('email', old('email', '')),
            'token' => $request->query('token', old('token', '')),
        ]);
    }

    /**
     * Verify the reset token server-side before proceeding to reset password.
     */
    public function verifyToken(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'token' => ['required', 'string'],
        ]);

        $normalizedEmail = strtolower(trim($validated['email']));
        $token = trim($validated['token']);

        $user = User::where('email', $normalizedEmail)
            ->orWhere('preferred_email', $normalizedEmail)
            ->first();

        if (! $user) {
            return back()->withInput($request->only('email'))
                ->withErrors(['token' => 'This password reset token is invalid, expired, or has already been used.']);
        }

        $broker = Password::broker();
        if (! $broker->getRepository()->exists($user, $token)) {
            return back()->withInput($request->only('email'))
                ->withErrors(['token' => 'This password reset token is invalid, expired, or has already been used.']);
        }

        // Token verified! Redirect to set new password view with verified state in session
        $request->session()->put('verified_password_reset', [
            'email' => $user->email,
            'token' => $token,
            'verified_at' => now()->timestamp,
        ]);

        return redirect()->route('password.reset.form')
            ->with('status', 'Reset token verified successfully. You may now enter your new password.');
    }

    /**
     * Display the new password entry view.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $verified = $request->session()->get('verified_password_reset');

        if (! $verified || empty($verified['email']) || empty($verified['token'])) {
            return redirect()->route('password.verify.show')
                ->withErrors(['token' => 'Please enter and verify your reset token first.']);
        }

        // Ensure session verification has not expired (allow up to 15 mins to complete form)
        if (now()->timestamp - ($verified['verified_at'] ?? 0) > 900) {
            $request->session()->forget('verified_password_reset');
            return redirect()->route('password.verify.show')
                ->withErrors(['token' => 'Verification window has expired. Please verify your token again.']);
        }

        return view('auth.reset-password', [
            'email' => $verified['email'],
            'token' => $verified['token'],
        ]);
    }

    /**
     * Handle updating the password, invalidating token and sessions, and sending confirmation email.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $normalizedEmail = strtolower(trim($request->email));
        $token = trim($request->token);

        $user = User::where('email', $normalizedEmail)
            ->orWhere('preferred_email', $normalizedEmail)
            ->first();

        if (! $user) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => __(Password::INVALID_USER)]);
        }

        $credentials = [
            'email' => $user->email,
            'password' => $request->password,
            'password_confirmation' => $request->password_confirmation,
            'token' => $token,
        ];

        $status = Password::reset($credentials, function (User $user) use ($request) {
            $user->forceFill([
                'password' => Hash::make($request->password),
                'remember_token' => Str::random(60),
                'active_session_hash' => null,
                'must_change_password' => false,
            ])->save();

            // Revoke all existing authenticated sessions across all devices
            UserSession::where('user_id', $user->id)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            AuditLogger::log('auth.password_reset_completed', $user, [], [
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))
                ->withErrors(['token' => __($status)]);
        }

        // Clean up temporary session verification state
        $request->session()->forget('verified_password_reset');

        // Send post-reset confirmation email
        try {
            Mail::to($user->email)->send(
                new PasswordChangedConfirmationMail(
                    userName: $user->name,
                    email: $user->email,
                    ipAddress: $request->ip(),
                )
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Password reset confirmation email delivery failed: ' . $e->getMessage());
        }

        // Redirect to login with success feedback (user is NOT automatically authenticated)
        return redirect()->route('login')->with(
            'status',
            'Your password has been successfully reset! All active sessions were revoked. You can now log in with your new password.'
        );
    }
}
