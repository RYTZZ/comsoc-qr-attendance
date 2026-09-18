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
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    public function index(Request $request): View|StreamedResponse
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

        $yearLevels = Student::YEAR_LEVELS;

        return view('admin.students.index', compact(
            'students', 'activeYear', 'academicYears', 'yearId', 'yearLevels'
        ));
    }

    public function show(Student $student): View
    {
        $activeYear = AcademicYear::active();
        $student->load(['memberships.academicYear', 'memberships.qrCodes', 'memberships.activeQrCode']);
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

        AuditLogger::log('student.updated', $student, $old, $student->fresh()->toArray());

        return back()->with('success', "Student record for {$student->display_name} updated successfully.");
    }

    public function updateEmail(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $student->update(['email' => $validated['email']]);

        AuditLogger::log('student.email_updated', $student, [], ['email' => $validated['email']]);

        return back()->with('success', "Registered email updated for {$student->display_name}.");
    }

    private function exportStudentsCsv($records, string $filename): StreamedResponse
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
                'Membership Status',
                'QR Status',
            ]);

            foreach ($sorted as $student) {
                $membership = $student->memberships->first();
                $qrStatus = $membership?->activeQrCode ? 'Active' : ($membership ? 'Missing' : 'No Membership');
                $membershipStatus = $membership ? ucfirst($membership->status) : 'None';

                fputcsv($handle, [
                    $student->student_number ?? 'N/A',
                    $student->last_name ?? '',
                    $student->first_name ?? '',
                    $student->middle_name ?? '',
                    $student->program ?? 'N/A',
                    $student->year_level ?? 'N/A',
                    $student->email ?? '',
                    $membershipStatus,
                    $qrStatus,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
