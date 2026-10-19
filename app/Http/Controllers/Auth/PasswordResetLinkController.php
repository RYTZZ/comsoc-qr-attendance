<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
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
     * Failure taxonomy logged on the server side:
     *   - TOKEN_STORAGE  : database write failed (QueryException)
     *   - MAIL_TRANSPORT : SMTP / transport layer rejected the message
     *   - UNEXPECTED     : any other Throwable not covered above
     *
     * The user-visible response is always the same success message
     * (to prevent account enumeration) unless throttled.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $correlationId = (string) Str::uuid();

        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Password reset — TOKEN_STORAGE failure', [
                'correlation_id' => $correlationId,
                'subsystem'      => 'token_repository',
                'exception'      => get_class($e),
                'sqlstate'       => $e->getCode(),
                'message'        => $e->getMessage(),
                'ip'             => $request->ip(),
            ]);

            // Surface a correlation ID so administrators can find the log entry,
            // but do not expose the SQL error to the user.
            return back()->withInput($request->only('email'))
                ->withErrors([
                    'error' => 'A temporary database processing error occurred. '
                             . 'Please try again shortly. (Ref: ' . $correlationId . ')',
                ]);
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface|\Symfony\Component\Mime\Exception\ExceptionInterface $e) {
            Log::error('Password reset — MAIL_TRANSPORT failure', [
                'correlation_id' => $correlationId,
                'subsystem'      => 'mail_transport',
                'exception'      => get_class($e),
                'message'        => $e->getMessage(),
                'ip'             => $request->ip(),
            ]);

            return back()->withInput($request->only('email'))
                ->withErrors([
                    'error' => 'Unable to send password reset email at this time due to a mail service connection issue. '
                             . 'Please try again shortly. (Ref: ' . $correlationId . ')',
                ]);
        } catch (\Throwable $e) {
            Log::error('Password reset — UNEXPECTED failure', [
                'correlation_id' => $correlationId,
                'subsystem'      => 'unknown',
                'exception'      => get_class($e),
                'message'        => $e->getMessage(),
                'file'           => $e->getFile(),
                'line'           => $e->getLine(),
                'ip'             => $request->ip(),
            ]);

            return back()->withInput($request->only('email'))
                ->withErrors([
                    'error' => 'An unexpected error occurred while processing your request. '
                             . 'Please try again shortly. (Ref: ' . $correlationId . ')',
                ]);
        }

        // RESET_THROTTLED: the user must wait before requesting another token.
        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
        }

        // For both RESET_LINK_SENT and USER_NOT_FOUND, always show the success
        // message to prevent account enumeration.
        return back()->with('status', __(Password::RESET_LINK_SENT));
    }
}
