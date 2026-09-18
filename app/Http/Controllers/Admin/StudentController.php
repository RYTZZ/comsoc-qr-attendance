<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $activeYear = AcademicYear::active();
        $academicYears = AcademicYear::orderByDesc('year_start')->get();

        $yearId = $request->filled('academic_year_id')
            ? $request->academic_year_id
            : $activeYear?->id;

        $query = Student::with([
                'memberships' => fn($q) => $q
                    ->when($yearId, fn($m) => $m->where('academic_year_id', $yearId))
                    ->with('activeQrCode'),
                'user',
            ])
            ->when($request->search, fn($q) => $q
                ->where('student_number', 'like', "%{$request->search}%")
                ->orWhere('last_name', 'ilike', "%{$request->search}%")
                ->orWhere('first_name', 'ilike', "%{$request->search}%")
            )
            ->when($request->filled('year_level'), fn($q) => $q->where('year_level', $request->year_level))
            ->when($request->filled('program'), fn($q) => $q->where('program', $request->program))
            ->when($request->filled('account_status'), function ($q) use ($request) {
                if ($request->account_status === 'no_account') {
                    $q->doesntHave('user');
                } elseif ($request->account_status === 'not_activated') {
                    $q->whereHas('user', fn($u) => $u->where('is_activated', false));
                } elseif ($request->account_status === 'active') {
                    $q->whereHas('user', fn($u) => $u->where('is_active', true)->where('is_activated', true));
                } elseif ($request->account_status === 'inactive') {
                    $q->whereHas('user', fn($u) => $u->where('is_active', false));
                }
            })
            ->when($request->filled('membership_status'), function ($q) use ($request, $yearId) {
                if ($request->membership_status === 'none') {
                    $q->when($yearId,
                        fn($s) => $s->whereDoesntHave('memberships', fn($m) => $m->where('academic_year_id', $yearId)),
                        fn($s) => $s->doesntHave('memberships')
                    );
                } else {
                    $q->whereHas('memberships', fn($m) => $m
                        ->when($yearId, fn($m2) => $m2->where('academic_year_id', $yearId))
                        ->where('status', $request->membership_status)
                    );
                }
            })
            ->when($request->filled('qr_status'), function ($q) use ($request, $yearId) {
                if ($request->qr_status === 'active') {
                    $q->whereHas('memberships', fn($m) => $m
                        ->when($yearId, fn($m2) => $m2->where('academic_year_id', $yearId))
                        ->whereHas('activeQrCode')
                    );
                } elseif ($request->qr_status === 'missing') {
                    $q->whereHas('memberships', fn($m) => $m
                        ->when($yearId, fn($m2) => $m2->where('academic_year_id', $yearId))
                        ->where('status', 'active')
                        ->whereDoesntHave('activeQrCode')
                    );
                } elseif ($request->qr_status === 'none') {
                    $q->whereDoesntHave('memberships');
                }
            });

        if ($request->input('export') === 'csv') {
            $records = $query->orderBy('last_name')->orderBy('first_name')->get();
            $yearLabel = $academicYears->firstWhere('id', $yearId)?->label ?? 'All_Years';
            $sanitizedYear = trim(preg_replace('/[^A-Za-z0-9_\-]+/', '_', $yearLabel), '_');
            return $this->exportStudentsCsv($records, "Student_Records_{$sanitizedYear}");
        }

        $students = $query
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(25)
            ->withQueryString();

        $noAccountCount = Student::when($yearId,
            fn($q) => $q->whereHas('memberships', fn($m) => $m->where('academic_year_id', $yearId))
        )->doesntHave('user')->count();

        $yearLevels = Student::YEAR_LEVELS;

        return view('admin.students.index', compact(
            'students', 'activeYear', 'academicYears', 'yearId', 'noAccountCount', 'yearLevels'
        ));
    }

    public function show(Student $student): View
    {
        $activeYear = AcademicYear::active();
        $student->load(['memberships.academicYear', 'memberships.qrCodes', 'memberships.activeQrCode', 'user']);
        $yearLevels = Student::YEAR_LEVELS;
        $programs = \App\Models\Program::activeOptions();
        return view('admin.students.show', compact('student', 'activeYear', 'yearLevels', 'programs'));
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'year_level' => ['required', \Illuminate\Validation\Rule::in(Student::YEAR_LEVELS)],
            'program' => ['nullable', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $old = $student->toArray();
        $student->update($validated);

        if ($request->filled('email') && $student->user) {
            $student->user->update(['email' => $request->email]);
        }

        AuditLogger::log('student.updated', $student, $old, $student->fresh()->toArray());

        return back()->with('success', "Student record for {$student->display_name} updated successfully.");
    }

    public function updateEmail(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $student->update(['email' => $validated['email']]);

        if ($student->user) {
            $student->user->update(['email' => $validated['email']]);
        }

        AuditLogger::log('student.email_updated', $student, [], ['email' => $validated['email']]);

        return back()->with('success', "Registered email updated for {$student->display_name}.");
    }

    public function resendActivation(Student $student): RedirectResponse
    {
        $email = $student->email ?? $student->user?->email;

        if (!$email) {
            return back()->with('error', 'Student does not have a registered email address. Please add one first.');
        }

        $user = $student->user;
        if (!$user) {
            $user = User::create([
                'name' => $student->display_name,
                'username' => $student->student_number,
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'role' => 'student',
                'student_id' => $student->id,
                'is_active' => true,
                'is_activated' => false,
                'must_change_password' => false,
                'created_by' => auth()->id(),
            ]);
        }

        $otp = (string) random_int(100000, 999999);
        $user->activation_otp = Hash::make($otp);
        $user->activation_otp_expires_at = now()->addMinutes(15);
        $user->is_activated = false;
        $user->save();

        try {
            \Illuminate\Support\Facades\Mail::send('emails.notification', [
                'subject' => 'ComSoc Account Activation OTP',
                'title' => 'Activate Your Student Account',
                'subtitle' => 'Verification code for Computing Society QR Attendance',
                'otp' => $otp,
                'notice' => 'This code will expire in 15 minutes. Never share your verification code with anyone.',
                'body' => '<p>Hello <strong>' . e($student->display_name) . '</strong>,</p><p>An administrator has sent an activation code for your student account. Please use this one-time password to complete your activation:</p>',
            ], function ($message) use ($email, $student) {
                $message->to($email, $student->display_name)
                    ->subject('ComSoc Account Activation OTP');
            });
        } catch (\Throwable $e) {
            report($e);
        }

        AuditLogger::log('account.activation_resent', $user, [], [
            'student_id' => $student->id,
            'sent_by' => auth()->id(),
        ]);

        return back()->with('success', "Activation code sent to {$email}.");
    }

    public function createAccount(Request $request, Student $student): RedirectResponse
    {
        if ($student->user) {
            return back()->with('info', 'Student already has an account.');
        }

        $studentNumber = $student->student_number;
        $email = $student->email ?? ($studentNumber . '@student.local');

        if (User::where('username', $studentNumber)->orWhere('email', $email)->exists()) {
            return back()->with('error', 'A user account with this student number or email already exists.');
        }

        $user = DB::transaction(function () use ($student, $studentNumber, $email) {
            return User::create([
                'name' => $student->display_name,
                'username' => $studentNumber,
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'role' => 'student',
                'student_id' => $student->id,
                'is_active' => true,
                'is_activated' => false,
                'must_change_password' => false,
                'created_by' => auth()->id(),
            ]);
        });

        AuditLogger::log('account.student_created', $user, [], [
            'student_id' => $student->id,
            'student_number' => $studentNumber,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', "Account record initialized for {$student->display_name}. The student can activate it via 'Activate Your Account' on the login screen.");
    }

    public function activateAccount(Student $student): RedirectResponse
    {
        if (!$student->user) {
            return back()->with('error', 'This student does not have an account.');
        }

        $student->user->update([
            'is_active' => true,
            'is_activated' => true,
        ]);

        AuditLogger::log('account.activated', $student->user, ['is_active' => false], ['is_active' => true]);

        return back()->with('success', "Account for {$student->display_name} has been activated.");
    }

    public function suspendAccount(Student $student): RedirectResponse
    {
        if (!$student->user) {
            return back()->with('error', 'This student does not have an account.');
        }

        $student->user->update(['is_active' => false]);

        AuditLogger::log('account.suspended', $student->user, ['is_active' => true], ['is_active' => false]);

        return back()->with('success', "Account for {$student->display_name} has been suspended.");
    }

    public function deactivateAccount(Student $student): RedirectResponse
    {
        if (!$student->user) {
            return back()->with('error', 'This student does not have an account.');
        }

        $student->user->update(['is_active' => false]);

        AuditLogger::log('account.deactivated', $student->user, ['is_active' => true], ['is_active' => false]);

        return back()->with('success', "Account for {$student->display_name} has been deactivated.");
    }

    public function resetPassword(Student $student): RedirectResponse
    {
        if (!$student->user) {
            return back()->with('error', 'This student does not have an account.');
        }

        $student->user->update([
            'is_activated' => false,
            'activation_token' => null,
            'activation_otp' => null,
            'activation_otp_expires_at' => null,
        ]);

        AuditLogger::log('account.password_reset_initiated', $student->user, [], [
            'reset_by' => auth()->id(),
        ]);

        return back()->with('success', "Account access reset for {$student->display_name}. The student must reactivate their account with an OTP.");
    }

    public function bulkCreateAccounts(Request $request): RedirectResponse
    {
        $request->validate([
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
        ]);

        $yearId = $request->academic_year_id ?? AcademicYear::active()?->id;

        $studentsWithoutAccounts = Student::doesntHave('user')
            ->when($yearId, fn($q) => $q->whereHas('memberships', fn($m) => $m->where('academic_year_id', $yearId)))
            ->get();

        if ($studentsWithoutAccounts->isEmpty()) {
            return back()->with('info', 'All students already have accounts.');
        }

        $created = 0;

        DB::transaction(function () use ($studentsWithoutAccounts, &$created) {
            foreach ($studentsWithoutAccounts as $student) {
                $studentNumber = $student->student_number;
                $email = $studentNumber . '@student.local';

                if (User::where('username', $studentNumber)->orWhere('email', $email)->exists()) {
                    continue;
                }

                $user = User::create([
                    'name' => $student->display_name,
                    'username' => $studentNumber,
                    'email' => $student->email ?? $email,
                    'password' => Hash::make(Str::random(32)),
                    'role' => 'student',
                    'student_id' => $student->id,
                    'is_active' => true,
                    'is_activated' => false,
                    'must_change_password' => false,
                    'created_by' => auth()->id(),
                ]);

                AuditLogger::log('account.student_created', $user, [], [
                    'student_id' => $student->id,
                    'student_number' => $studentNumber,
                    'bulk' => true,
                    'created_by' => auth()->id(),
                ]);

                $created++;
            }
        });

        return back()->with('success', "{$created} student account(s) initialized. Students can activate their accounts using their Student Number and registered email.");
    }

    private function exportStudentsCsv($records, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $sorted = $records->sort(function ($a, $b) {
            $cmpProgram = strcasecmp($a->program ?? 'N/A', $b->program ?? 'N/A');
            if ($cmpProgram !== 0) return $cmpProgram;

            $cmpYear = strcasecmp($a->year_level ?? 'N/A', $b->year_level ?? 'N/A');
            if ($cmpYear !== 0) return $cmpYear;

            return strcasecmp($a->full_name ?? '', $b->full_name ?? '');
        })->values();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($sorted) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Student Number',
                'Last Name',
                'First Name',
                'Middle Name',
                'Program',
                'Year Level',
                'Email',
                'Account Status',
                'Membership Status',
                'QR Status',
            ]);

            foreach ($sorted as $student) {
                $membership = $student->memberships->first();
                $qrStatus = $membership?->activeQrCode ? 'Active' : ($membership ? 'Missing' : 'No Membership');
                $membershipStatus = $membership ? ucfirst($membership->status) : 'None';
                $accountStatus = 'No Account';
                if ($student->user) {
                    if (!$student->user->is_active) {
                        $accountStatus = 'Suspended';
                    } elseif (!$student->user->is_activated) {
                        $accountStatus = 'Not Activated';
                    } else {
                        $accountStatus = 'Active';
                    }
                }

                fputcsv($handle, [
                    $student->student_number ?? 'N/A',
                    $student->last_name ?? '',
                    $student->first_name ?? '',
                    $student->middle_name ?? '',
                    $student->program ?? 'N/A',
                    $student->year_level ?? 'N/A',
                    $student->user?->email ?? $student->email ?? '',
                    $accountStatus,
                    $membershipStatus,
                    $qrStatus,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
