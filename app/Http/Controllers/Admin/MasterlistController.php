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
        $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($path);
        $rows = Excel::toArray(new MasterlistImport, $fullPath);
        $data = $rows[0] ?? [];

        $academicYear = AcademicYear::findOrFail($request->academic_year_id);
        $preview = $this->classifyRows($data, $academicYear->id);

        session(['masterlist_import' => [
            'path' => $path,
            'academic_year_id' => $request->academic_year_id,
            'corrected_records' => [],
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
        $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($importData['path']);
        $rows = Excel::toArray(new MasterlistImport, $fullPath);
        $data = $rows[0] ?? [];

        $corrections = $request->input('corrections', []);
        $preview = $this->classifyRows($data, $academicYear->id, $corrections);

        $imported = 0;
        DB::transaction(function () use ($preview, $academicYear, &$imported) {
            foreach ($preview['new'] as $row) {
                $student = Student::create([
                    'student_number' => $row['student_number'],
                    'last_name' => $row['last_name'],
                    'first_name' => $row['first_name'],
                    'middle_name' => $row['middle_name'] ?? null,
                    'program' => $row['program'] ?? null,
                    'year_level' => $row['year_level'],
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
                    $updateData = [];
                    if (!empty($row['program'])) {
                        $updateData['program'] = $row['program'];
                    }
                    if (!empty($row['year_level'])) {
                        $updateData['year_level'] = $row['year_level'];
                    }
                    if (!empty($updateData)) {
                        $student->update($updateData);
                    }
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

    private function classifyRows(array $data, string $academicYearId, array $corrections = []): array
    {
        $new = $existing = $duplicates = $invalid = [];
        $seenNumbers = [];

        foreach ($data as $index => $row) {
            $rowNormalized = [];
            foreach ((array) $row as $key => $val) {
                $cleanKey = strtolower(trim((string) $key));
                $cleanKey = preg_replace('/\s+/', ' ', $cleanKey);
                $cleanKey = str_replace(['_', '-'], ' ', $cleanKey);
                $rowNormalized[$cleanKey] = $val;
            }

            $studentNumber = trim(
                $this->extractFieldValue($rowNormalized, [
                    'student number',
                    'student no',
                    'student id',
                    'student',
                    'id number',
                    'id no',
                    'id',
                ])
            );

            $lastName = trim($this->extractFieldValue($rowNormalized, ['last name', 'lastname', 'surname', 'family name']));
            $firstName = trim($this->extractFieldValue($rowNormalized, ['first name', 'firstname', 'given name']));
            $middleName = trim($this->extractFieldValue($rowNormalized, ['middle name', 'middlename', 'middle initial', 'mi']));
            $rawName = trim($this->extractFieldValue($rowNormalized, ['name', 'full name', 'fullname', 'student name']));

            if (empty($studentNumber) && empty($rawName) && empty($lastName) && empty($firstName)) {
                continue;
            }

            if (empty($studentNumber) || (empty($rawName) && empty($lastName) && empty($firstName))) {
                $invalid[] = ['reason' => 'Missing student number or name', 'raw' => $row, 'index' => $index];
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

            $rawProgram = trim($this->extractFieldValue($rowNormalized, [
                'program',
                'course',
                'program / course',
                'program/course',
                'course / program',
                'course/program',
                'degree program',
                'degree',
            ]));

            $normalizedProgram = Student::normalizeProgram($rawProgram);

            $rawYear = trim($this->extractFieldValue($rowNormalized, [
                'year level',
                'yearlevel',
                'year',
                'yr level',
                'yr',
                'level',
            ]));

            if (isset($corrections[$studentNumber]['year_level'])) {
                $rawYear = $corrections[$studentNumber]['year_level'];
            } elseif (isset($corrections[$index]['year_level'])) {
                $rawYear = $corrections[$index]['year_level'];
            }

            $normalizedYear = Student::normalizeYearLevel($rawYear);

            $student = Student::where('student_number', $studentNumber)->first();

            if (!$normalizedYear && $student && $student->year_level) {
                $normalizedYear = $student->year_level;
            }

            $finalProgram = $normalizedProgram ?: ($student?->program ?? null);

            $rowData = array_merge($nameParts, [
                'index' => $index,
                'student_number' => $studentNumber,
                'program' => $finalProgram,
                'year_level' => $normalizedYear,
                'raw_year_level' => $rawYear,
            ]);

            if (empty($normalizedYear)) {
                $invalid[] = array_merge($rowData, [
                    'reason' => 'Missing or invalid Year Level (must be 1st Year, 2nd Year, 3rd Year, or 4th Year)',
                    'raw' => $row,
                ]);
                continue;
            }

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

    private function extractFieldValue(array $rowNormalized, array $candidateKeys): string
    {
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
