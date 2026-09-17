<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\QrPngRenderer;

class EventRegistrationController extends Controller
{
    public function index(Request $request, Event $event): View
    {
        $query = $event->eventRegistrations();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('organization', 'like', "%{$search}%")
                  ->orWhere('program', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($size = $request->input('tshirt_size')) {
            $query->where('tshirt_size', $size);
        }

        if ($yearLevel = $request->input('year_level')) {
            $query->where('year_level', $yearLevel);
        }

        if ($program = $request->input('program')) {
            $abbr = null;
            if (preg_match('/\(([^)]+)\)/', $program, $matches)) {
                $abbr = $matches[1];
            }
            $query->where(function ($pq) use ($program, $abbr) {
                $pq->where('program', $program);
                if ($abbr) {
                    $pq->orWhere('program', $abbr);
                }
            });
        }

        if ($food = $request->input('food_restrictions')) {
            $query->where('food_restrictions', $food);
        }

        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');

        if (in_array($sort, ['full_name', 'email', 'created_at', 'status', 'tshirt_size', 'year_level', 'program'])) {
            $query->orderBy($sort, $direction === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderByDesc('created_at');
        }

        $registrations = $query->paginate(20)->withQueryString();

        $tshirtSizes = EventRegistration::TSHIRT_SIZES;
        $programs = \App\Models\Program::activeOptions();
        if (empty($programs)) {
            $programs = EventRegistration::PROGRAMS;
        }
        $yearLevels = EventRegistration::YEAR_LEVELS;
        $foodRestrictions = EventRegistration::FOOD_RESTRICTIONS;
        $events = Event::where('allow_non_students', true)->orderByDesc('event_date')->get();

        return view('admin.event-registrations.index', compact(
            'event',
            'events',
            'registrations',
            'tshirtSizes',
            'programs',
            'yearLevels',
            'foodRestrictions'
        ));
    }

    public function allRegistrations(Request $request): View|RedirectResponse
    {
        $selectedEventId = $request->input('event_id');
        if ($selectedEventId) {
            $targetEvent = Event::find($selectedEventId);
            if ($targetEvent) {
                return redirect()->route('admin.event-registrations.index', array_merge(['event' => $targetEvent], $request->except('event_id')));
            }
        }

        $latestEvent = Event::where('allow_non_students', true)->latest('event_date')->first()
            ?? Event::latest('event_date')->first();

        if ($latestEvent) {
            return redirect()->route('admin.event-registrations.index', array_merge(['event' => $latestEvent], $request->all()));
        }

        $events = collect();
        $registrations = collect();
        return view('admin.event-registrations.no-events');
    }

    public function show(EventRegistration $registration): View
    {
        $registration->load('event', 'reviewedByUser');

        return view('admin.event-registrations.show', compact('registration'));
    }

    public function approve(Request $request, EventRegistration $registration): RedirectResponse
    {
        if ($registration->status !== 'pending') {
            return back()->with('error', 'Registration already processed.');
        }

        $registration->approve(auth()->user());
        AuditLogger::log('registration.approved', $registration);

        return back()->with('success', 'Registration approved and QR code issued.');
    }

    public function reject(Request $request, EventRegistration $registration): RedirectResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);

        if ($registration->status !== 'pending') {
            return back()->with('error', 'Registration already processed.');
        }

        $registration->reject(auth()->user(), $request->reason);
        AuditLogger::log('registration.rejected', $registration, [], ['reason' => $request->reason]);

        return back()->with('success', 'Registration rejected.');
    }

    public function downloadQr(EventRegistration $registration)
    {
        if (!$registration->isQrValid()) {
            return back()->with('error', 'QR code is not available or has expired.');
        }

        $qrImage = QrPngRenderer::generate($registration->qr_token, 300, 1);

        $filename = 'event_qr_' . str_replace(' ', '_', $registration->full_name) . '.png';

        return response($qrImage)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
