<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordChangedConfirmationMail;
use App\Models\PasswordResetCode;
use App\Models\User;
use App\Models\UserSession;
use App\Services\AuditLogger;
use App\Services\PasswordResetCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function __construct(
        protected PasswordResetCodeService $codeService
    ) {}

    /**
     * Display the 6-character code verification view.
     */
    public function showVerify(Request $request): View
    {
        $email = $request->query('email', old('email', ''));
        $cooldown = 0;

        if ($email) {
            $user = User::where('email', strtolower(trim((string) $email)))
                ->orWhere('preferred_email', strtolower(trim((string) $email)))
                ->first();
            if ($user) {
                $cooldown = $this->codeService->remainingResendCooldownSeconds($user, $email);
            }
        }

        return view('auth.verify-reset-token', [
            'email' => $email,
            'code' => $request->query('code', old('code', '')),
            'cooldown_seconds' => $cooldown,
        ]);
    }

    /**
     * Verify the 6-character reset code server-side.
     */
    public function verifyToken(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $normalizedEmail = strtolower(trim($validated['email']));
        $code = strtoupper(trim($validated['code']));

        $user = User::where('email', $normalizedEmail)
            ->orWhere('preferred_email', $normalizedEmail)
            ->first();

        if (! $user) {
            $msg = 'The verification code is invalid, expired, or has already been used.';
            if ($request->wantsJson()) {
                return response()->json(['valid' => false, 'message' => $msg], 422);
            }
            return back()->withInput($request->only('email'))
                ->withErrors(['code' => $msg]);
        }

        $outcome = $this->codeService->verify($user, $code, $request->ip());

        if (! $outcome['valid']) {
            if ($request->wantsJson()) {
                return response()->json(['valid' => false, 'message' => $outcome['message']], 422);
            }
            return back()->withInput($request->only('email'))
                ->withErrors(['code' => $outcome['message']]);
        }

        // Store authorization record in session to allow access to reset-password form
        $request->session()->put('verified_password_reset_code', [
            'code_id' => $outcome['code_record']->id,
            'user_id' => $user->id,
            'email' => $user->email,
            'verified_at' => now()->timestamp,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'valid' => true,
                'redirect' => route('password.reset.form'),
                'message' => 'Code verified successfully.',
            ]);
        }

        return redirect()->route('password.reset.form')
            ->with('status', 'Verification code confirmed. You may now enter your new password.');
    }

    /**
     * Display the new password entry view.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $verified = $request->session()->get('verified_password_reset_code');

        if (! $verified || empty($verified['code_id']) || empty($verified['email'])) {
            return redirect()->route('password.verify.show')
                ->withErrors(['code' => 'Please enter and verify your 6-character reset code first.']);
        }

        // 15-minute window to complete the password update once verified
        if (now()->timestamp - ($verified['verified_at'] ?? 0) > 900) {
            $request->session()->forget('verified_password_reset_code');
            return redirect()->route('password.verify.show', ['email' => $verified['email']])
                ->withErrors(['code' => 'Your verification session has expired. Please verify your code again.']);
        }

        return view('auth.reset-password', [
            'email' => $verified['email'],
        ]);
    }

    /**
     * Handle updating the password, invalidating the code, revoking sessions, and sending confirmation email.
     */
    public function store(Request $request): RedirectResponse
    {
        $verified = $request->session()->get('verified_password_reset_code');

        if (! $verified || empty($verified['code_id']) || empty($verified['email'])) {
            return redirect()->route('password.verify.show')
                ->withErrors(['code' => 'Please enter and verify your reset code first.']);
        }

        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $normalizedEmail = strtolower(trim($request->email));

        if ($normalizedEmail !== strtolower(trim((string) $verified['email']))) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'The provided email address does not match your verified session.']);
        }

        $codeRecord = PasswordResetCode::find($verified['code_id']);
        if (! $codeRecord || ! $codeRecord->isUsable()) {
            $request->session()->forget('verified_password_reset_code');
            return redirect()->route('password.verify.show', ['email' => $normalizedEmail])
                ->withErrors(['code' => 'The reset code was already consumed or expired. Please request a new code.']);
        }

        $user = User::find($verified['user_id']);
        if (! $user) {
            return redirect()->route('login');
        }

        // Update password and revoke sessions
        $user->forceFill([
            'password' => Hash::make($request->password),
            'remember_token' => Str::random(60),
            'active_session_hash' => null,
            'must_change_password' => false,
        ])->save();

        // Invalidate active reset codes
        $this->codeService->consume($codeRecord);

        // Revoke active sessions across all devices
        UserSession::where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        AuditLogger::log('auth.password_reset_completed', $user, [], [
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        // Clean up temporary session verification state
        $request->session()->forget('verified_password_reset_code');

        // Send confirmation email
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

        // Redirect user to login (user is NOT automatically authenticated)
        return redirect()->route('login')->with(
            'status',
            'Your password has been reset successfully! You can now sign in using your new password.'
        );
    }
}
