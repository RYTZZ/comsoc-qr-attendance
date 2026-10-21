<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Support\MailFailureClassifier;
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

        $normalizedEmail = strtolower(trim((string) $request->email));

        // Account lookup: Check if user exists by primary email or preferred_email
        $user = \App\Models\User::where('email', $normalizedEmail)
            ->orWhere('preferred_email', $normalizedEmail)
            ->first();

        // If the user was found by preferred email, use their primary account email
        // so Laravel's PasswordBroker token repository (which is keyed on primary email)
        // correctly persists and validates the reset token.
        $targetEmail = $user ? $user->email : $normalizedEmail;

        $correlationId = (string) Str::uuid();

        $mailer = (string) config('mail.default');

        if (app()->environment('production') && in_array($mailer, ['log', 'array', 'null'], true)) {
            Log::critical('Password reset — MAIL_MISCONFIGURED: mailer cannot deliver in production', [
                'correlation_id' => $correlationId,
                'subsystem'      => 'mail_config',
                'mailer'         => $mailer,
                'ip'             => $request->ip(),
            ]);

            return back()->withInput($request->only('email'))
                ->withErrors([
                    'error' => 'Password reset email is temporarily unavailable. '
                             . 'Please contact an administrator. (Ref: ' . $correlationId . ')',
                ]);
        }

        try {
            $status = Password::sendResetLink(
                ['email' => $targetEmail]
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
            $diagnosis = MailFailureClassifier::classify($e);

            Log::error('Password reset — MAIL_TRANSPORT failure', [
                'correlation_id'  => $correlationId,
                'request_id'      => $request->header('X-Request-ID'),
                'subsystem'       => 'mail_transport',
                'operation'       => 'password_reset_notification',
                'failed_stage'    => $diagnosis['stage'],
                'exception'       => get_class($e),
                'exception_chain' => $diagnosis['exception_chain'],
                'detail'          => $diagnosis['detail'],
                'transport'       => MailFailureClassifier::transportContext(),
                'ip'              => $request->ip(),
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

        // Dispatch means the mailer accepted the message; it does not confirm inbox delivery.
        Log::info('Password reset — notification dispatched', [
            'correlation_id' => $correlationId,
            'mailer'         => $mailer,
            'account_found'  => $status === Password::RESET_LINK_SENT,
        ]);

        // Identical neutral response for RESET_LINK_SENT and USER_NOT_FOUND to prevent enumeration.
        return back()->with(
            'status',
            'If an account exists for that email address, a password reset link has been sent. '
            . 'It may take a few minutes to arrive; please also check your spam folder.'
        );
    }
}
