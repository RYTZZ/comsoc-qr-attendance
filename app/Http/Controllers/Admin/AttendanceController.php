<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Event;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $events = Event::orderByDesc('event_date')->get();

        $records = AttendanceRecord::with(['event', 'attendanceSession', 'kiosk', 'scannedByUser', 'correctedByUser'])
            ->when($request->event_id, fn($q) => $q->where('event_id', $request->event_id))
            ->when($request->session_type, fn($q) => $q->whereHas('attendanceSession', fn($s) => $s->where('type', $request->session_type)))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('qr_token', 'like', "%{$request->search}%"))
            ->orderByDesc('scanned_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.attendance.index', compact('records', 'events'));
    }

    public function correct(Request $request, AttendanceRecord $record): RedirectResponse
    {
        $data = $request->validate([
            'status' => 'required|in:present,late',
            'action' => 'required|in:in,out',
            'scanned_at' => 'required|date',
            'reason' => 'required|string|min:10|max:1000',
        ]);

        $old = $record->only(['status', 'action', 'scanned_at']);

        $record->update([
            'status' => $data['status'],
            'action' => $data['action'],
            'scanned_at' => $data['scanned_at'],
            'is_corrected' => true,
            'corrected_by' => auth()->id(),
            'corrected_at' => now(),
            'correction_reason' => $data['reason'],
        ]);

        AuditLogger::log('attendance.corrected', $record, $old, [
            'status' => $data['status'],
            'action' => $data['action'],
            'scanned_at' => $data['scanned_at'],
        ], $data['reason']);

        return back()->with('success', 'Attendance record corrected.');
    }

    public function destroy(Request $request, AttendanceRecord $record): RedirectResponse
    {
        $request->validate(['reason' => 'required|string|min:10|max:1000']);

        AuditLogger::log('attendance.deleted', $record, $record->toArray(), [], $request->reason);
        $record->delete();

        return back()->with('success', 'Attendance record deleted.');
    }
}
