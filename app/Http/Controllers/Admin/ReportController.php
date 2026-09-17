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
        $query = AttendanceRecord::with(['event', 'attendanceSession', 'kiosk', 'scannedByUser', 'student', 'eventRegistration'])
            ->when($request->event_id, fn($q) => $q->where('event_id', $request->event_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->date_from, fn($q) => $q->whereDate('scanned_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('scanned_at', '<=', $request->date_to))
            ->orderByDesc('scanned_at');

        $data = $query->get();

        if ($request->export === 'csv') {
            $eventName = 'Attendance';
            if ($request->event_id) {
                $selectedEvent = $events->firstWhere('id', $request->event_id) ?? Event::find($request->event_id);
                if ($selectedEvent && !empty($selectedEvent->name)) {
                    $eventName = $selectedEvent->name;
                }
            } elseif ($data->isNotEmpty() && $data->first()->event && !empty($data->first()->event->name)) {
                $uniqueEventIds = $data->pluck('event_id')->unique();
                if ($uniqueEventIds->count() === 1) {
                    $eventName = $data->first()->event->name;
                }
            }

            $sanitizedFilename = trim(preg_replace('/[^A-Za-z0-9_\-]+/', '_', $eventName), '_');
            if (empty($sanitizedFilename)) {
                $sanitizedFilename = 'Attendance_Report';
            }

            return $this->exportAttendanceCsv($data, $sanitizedFilename);
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

    public function snacks(Request $request): View|StreamedResponse
    {
        $events = Event::orderByDesc('event_date')->get();
        $query = SnackClaim::with(['snackSession.event', 'snackInventory', 'kiosk', 'distributedByUser', 'student', 'eventRegistration'])
            ->when($request->event_id, fn($q) => $q->whereHas('snackSession', fn($s) => $s->where('event_id', $request->event_id)))
            ->when($request->date_from, fn($q) => $q->whereDate('claimed_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('claimed_at', '<=', $request->date_to))
            ->orderByDesc('claimed_at');

        $data = $query->get();

        if ($request->export === 'csv') {
            $eventName = 'Snack_Distribution';
            if ($request->event_id) {
                $selectedEvent = $events->firstWhere('id', $request->event_id) ?? Event::find($request->event_id);
                if ($selectedEvent && !empty($selectedEvent->name)) {
                    $eventName = $selectedEvent->name . '_Snacks';
                }
            }

            $sanitizedFilename = trim(preg_replace('/[^A-Za-z0-9_\-]+/', '_', $eventName), '_');
            return $this->exportSnacksCsv($data, $sanitizedFilename ?: 'Snack_Report');
        }

        return view('admin.reports.snacks', compact('data', 'events'));
    }

    public function incidents(Request $request): View|StreamedResponse
    {
        $events = Event::orderByDesc('event_date')->get();
        $query = Incident::with(['reportedByUser', 'resolvedByUser', 'event'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->event_id, fn($q) => $q->where('event_id', $request->event_id))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->orderByDesc('created_at');

        $data = $query->get();

        if ($request->export === 'csv') {
            $eventName = 'Incidents_Report';
            if ($request->event_id) {
                $selectedEvent = $events->firstWhere('id', $request->event_id) ?? Event::find($request->event_id);
                if ($selectedEvent && !empty($selectedEvent->name)) {
                    $eventName = $selectedEvent->name . '_Incidents';
                }
            }

            $sanitizedFilename = trim(preg_replace('/[^A-Za-z0-9_\-]+/', '_', $eventName), '_');
            return $this->exportIncidentsCsv($data, $sanitizedFilename ?: 'Incidents_Report');
        }

        return view('admin.reports.incidents', compact('data', 'events'));
    }

    private function exportAttendanceCsv($records, string $filename): StreamedResponse
    {
        $sorted = $records->sort(function ($a, $b) {
            $studentA = $a->student;
            $studentB = $b->student;
            $regA = $a->eventRegistration;
            $regB = $b->eventRegistration;

            $programA = $studentA?->program ?? $regA?->program ?? 'N/A';
            $programB = $studentB?->program ?? $regB?->program ?? 'N/A';
            $cmpProgram = strcasecmp($programA, $programB);
            if ($cmpProgram !== 0) return $cmpProgram;

            $yearA = $studentA?->year_level ?? $regA?->year_level ?? 'N/A';
            $yearB = $studentB?->year_level ?? $regB?->year_level ?? 'N/A';
            $cmpYear = strcasecmp($yearA, $yearB);
            if ($cmpYear !== 0) return $cmpYear;

            $nameA = $studentA?->full_name ?? $regA?->full_name ?? '';
            $nameB = $studentB?->full_name ?? $regB?->full_name ?? '';
            return strcasecmp($nameA, $nameB);
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
                'Student ID',
                'Full Name',
                'Program',
                'Year Level',
                'Participant Type',
                'Event',
                'Attendance Session',
                'Action',
                'Attendance Status',
                'Scan Time',
                'Kiosk',
                'Scanned By',
            ]);

            foreach ($sorted as $rec) {
                $student = $rec->student;
                $reg = $rec->eventRegistration;

                $studentId = $student?->student_number ?? ($reg ? 'GUEST-' . substr($reg->id, 0, 8) : 'N/A');
                $fullName = $student?->full_name ?? $reg?->full_name ?? 'N/A';
                $program = $student?->program ?? $reg?->program ?? 'N/A';
                $yearLevel = $student?->year_level ?? $reg?->year_level ?? 'N/A';
                $participantType = $rec->participant_type === 'student' ? 'Student Member' : 'Event Attendee / Guest';
                $eventName = $rec->event?->name ?? 'N/A';
                $sessionName = $rec->attendanceSession?->label ?? $rec->attendanceSession?->type ?? 'N/A';
                $action = strtoupper($rec->action ?? 'IN');
                $status = ucfirst($rec->status ?? 'Present');
                $scanTime = $rec->scanned_at ? $rec->scanned_at->format('Y-m-d H:i:s') : 'N/A';
                $kioskName = $rec->kiosk?->name ?? 'N/A';
                $scannedBy = $rec->scannedByUser?->name ?? 'N/A';

                fputcsv($handle, [
                    $studentId,
                    $fullName,
                    $program,
                    $yearLevel,
                    $participantType,
                    $eventName,
                    $sessionName,
                    $action,
                    $status,
                    $scanTime,
                    $kioskName,
                    $scannedBy,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportSnacksCsv($records, string $filename): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Student ID',
                'Claimant Name',
                'Program',
                'Year Level',
                'Event',
                'Snack Session',
                'Item Claimed',
                'Quantity',
                'Claimed At',
                'Kiosk',
                'Distributed By',
            ]);

            foreach ($records as $claim) {
                $student = $claim->student;
                $reg = $claim->eventRegistration;

                $studentId = $student?->student_number ?? ($reg ? 'GUEST-' . substr($reg->id, 0, 8) : 'N/A');
                $fullName = $student?->full_name ?? $reg?->full_name ?? 'N/A';
                $program = $student?->program ?? $reg?->program ?? 'N/A';
                $yearLevel = $student?->year_level ?? $reg?->year_level ?? 'N/A';
                $eventName = $claim->snackSession?->event?->name ?? 'N/A';
                $sessionName = $claim->snackSession?->name ?? 'N/A';
                $item = $claim->snackInventory?->item_name ?? 'Standard Ration';
                $quantity = $claim->quantity ?? 1;
                $claimedAt = $claim->claimed_at ? $claim->claimed_at->format('Y-m-d H:i:s') : 'N/A';
                $kioskName = $claim->kiosk?->name ?? 'N/A';
                $distributedBy = $claim->distributedByUser?->name ?? 'Kiosk';

                fputcsv($handle, [
                    $studentId,
                    $fullName,
                    $program,
                    $yearLevel,
                    $eventName,
                    $sessionName,
                    $item,
                    $quantity,
                    $claimedAt,
                    $kioskName,
                    $distributedBy,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportIncidentsCsv($records, string $filename): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Incident ID',
                'Date',
                'Event',
                'Category',
                'Severity',
                'Status',
                'Title / Summary',
                'Description',
                'Reported By',
                'Resolved By',
                'Resolution Notes',
            ]);

            foreach ($records as $inc) {
                fputcsv($handle, [
                    'INC-' . substr($inc->id, 0, 8),
                    $inc->created_at ? $inc->created_at->format('Y-m-d H:i:s') : 'N/A',
                    $inc->event?->name ?? 'General',
                    ucwords(str_replace('_', ' ', $inc->category ?? 'General')),
                    strtoupper($inc->severity ?? 'LOW'),
                    ucwords(str_replace('_', ' ', $inc->status ?? 'Open')),
                    $inc->title ?? 'N/A',
                    $inc->description ?? '',
                    $inc->reportedByUser?->name ?? 'N/A',
                    $inc->resolvedByUser?->name ?? 'Unresolved',
                    $inc->resolution_notes ?? '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
