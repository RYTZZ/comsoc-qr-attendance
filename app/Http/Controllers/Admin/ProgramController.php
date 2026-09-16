<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(Request $request): View
    {
        if (Program::count() === 0) {
            Program::seedDefaults();
        }

        $programs = Program::orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.programs.index', compact('programs'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'abbreviation' => ['required', 'string', 'max:20'],
        ]);

        $displayName = trim($validated['name']) . ' (' . trim($validated['abbreviation']) . ')';

        $program = Program::create([
            'name' => trim($validated['name']),
            'abbreviation' => trim($validated['abbreviation']),
            'display_name' => $displayName,
            'is_active' => true,
            'sort_order' => (Program::max('sort_order') ?? 0) + 1,
        ]);

        AuditLogger::log('program.created', $program, [], $program->toArray());

        return back()->with('success', "Program '{$program->display_name}' added successfully.");
    }

    public function update(Request $request, Program $program): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'abbreviation' => ['required', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $displayName = trim($validated['name']) . ' (' . trim($validated['abbreviation']) . ')';
        $old = $program->toArray();

        $program->update([
            'name' => trim($validated['name']),
            'abbreviation' => trim($validated['abbreviation']),
            'display_name' => $displayName,
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditLogger::log('program.updated', $program, $old, $program->fresh()->toArray());

        return back()->with('success', "Program updated successfully.");
    }

    public function toggleActive(Program $program): RedirectResponse
    {
        $old = ['is_active' => $program->is_active];
        $program->is_active = !$program->is_active;
        $program->save();

        AuditLogger::log('program.toggled', $program, $old, ['is_active' => $program->is_active]);

        return back()->with('success', "Program status updated.");
    }

    public function destroy(Program $program): RedirectResponse
    {
        $old = $program->toArray();
        $program->delete();

        AuditLogger::log('program.deleted', null, $old, []);

        return back()->with('success', "Program deleted.");
    }
}

