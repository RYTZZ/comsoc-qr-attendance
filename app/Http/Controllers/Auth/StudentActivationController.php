<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class StudentActivationController extends Controller
{
    public function showVerifyStudentForm(): View
    {
        return view('auth.student-activate-step1');
    }

    public function verifyStudent(Request $request): RedirectResponse
    {
        $request->validate([
            'student_number' => ['required', 'string'],
        ]);

        $studentNumber = trim($request->input('student_number'));

        $student = Student::whereRaw('LOWER(TRIM(student_number)) = ?', [strtolower($studentNumber)])->first();

        if (!$student) {
            return back()->withInput()->withErrors([
                'student_number' => 'No student record found with this student number in the current masterlist.',
            ]);
        }

        $user = $student->user;
        if ($user && $user->is_activated && $user->is_active) {
            return redirect()->route('login')->with('info', 'Your account is already active. Please sign in with your credentials.');
        }

        session([
            'activation_student_id' => $student->id,
            'activation_student_number' => $student->student_number,
            'activation_step' => 'email',
        ]);

        return redirect()->route('student.activate.email');
    }

    public function showEmailForm(Request $request): View|RedirectResponse
    {
        $studentId = session('activation_student_id');
        if (!$studentId) {
            return redirect()->route('student.activate')->withErrors([
                'student_number' => 'Please verify your student number first.',
            ]);
        }

        $student = Student::find($studentId);
        if (!$student) {
            return redirect()->route('student.activate');
        }

        $currentEmail = $student->email ?? $student->user?->email;

        return view('auth.student-activate-step2', compact('student', 'currentEmail'));
    }

    public function submitEmailAndSendOtp(Request $request): RedirectResponse
    {
        $studentId = session('activation_student_id');
        if (!$studentId) {
            return redirect()->route('student.activate')->withErrors([
                'student_number' => 'Please verify your student number first.',
            ]);
        }

        $student = Student::find($studentId);
        if (!$student) {
            return redirect()->route('student.activate');
        }

        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $inputEmail = strtolower(trim($request->input('email')));

        $existingUserWithEmail = User::where('email', $inputEmail)
            ->where('student_id', '!=', $student->id)
            ->first();

        if ($existingUserWithEmail) {
            return back()->withInput()->withErrors([
                'email' => 'This email address is already associated with another account.',
            ]);
        }

        $student->update(['email' => $inputEmail]);

        $user = $student->user;
        if (!$user) {
            $user = User::create([
                'name' => $student->display_name,
                'username' => $student->student_number,
                'email' => $inputEmail,
                'password' => Hash::make(Str::random(32)),
                'role' => 'student',
                'student_id' => $student->id,
                'is_active' => true,
                'is_activated' => false,
                'must_change_password' => false,
            ]);
        } else {
            $user->email = $inputEmail;
        }

        $otp = (string) random_int(100000, 999999);
        $user->activation_otp = Hash::make($otp);
        $user->activation_otp_expires_at = now()->addMinutes(15);
        $user->activation_token = null;
        $user->save();

        session([
            'activation_student_id' => $student->id,
            'activation_user_id' => $user->id,
            'activation_email' => $inputEmail,
            'activation_step' => 'otp',
        ]);

        try {
            Mail::send('emails.notification', [
                'subject' => 'ComSoc Account Activation OTP',
                'title' => 'Activate Your Student Account',
                'subtitle' => 'Verification code for Computing Society QR Attendance',
                'otp' => $otp,
                'notice' => 'This code will expire in 15 minutes. Never share your verification code with anyone.',
                'body' => '<p>Hello <strong>' . e($student->display_name) . '</strong>,</p><p>We received a request to activate your student account for the ComSoc QR Attendance System. Please enter the one-time password below to continue:</p>',
            ], function ($message) use ($inputEmail, $student) {
                $message->to($inputEmail, $student->display_name)
                    ->subject('ComSoc Account Activation OTP');
            });
        } catch (\Throwable $e) {
            report($e);
        }

        AuditLogger::log('account.activation_otp_sent', $user, [], [
            'student_id' => $student->id,
            'student_number' => $student->student_number,
            'email' => $inputEmail,
        ]);

        return redirect()->route('student.activate.verify')->with('success', 'A 6-digit activation code has been sent to ' . $inputEmail . '.');
    }

    public function showVerifyForm(Request $request): View|RedirectResponse
    {
        $userId = session('activation_user_id');
        $studentId = session('activation_student_id');

        if (!$userId || !$studentId) {
            return redirect()->route('student.activate')->withErrors([
                'student_number' => 'Please verify your student number first.',
            ]);
        }

        $user = User::find($userId);
        if (!$user) {
            return redirect()->route('student.activate');
        }

        $email = session('activation_email', $user->email);

        return view('auth.student-verify-otp', compact('user', 'email'));
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $userId = session('activation_user_id');
        $studentId = session('activation_student_id');

        if (!$userId || !$studentId) {
            return redirect()->route('student.activate')->withErrors([
                'student_number' => 'Session expired. Please restart activation.',
            ]);
        }

        $user = User::find($userId);
        if (!$user) {
            return redirect()->route('student.activate');
        }

        $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $otpInput = trim($request->input('otp'));

        if (!$user->activation_otp || !$user->activation_otp_expires_at) {
            return back()->withErrors(['otp' => 'No active verification code found. Please request a new code.']);
        }

        if (now()->isAfter($user->activation_otp_expires_at)) {
            return back()->withErrors(['otp' => 'The verification code has expired. Please request a new code.']);
        }

        if (!Hash::check($otpInput, $user->activation_otp)) {
            return back()->withErrors(['otp' => 'The entered verification code is incorrect.']);
        }

        $activationToken = Str::random(64);
        $user->activation_token = $activationToken;
        $user->activation_otp = null;
        $user->activation_otp_expires_at = null;
        $user->save();

        session([
            'activation_token' => $activationToken,
            'activation_step' => 'password',
        ]);

        return redirect()->route('student.activate.password')->with('success', 'Verification code confirmed. Please set your new password.');
    }

    public function showPasswordForm(Request $request): View|RedirectResponse
    {
        $userId = session('activation_user_id');
        $sessionToken = session('activation_token');

        if (!$userId || !$sessionToken) {
            return redirect()->route('student.activate')->withErrors([
                'student_number' => 'Session expired. Please restart activation.',
            ]);
        }

        $user = User::find($userId);
        if (!$user || $user->activation_token !== $sessionToken) {
            return redirect()->route('student.activate')->withErrors([
                'student_number' => 'Invalid or expired activation session.',
            ]);
        }

        return view('auth.student-set-password', compact('user'));
    }

    public function completeActivation(Request $request): RedirectResponse
    {
        $userId = session('activation_user_id');
        $sessionToken = session('activation_token');

        if (!$userId || !$sessionToken) {
            return redirect()->route('student.activate')->withErrors([
                'student_number' => 'Session expired. Please restart activation.',
            ]);
        }

        $user = User::find($userId);
        if (!$user || $user->activation_token !== $sessionToken) {
            return redirect()->route('student.activate')->withErrors([
                'student_number' => 'Invalid or expired activation session.',
            ]);
        }

        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user->password = Hash::make($request->input('password'));
        $user->is_activated = true;
        $user->is_active = true;
        $user->must_change_password = false;
        $user->activation_token = null;
        $user->activation_otp = null;
        $user->activation_otp_expires_at = null;
        $user->save();

        session()->forget([
            'activation_student_id',
            'activation_student_number',
            'activation_user_id',
            'activation_email',
            'activation_token',
            'activation_step',
        ]);

        AuditLogger::log('account.activated_by_student', $user, [], [
            'student_id' => $user->student_id,
            'student_number' => $user->student?->student_number ?? $user->username,
        ]);

        return redirect()->route('login')->with('status', 'Your account has been successfully activated! You may now sign in with your credentials.');
    }
}
