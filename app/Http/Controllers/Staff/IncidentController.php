<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Incident;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IncidentController extends Controller
{
    public function create(Request $request): View
    {
        $events = Event::where('event_date', '>=', now()->subDays(7))->orderByDesc('event_date')->get();
        return view('staff.incidents.create', compact('events'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'event_id' => 'nullable|exists:events,id',
            'category' => 'required|in:qr_identity_issue,attendance_issue,disruptive_conduct,harassment_bullying,property_issue,safety_concern,other',
            'description' => 'required|string|min:20|max:3000',
        ]);

        $data['reported_by'] = auth()->id();
        $incident = Incident::create($data);

        return redirect()->route('staff.incidents.show', $incident)
            ->with('success', 'Incident report submitted.');
    }

    public function show(Incident $incident): View
    {
        if ($incident->reported_by !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }
        $incident->load(['event', 'resolvedByUser']);
        return view('staff.incidents.show', compact('incident'));
    }

    public function myIncidents(): View
    {
        $incidents = Incident::where('reported_by', auth()->id())
            ->orderByDesc('created_at')
            ->paginate(15);
        return view('staff.incidents.my', compact('incidents'));
    }
}
