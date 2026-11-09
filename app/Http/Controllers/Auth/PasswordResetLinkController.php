<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PasswordResetCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function __construct(
        protected PasswordResetCodeService $codeService
    ) {}

    /**
     * Display the forgot password request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset code request.
     *
     * Anti-enumeration protection:
     * Returns an identical neutral response whether the email is registered or not.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $normalizedEmail = strtolower(trim((string) $request->email));

        $user = User::where('email', $normalizedEmail)
            ->orWhere('preferred_email', $normalizedEmail)
            ->first();

        $cooldown = 60;
        if ($user) {
            $result = $this->codeService->issueAndSend($user, $request->ip(), enforceCooldown: true);

            if (! $result['sent'] && ($result['status'] === 'throttled' || $result['status'] === 'rate_limited')) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $result['message'],
                        'cooldown_seconds' => $result['cooldown_seconds'] ?? 60,
                    ], 429);
                }

                return back()->withInput($request->only('email'))
                    ->withErrors(['email' => $result['message']])
                    ->with('cooldown_seconds', $result['cooldown_seconds'] ?? 60);
            }

            $cooldown = $result['cooldown_seconds'] ?? 60;
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'If an account exists for that email, a 6-character reset code has been sent.',
                'cooldown_seconds' => $cooldown,
            ]);
        }

        return redirect()->route('password.verify.show', ['email' => $normalizedEmail])
            ->with('status', 'If your email address is registered with COMSOC Hub, a 6-character reset code valid for 15 minutes has been sent to your inbox.')
            ->with('cooldown_seconds', $cooldown);
    }
}
