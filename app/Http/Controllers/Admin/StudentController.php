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
            ->when($request->filled('account_status'), function ($q) use ($request) {
                if ($request->account_status === 'no_account') {
                    $q->doesntHave('user');
                } elseif ($request->account_status === 'active') {
                    $q->whereHas('user', fn($u) => $u->where('is_active', true));
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

        $students = $query
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(25)
            ->withQueryString();

        $noAccountCount = Student::when($yearId,
            fn($q) => $q->whereHas('memberships', fn($m) => $m->where('academic_year_id', $yearId))
        )->doesntHave('user')->count();

        return view('admin.students.index', compact(
            'students', 'activeYear', 'academicYears', 'yearId', 'noAccountCount'
        ));
    }

    public function show(Student $student): View
    {
        $activeYear = AcademicYear::active();
        $student->load(['memberships.academicYear', 'memberships.qrCodes', 'memberships.activeQrCode', 'user']);
        return view('admin.students.show', compact('student', 'activeYear'));
    }

    public function createAccount(Request $request, Student $student): RedirectResponse
    {
        if ($student->user) {
            return back()->with('info', 'Student already has an account.');
        }

        $studentNumber = $student->student_number;
        $email = $studentNumber . '@student.local';

        if (User::where('username', $studentNumber)->orWhere('email', $email)->exists()) {
            return back()->with('error', 'A user account with this student number already exists.');
        }

        $tempPassword = Str::password(12, true, true, false);

        $user = DB::transaction(function () use ($student, $studentNumber, $email, $tempPassword) {
            return User::create([
                'name' => $student->display_name,
                'username' => $studentNumber,
                'email' => $email,
                'password' => Hash::make($tempPassword),
                'role' => 'student',
                'student_id' => $student->id,
                'is_active' => true,
                'must_change_password' => true,
                'created_by' => auth()->id(),
            ]);
        });

        AuditLogger::log('account.student_created', $user, [], [
            'student_id' => $student->id,
            'student_number' => $studentNumber,
            'created_by' => auth()->id(),
        ]);

        return back()->with('student_credentials', [
            'username' => $studentNumber,
            'password' => $tempPassword,
            'student_name' => $student->display_name,
        ]);
    }

    public function activateAccount(Student $student): RedirectResponse
    {
        if (!$student->user) {
            return back()->with('error', 'This student does not have an account.');
        }

        $student->user->update(['is_active' => true]);

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

        $tempPassword = Str::password(12, true, true, false);

        $student->user->update([
            'password' => Hash::make($tempPassword),
            'must_change_password' => true,
        ]);

        AuditLogger::log('account.password_reset', $student->user, [], [
            'reset_by' => auth()->id(),
            'must_change_password' => true,
        ]);

        return back()->with('student_credentials', [
            'username' => $student->student_number,
            'password' => $tempPassword,
            'student_name' => $student->display_name,
            'is_reset' => true,
        ]);
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

                $tempPassword = Str::password(12, true, true, false);

                $user = User::create([
                    'name' => $student->display_name,
                    'username' => $studentNumber,
                    'email' => $email,
                    'password' => Hash::make($tempPassword),
                    'role' => 'student',
                    'student_id' => $student->id,
                    'is_active' => true,
                    'must_change_password' => true,
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

        return back()->with('success', "{$created} student account(s) created. Students must log in with their Student Number and change their temporary password.");
    }
}
