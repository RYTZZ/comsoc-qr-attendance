<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Membership;
use App\Models\QrCode;
use App\Models\Student;
use App\Services\AuditLogger;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MembershipController extends Controller
{
    public function __construct(private QrCodeService $qrService) {}

    public function index(Request $request): View
    {
        $academicYears = AcademicYear::orderByDesc('year_start')->get();
        $activeYear = AcademicYear::active();

        $filterYearId = $request->academic_year_id ?? $activeYear?->id;

        $query = Membership::with(['student.user', 'academicYear', 'activeQrCode.card', 'latestQrCode'])
            ->when($request->academic_year_id, fn($q) => $q->where('academic_year_id', $request->academic_year_id))
            ->when(!$request->academic_year_id && $activeYear, fn($q) => $q->where('academic_year_id', $activeYear->id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->filled('year_level'), fn($q) => $q->whereHas('student', fn($s) => $s->where('year_level', $request->year_level)))
            ->when($request->search, fn($q) => $q->whereHas('student', fn($s) => $s->where(function ($sub) use ($request) {
                $sub->where('student_number', 'ilike', "%{$request->search}%")
                    ->orWhere('last_name', 'ilike', "%{$request->search}%")
                    ->orWhere('first_name', 'ilike', "%{$request->search}%");
            })));

        $memberships = $query->orderBy('memberships.created_at', 'desc')->paginate(20)->withQueryString();

        $missingQrCount = Membership::where('status', 'active')
            ->when($filterYearId, fn($q) => $q->where('academic_year_id', $filterYearId))
            ->whereDoesntHave('qrCodes', fn($q) => $q->where('status', 'active'))
            ->count();

        $yearLevels = Student::YEAR_LEVELS;

        return view('admin.memberships.index', compact('memberships', 'academicYears', 'activeYear', 'missingQrCount', 'filterYearId', 'yearLevels'));
    }

    public function activate(Membership $membership): RedirectResponse
    {
        $membership->update(['status' => 'active']);

        if (!$membership->activeQrCode) {
            $lastBatch = QrCode::whereHas('membership', fn($q) => $q->where('academic_year_id', $membership->academic_year_id))
                ->max('batch_number') ?? 0;
            $this->qrService->generateForMembership($membership, $lastBatch + 1);
        }

        AuditLogger::log('membership.activated', $membership, ['status' => 'inactive'], ['status' => 'active']);
        return back()->with('success', 'Membership activated.');
    }

    public function deactivate(Membership $membership): RedirectResponse
    {
        $membership->update(['status' => 'inactive']);
        $membership->activeQrCode()?->update(['status' => 'expired', 'expired_at' => now()]);
        AuditLogger::log('membership.deactivated', $membership, ['status' => 'active'], ['status' => 'inactive']);
        return back()->with('success', 'Membership deactivated.');
    }

    public function generateQr(Membership $membership): RedirectResponse
    {
        if ($membership->status !== 'active') {
            return back()->with('error', 'Cannot generate QR for an inactive membership.');
        }

        if ($membership->activeQrCode) {
            return back()->with('info', 'This membership already has an active QR code.');
        }

        $lastBatch = QrCode::whereHas('membership', fn($q) => $q->where('academic_year_id', $membership->academic_year_id))
            ->max('batch_number') ?? 0;

        $this->qrService->generateForMembership($membership, $lastBatch + 1);

        AuditLogger::log('qr.generated_for_membership', $membership, [], ['membership_id' => $membership->id]);
        return back()->with('success', 'QR code generated successfully.');
    }

    public function generateMissingQrs(Request $request): RedirectResponse
    {
        $request->validate([
            'academic_year_id' => 'nullable|exists:academic_years,id',
        ]);

        $activeYear = AcademicYear::active();
        $yearId = $request->academic_year_id ?? $activeYear?->id;

        $memberships = Membership::where('status', 'active')
            ->when($yearId, fn($q) => $q->where('academic_year_id', $yearId))
            ->whereDoesntHave('qrCodes', fn($q) => $q->where('status', 'active'))
            ->get();

        $lastBatch = QrCode::when($yearId, fn($q) => $q->whereHas('membership', fn($m) => $m->where('academic_year_id', $yearId)))
            ->max('batch_number') ?? 0;
        $batchNumber = $lastBatch + 1;

        $generated = 0;
        $failed = [];

        foreach ($memberships as $membership) {
            try {
                $this->qrService->generateForMembership($membership, $batchNumber);
                $generated++;
            } catch (\Throwable $e) {
                $failed[] = $membership->student->student_number ?? $membership->id;
            }
        }

        AuditLogger::log('qr.batch_generated_missing', null, [], [
            'academic_year_id' => $yearId,
            'batch_number' => $batchNumber,
            'generated' => $generated,
            'failed' => count($failed),
        ]);

        $message = "QR Codes Generated: {$generated}. Already Existing: 0. Failed: " . count($failed) . '.';
        if (!empty($failed)) {
            $message .= ' Failed students: ' . implode(', ', $failed);
        }

        return back()->with('success', $message);
    }

    public function bulkActivate(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'academic_year_id' => 'required|exists:academic_years,id',
            'fee_paid' => 'nullable|numeric|min:0',
        ]);

        $rows = \Maatwebsite\Excel\Facades\Excel::toArray(
            new \App\Imports\MasterlistImport,
            $request->file('file')
        );

        $data = $rows[0] ?? [];
        $count = 0;

        $lastBatch = QrCode::whereHas('membership', fn($q) => $q->where('academic_year_id', $request->academic_year_id))
            ->max('batch_number') ?? 0;
        $batchNumber = $lastBatch + 1;

        foreach ($data as $row) {
            $rowNormalized = [];
            foreach ((array) $row as $key => $val) {
                $cleanKey = strtolower(trim((string) $key));
                $cleanKey = preg_replace('/\s+/', ' ', $cleanKey);
                $cleanKey = str_replace(['_', '-'], ' ', $cleanKey);
                $rowNormalized[$cleanKey] = $val;
            }

            $extractField = function (array $candidateKeys) use ($rowNormalized): string {
                foreach ($candidateKeys as $key) {
                    $normalizedKey = strtolower(trim($key));
                    $normalizedKey = preg_replace('/\s+/', ' ', $normalizedKey);
                    $normalizedKey = str_replace(['_', '-'], ' ', $normalizedKey);
                    if (array_key_exists($normalizedKey, $rowNormalized) && $rowNormalized[$normalizedKey] !== null) {
                        return (string) $rowNormalized[$normalizedKey];
                    }
                }
                foreach ($rowNormalized as $k => $v) {
                    $strippedKey = preg_replace('/[^a-z0-9]/', '', $k);
                    foreach ($candidateKeys as $key) {
                        $strippedCandidate = preg_replace('/[^a-z0-9]/', '', strtolower($key));
                        if ($strippedKey === $strippedCandidate && $v !== null) {
                            return (string) $v;
                        }
                    }
                }
                return '';
            };

            $sn = trim($extractField([
                'student number',
                'student no',
                'student id',
                'student',
                'id number',
                'id no',
                'id',
            ]));
            if (!$sn) continue;

            $student = Student::where('student_number', $sn)->first();
            $lastName = trim($extractField(['last name', 'lastname', 'surname', 'family name']));
            $firstName = trim($extractField(['first name', 'firstname', 'given name']));
            $middleName = trim($extractField(['middle name', 'middlename', 'middle initial', 'mi']));
            $rawName = trim($extractField(['name', 'full name', 'fullname', 'student name']));

            $rawProgram = trim($extractField([
                'program',
                'course',
                'program / course',
                'program/course',
                'course / program',
                'course/program',
                'degree program',
                'degree',
            ]));
            $program = Student::normalizeProgram($rawProgram);

            $rawYear = trim($extractField([
                'year level',
                'yearlevel',
                'year',
                'yr level',
                'yr',
                'level',
            ]));
            $normalizedYear = Student::normalizeYearLevel($rawYear);

            if (!$student) {
                if (!empty($lastName) || !empty($firstName)) {
                    $student = Student::create([
                        'student_number' => $sn,
                        'last_name' => $lastName ?: $sn,
                        'first_name' => $firstName ?: $sn,
                        'middle_name' => $middleName ?: null,
                        'program' => $program ?: null,
                        'year_level' => $normalizedYear ?: '1st Year',
                    ]);
                } elseif (!empty($rawName)) {
                    $parts = array_map('trim', explode(',', $rawName, 2));
                    $rest = isset($parts[1]) ? array_map('trim', explode(' ', $parts[1], 2)) : [];
                    $student = Student::create([
                        'student_number' => $sn,
                        'last_name' => $parts[0] ?: $sn,
                        'first_name' => $rest[0] ?? $sn,
                        'middle_name' => $rest[1] ?? null,
                        'program' => $program ?: null,
                        'year_level' => $normalizedYear ?: '1st Year',
                    ]);
                }
            } else {
                $updateData = [];
                if ($normalizedYear && empty($student->year_level)) {
                    $updateData['year_level'] = $normalizedYear;
                }
                if ($program && empty($student->program)) {
                    $updateData['program'] = $program;
                }
                if (!empty($updateData)) {
                    $student->update($updateData);
                }
            }

            if (!$student) continue;

            $membership = Membership::firstOrCreate([
                'student_id' => $student->id,
                'academic_year_id' => $request->academic_year_id,
            ], [
                'status' => 'active',
                'fee_paid' => $request->fee_paid,
                'paid_at' => now()->toDateString(),
            ]);

            if ($membership) {
                $membership->update([
                    'status' => 'active',
                    'fee_paid' => $request->fee_paid ?? $membership->fee_paid,
                    'paid_at' => $membership->paid_at ?? now()->toDateString(),
                ]);

                if (!$membership->activeQrCode) {
                    try {
                        $this->qrService->generateForMembership($membership, $batchNumber);
                    } catch (\Throwable $e) {
                    }
                }

                $count++;
            }
        }

        AuditLogger::log('membership.bulk_activated', null, [], [
            'count' => $count,
            'academic_year_id' => $request->academic_year_id,
        ]);

        return back()->with('success', "{$count} memberships activated.");
    }
}
