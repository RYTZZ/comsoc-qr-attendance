<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\AttendanceSheetService;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class AttendanceSheetController extends Controller
{
    public function __construct(private AttendanceSheetService $sheets) {}

    /** Names of attendees without a registered signature (no signature data is returned). */
    public function check(Event $event): JsonResponse
    {
        Gate::authorize('export-attendance-sheet', $event);
        $c = $this->sheets->check($event);
        $issued = $this->sheets->latestIssuance($event);
        $attendeeCount = $c['rows']->count();
        $missingCount = $c['missing']->count();

        return response()->json([
            'attendee_count' => $attendeeCount,
            'registered_count' => max(0, $attendeeCount - $missingCount),
            'missing_count' => $missingCount,
            'missing' => $c['missing']->pluck('name')->values()->all(),
            'already_issued_at' => $issued?->issued_at?->toIso8601String(),
        ]);
    }

    public function download(Request $request, Event $event): Response
    {
        Gate::authorize('export-attendance-sheet', $event);

        $data = $request->validate([
            'reissue' => ['nullable', 'boolean'],
            'reason' => ['required_if:reissue,1,true', 'nullable', 'string', 'min:10', 'max:500'],
        ]);

        $previous = $this->sheets->latestIssuance($event);
        $filename = 'attendance-sheet-' . \Illuminate\Support\Str::slug($event->name ?: 'event') . '.pdf';
        $headers = ['Content-Type' => 'application/pdf', 'Cache-Control' => 'no-store, private',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'];

        // A finalized sheet is served byte-for-byte from the private archive unless an explicit, reasoned reissue is requested.
        if ($previous && empty($data['reissue'])) {
            $pdf = $this->sheets->readArchive($previous);
            abort_if($pdf === null, 500, 'The archived attendance sheet could not be verified. Please contact an administrator.');
            AuditLogger::log('attendance_sheet.downloaded', $event, [], ['issuance_id' => $previous->id, 'sha256' => $previous->pdf_sha256]);

            return response($pdf, 200, $headers);
        }

        try {
            [$issuance, $pdf] = $this->sheets->issue($event, $request->user(), $data['reason'] ?? null, $previous);
        } catch (\Throwable $e) {
            report($e);
            abort(500, 'The attendance sheet could not be generated.');
        }

        AuditLogger::log($previous ? 'attendance_sheet.reissued' : 'attendance_sheet.exported', $event, [], [
            'issuance_id' => $issuance->id,
            'sha256' => $issuance->pdf_sha256,
            'attendees' => $issuance->attendee_count,
            'missing_signatures' => $issuance->missing_signature_count,
        ], $data['reason'] ?? null);

        return response($pdf, 200, $headers);
    }
}
