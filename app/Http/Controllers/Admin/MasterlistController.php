<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Membership;
use App\Models\Student;
use App\Services\AuditLogger;
use App\Imports\MasterlistImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class MasterlistController extends Controller
{
    public function create(): View
    {
        $academicYears = AcademicYear::orderByDesc('year_start')->get();
        return view('admin.masterlist.upload', compact('academicYears'));
    }

    public function preview(Request $request): View
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $path = $request->file('file')->store('imports', 'local');
        $rows = Excel::toArray(new MasterlistImport, storage_path('app/private/' . $path));
        $data = $rows[0] ?? [];

        $academicYear = AcademicYear::findOrFail($request->academic_year_id);
        $preview = $this->classifyRows($data, $academicYear->id);

        session(['masterlist_import' => [
            'path' => $path,
            'academic_year_id' => $request->academic_year_id,
        ]]);

        return view('admin.masterlist.preview', compact('preview', 'academicYear'));
    }

    public function import(Request $request): RedirectResponse
    {
        $importData = session('masterlist_import');
        if (!$importData) {
            return redirect()->route('admin.masterlist.upload')->with('error', 'Session expired. Please re-upload.');
        }

        $academicYear = AcademicYear::findOrFail($importData['academic_year_id']);
        $rows = Excel::toArray(new MasterlistImport, storage_path('app/private/' . $importData['path']));
        $data = $rows[0] ?? [];
        $preview = $this->classifyRows($data, $academicYear->id);

        $imported = 0;
        DB::transaction(function () use ($preview, $academicYear, &$imported) {
            foreach ($preview['new'] as $row) {
                $student = Student::create([
                    'student_number' => $row['student_number'],
                    'last_name' => $row['last_name'],
                    'first_name' => $row['first_name'],
                    'middle_name' => $row['middle_name'] ?? null,
                    'program' => $row['program'] ?? null,
                    'year_level' => $row['year_level'] ?? null,
                ]);

                Membership::create([
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYear->id,
                    'status' => 'inactive',
                ]);

                $imported++;
            }

            foreach ($preview['existing'] as $row) {
                $student = Student::where('student_number', $row['student_number'])->first();
                if ($student) {
                    $student->update(array_filter([
                        'program' => $row['program'] ?? null,
                        'year_level' => $row['year_level'] ?? null,
                    ]));
                }
                Membership::firstOrCreate([
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYear->id,
                ], ['status' => 'inactive']);
                $imported++;
            }
        });

        session()->forget('masterlist_import');

        AuditLogger::log('masterlist.imported', $academicYear, [], [
            'academic_year' => $academicYear->label,
            'imported_count' => $imported,
        ]);

        return redirect()->route('admin.students.index')
            ->with('success', "Masterlist imported: {$imported} records processed for {$academicYear->label}.");
    }

    private function classifyRows(array $data, string $academicYearId): array
    {
        $new = $existing = $duplicates = $invalid = [];
        $seenNumbers = [];

        foreach ($data as $row) {
            $rowClean = array_change_key_case((array) $row, CASE_LOWER);

            $studentNumber = trim(
                $rowClean['student_number'] 
                ?? $rowClean['student number'] 
                ?? $rowClean['student_id'] 
                ?? $rowClean['student id'] 
                ?? $rowClean['id'] 
                ?? ''
            );

            $lastName = trim($rowClean['last_name'] ?? $rowClean['last name'] ?? $rowClean['lastname'] ?? '');
            $firstName = trim($rowClean['first_name'] ?? $rowClean['first name'] ?? $rowClean['firstname'] ?? '');
            $middleName = trim($rowClean['middle_name'] ?? $rowClean['middle name'] ?? $rowClean['middlename'] ?? '');
            $rawName = trim($rowClean['name'] ?? $rowClean['full_name'] ?? $rowClean['full name'] ?? '');

            if (empty($studentNumber) && empty($rawName) && empty($lastName) && empty($firstName)) {
                continue;
            }

            if (empty($studentNumber) || (empty($rawName) && empty($lastName) && empty($firstName))) {
                $invalid[] = ['reason' => 'Missing student number or name', 'raw' => $row];
                continue;
            }

            if (isset($seenNumbers[$studentNumber])) {
                $duplicates[] = array_merge($row, ['student_number' => $studentNumber]);
                continue;
            }
            $seenNumbers[$studentNumber] = true;

            if (!empty($lastName) || !empty($firstName)) {
                $nameParts = [
                    'last_name' => $lastName,
                    'first_name' => $firstName,
                    'middle_name' => $middleName ?: null,
                ];
            } else {
                $nameParts = $this->parseName($rawName);
            }

            $program = trim($rowClean['program'] ?? $rowClean['course'] ?? $rowClean['degree'] ?? '');
            $yearLevel = trim($rowClean['year_level'] ?? $rowClean['year level'] ?? $rowClean['year'] ?? $rowClean['yr'] ?? '');

            $rowData = array_merge($nameParts, [
                'student_number' => $studentNumber,
                'program' => $program ?: null,
                'year_level' => $yearLevel ?: null,
            ]);

            $student = Student::where('student_number', $studentNumber)->first();
            if ($student) {
                $hasMembership = Membership::where('student_id', $student->id)
                    ->where('academic_year_id', $academicYearId)
                    ->exists();
                if ($hasMembership) {
                    $duplicates[] = $rowData;
                } else {
                    $existing[] = $rowData;
                }
            } else {
                $new[] = $rowData;
            }
        }

        return compact('new', 'existing', 'duplicates', 'invalid');
    }

    private function parseName(string $raw): array
    {
        $parts = array_map('trim', explode(',', $raw, 2));
        $lastName = $parts[0] ?? '';
        $rest = isset($parts[1]) ? array_map('trim', explode(' ', $parts[1], 2)) : [];
        return [
            'last_name' => $lastName,
            'first_name' => $rest[0] ?? '',
            'middle_name' => $rest[1] ?? null,
        ];
    }
}
