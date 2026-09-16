<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\Event;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IncidentController extends Controller
{
    public function index(Request $request): View
    {
        $events = Event::orderByDesc('event_date')->get();

        $incidents = Incident::with(['reportedByUser', 'resolvedByUser', 'event'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->event_id, fn($q) => $q->where('event_id', $request->event_id))
            ->when($request->category, fn($q) => $q->where('category', $request->category))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.incidents.index', compact('incidents', 'events'));
    }

    public function show(Incident $incident): View
    {
        $incident->load(['reportedByUser', 'resolvedByUser', 'event']);
        return view('admin.incidents.show', compact('incident'));
    }

    public function resolve(Request $request, Incident $incident): RedirectResponse
    {
        $request->validate(['resolution_notes' => 'required|string|min:10|max:2000']);

        if (!$incident->isOpen()) {
            return back()->with('error', 'Incident is not open.');
        }

        $incident->update([
            'status' => 'resolved',
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
            'resolution_notes' => $request->resolution_notes,
        ]);

        AuditLogger::log('incident.resolved', $incident, ['status' => 'open'], [
            'status' => 'resolved',
            'resolution_notes' => $request->resolution_notes,
        ], $request->resolution_notes);

        return back()->with('success', 'Incident resolved.');
    }
}
