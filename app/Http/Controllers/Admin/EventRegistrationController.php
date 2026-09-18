<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\QrPngRenderer;

class EventRegistrationController extends Controller
{
    public function index(Request $request, Event $event): View|StreamedResponse
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

        if ($request->input('export') === 'csv') {
            $records = $query->get();
            $sanitizedEventName = trim(preg_replace('/[^A-Za-z0-9_\-]+/', '_', $event->name), '_');
            return $this->exportCsv($records, ($sanitizedEventName ?: 'Event') . '_Registrations');
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

    public function batchApprove(Request $request): RedirectResponse
    {
        $ids = $request->input('registration_ids', []);
        if (empty($ids) || !is_array($ids)) {
            return back()->with('error', 'No registrations selected.');
        }

        $registrations = EventRegistration::whereIn('id', $ids)
            ->where('status', 'pending')
            ->get();

        $count = 0;
        foreach ($registrations as $registration) {
            $registration->approve(auth()->user());
            AuditLogger::log('registration.approved', $registration);
            $count++;
        }

        return back()->with('success', "{$count} participant registration(s) approved and QR codes issued.");
    }

    public function batchReject(Request $request): RedirectResponse
    {
        $ids = $request->input('registration_ids', []);
        $reason = $request->input('reason', 'Administrative decision');
        if (empty($ids) || !is_array($ids)) {
            return back()->with('error', 'No registrations selected.');
        }

        $registrations = EventRegistration::whereIn('id', $ids)
            ->where('status', 'pending')
            ->get();

        $count = 0;
        foreach ($registrations as $registration) {
            $registration->reject(auth()->user(), $reason);
            AuditLogger::log('registration.rejected', $registration, [], ['reason' => $reason]);
            $count++;
        }

        return back()->with('success', "{$count} participant registration(s) rejected.");
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

    private function exportCsv($records, string $filename): StreamedResponse
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
                'Full Name',
                'Email',
                'Phone',
                'School / Organization',
                'Program',
                'Year Level',
                'T-Shirt Size',
                'Food Restrictions',
                'Food Restriction Details',
                'Status',
                'Registered At',
            ]);

            foreach ($sorted as $reg) {
                fputcsv($handle, [
                    $reg->full_name ?? 'N/A',
                    $reg->email ?? 'N/A',
                    $reg->phone ?? '',
                    $reg->organization ?? 'N/A',
                    $reg->program ?? 'N/A',
                    $reg->year_level ?? 'N/A',
                    $reg->tshirt_size ?? 'N/A',
                    $reg->food_restrictions ?? 'None',
                    $reg->food_restriction_details ?? '',
                    ucfirst($reg->status ?? 'pending'),
                    $reg->created_at ? $reg->created_at->format('Y-m-d H:i:s') : 'N/A',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
