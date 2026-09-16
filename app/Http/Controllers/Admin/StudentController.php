<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $activeYear = AcademicYear::active();

        $students = Student::with([
                'memberships' => fn($q) => $q->when($activeYear, fn($m) => $m->where('academic_year_id', $activeYear->id))
                                             ->with('activeQrCode'),
                'user',
            ])
            ->when($request->search, fn($q) => $q
                ->where('student_number', 'like', "%{$request->search}%")
                ->orWhere('last_name', 'ilike', "%{$request->search}%")
                ->orWhere('first_name', 'ilike', "%{$request->search}%")
            )
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.students.index', compact('students', 'activeYear'));
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
            return back()->with('info', 'This student already has an account.');
        }

        $request->validate([
            'email' => 'required|email|unique:users,email|unique:users,username',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $email = trim($request->email);
        $username = strstr($email, '@', true);

        $user = User::create([
            'name' => $student->display_name,
            'email' => $email,
            'username' => $username,
            'password' => Hash::make($request->password),
            'role' => 'student',
            'student_id' => $student->id,
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        AuditLogger::log('account.student_created', $user, [], [
            'student_id' => $student->id,
            'student_number' => $student->student_number,
            'email' => $email,
        ]);

        return back()->with('success', "Student account created for {$student->display_name}.");
    }
}
