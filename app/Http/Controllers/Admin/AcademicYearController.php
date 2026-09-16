<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicYearController extends Controller
{
    public function index(): View
    {
        $academicYears = AcademicYear::orderByDesc('year_start')->paginate(15);
        $years = $academicYears;
        return view('admin.academic-years.index', compact('academicYears', 'years'));
    }

    public function create(): View
    {
        return view('admin.academic-years.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label' => 'nullable|string|max:255',
            'year_start' => 'nullable|integer|min:2000|max:2100',
            'year_end' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        if (empty($data['label']) && !empty($data['year_start']) && !empty($data['year_end'])) {
            $data['label'] = 'AY ' . $data['year_start'] . '–' . $data['year_end'];
        }

        if (empty($data['year_start']) && !empty($data['label']) && preg_match('/(\d{4})\s*[-–]\s*(\d{4})/', $data['label'], $matches)) {
            $data['year_start'] = (int) $matches[1];
            $data['year_end'] = (int) $matches[2];
        }

        if (empty($data['year_start'])) {
            $data['year_start'] = (int) date('Y');
            $data['year_end'] = $data['year_start'] + 1;
        }

        if (empty($data['label'])) {
            $data['label'] = 'AY ' . $data['year_start'] . '–' . $data['year_end'];
        }

        $isActive = !empty($data['is_active']);
        unset($data['is_active']);

        $year = AcademicYear::create($data);

        if ($isActive) {
            $year->activate();
        }

        AuditLogger::log('academic_year.created', $year, [], $year->toArray());

        return redirect()->route('admin.academic-years.index')
            ->with('success', "Academic year {$year->label} created.");
    }

    public function edit(AcademicYear $academicYear): View
    {
        return view('admin.academic-years.edit', compact('academicYear'));
    }

    public function update(Request $request, AcademicYear $academicYear): RedirectResponse
    {
        $data = $request->validate([
            'label' => 'required|string|max:255',
        ]);

        if (preg_match('/(\d{4})\s*[-–]\s*(\d{4})/', $data['label'], $matches)) {
            $data['year_start'] = (int) $matches[1];
            $data['year_end'] = (int) $matches[2];
        }

        $old = $academicYear->toArray();
        $academicYear->update($data);
        AuditLogger::log('academic_year.updated', $academicYear, $old, $academicYear->toArray());

        return redirect()->route('admin.academic-years.index')
            ->with('success', "Academic year {$academicYear->label} updated.");
    }

    public function activate(AcademicYear $academicYear): RedirectResponse
    {
        $academicYear->activate();
        AuditLogger::log('academic_year.activated', $academicYear);

        return redirect()->route('admin.academic-years.index')
            ->with('success', "{$academicYear->label} set as active.");
    }

    public function destroy(AcademicYear $academicYear): RedirectResponse
    {
        if ($academicYear->is_active) {
            return back()->with('error', 'Cannot delete the active academic year.');
        }

        $label = $academicYear->label;
        AuditLogger::log('academic_year.deleted', $academicYear, $academicYear->toArray());
        $academicYear->delete();

        return redirect()->route('admin.academic-years.index')
            ->with('success', "{$label} deleted.");
    }
}
