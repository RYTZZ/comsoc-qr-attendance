<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // We will send the password reset link to this user.
        // We catch mail transport exceptions to prevent mail delivery issues
        // from presenting as a 500 database error or disrupting the user workflow.
        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            \Illuminate\Support\Facades\Log::error('Password reset email transport failure', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            // Treat as link sent to the user to maintain generic response and prevent account enumeration,
            // while logging the mailer outage internally for administrators.
            $status = Password::RESET_LINK_SENT;
        } catch (\Illuminate\Database\QueryException $e) {
            $correlationId = (string) \Illuminate\Support\Str::uuid();
            \Illuminate\Support\Facades\Log::error('Password reset database query failure', [
                'correlation_id' => $correlationId,
                'sqlstate' => $e->getCode(),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return back()->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'A temporary database processing error occurred. Please try again shortly. Reference: ' . $correlationId,
                ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Unexpected error during password reset request', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return back()->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'An unexpected error occurred while processing your request. Please try again shortly.',
                ]);
        }

        // Prevent account enumeration by always displaying the success message when the user doesn't exist.
        // If throttled (TOO_MANY_REQUESTS), respect throttling and show error.
        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
        }

        return back()->with('status', __(Password::RESET_LINK_SENT));
    }
}
