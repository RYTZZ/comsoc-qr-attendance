<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\Event;
use App\Models\Incident;
use App\Models\Membership;
use App\Models\QrCode;
use App\Models\SnackClaim;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('admin.reports.index');
    }

    public function membership(Request $request): View|\Symfony\Component\HttpFoundation\Response
    {
        $academicYears = AcademicYear::orderByDesc('year_start')->get();
        $data = Membership::with(['student', 'academicYear', 'activeQrCode'])
            ->when($request->academic_year_id, fn($q) => $q->where('academic_year_id', $request->academic_year_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('created_at')
            ->get();

        if ($request->export === 'pdf') {
            $pdf = Pdf::loadView('reports.membership-pdf', compact('data'));
            return $pdf->download('membership_report.pdf');
        }

        return view('admin.reports.membership', compact('data', 'academicYears'));
    }

    public function attendance(Request $request): View|StreamedResponse
    {
        $events = Event::orderByDesc('event_date')->get();
        $data = AttendanceRecord::with(['event', 'attendanceSession', 'kiosk', 'scannedByUser'])
            ->when($request->event_id, fn($q) => $q->where('event_id', $request->event_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->date_from, fn($q) => $q->whereDate('scanned_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('scanned_at', '<=', $request->date_to))
            ->orderByDesc('scanned_at')
            ->get();

        if ($request->export === 'csv') {
            return $this->exportCsv($data, 'attendance_report');
        }

        return view('admin.reports.attendance', compact('data', 'events'));
    }

    public function qrCard(Request $request): View
    {
        $academicYears = AcademicYear::orderByDesc('year_start')->get();
        $data = QrCode::with(['membership.student', 'card'])
            ->when($request->academic_year_id, fn($q) => $q->whereHas('membership', fn($m) => $m->where('academic_year_id', $request->academic_year_id)))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->get();

        return view('admin.reports.qr-card', compact('data', 'academicYears'));
    }

    public function snacks(Request $request): View
    {
        $events = Event::orderByDesc('event_date')->get();
        $data = SnackClaim::with(['snackSession.event', 'snackInventory', 'kiosk', 'distributedByUser'])
            ->when($request->event_id, fn($q) => $q->whereHas('snackSession', fn($s) => $s->where('event_id', $request->event_id)))
            ->when($request->date_from, fn($q) => $q->whereDate('claimed_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('claimed_at', '<=', $request->date_to))
            ->orderByDesc('claimed_at')
            ->get();

        return view('admin.reports.snacks', compact('data', 'events'));
    }

    public function incidents(Request $request): View
    {
        $events = Event::orderByDesc('event_date')->get();
        $data = Incident::with(['reportedByUser', 'resolvedByUser', 'event'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->event_id, fn($q) => $q->where('event_id', $request->event_id))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->orderByDesc('created_at')
            ->get();

        return view('admin.reports.incidents', compact('data', 'events'));
    }

    private function exportCsv($data, string $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
        ];

        $callback = function () use ($data) {
            $handle = fopen('php://output', 'w');
            foreach ($data as $row) {
                fputcsv($handle, is_array($row) ? $row : $row->toArray());
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
