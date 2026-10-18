<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\Event;
use App\Models\EventFeedback;
use App\Models\EventRegistration;
use App\Models\VolunteerApplication;
use App\Models\XpTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventAnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $selectedEventId = $request->input('event_id');
        $academicYearId = $request->input('academic_year_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $category = $request->input('category');

        $events = Event::orderByDesc('event_date')->get();
        $academicYears = AcademicYear::orderByDesc('year_start')->get();

        // Target event query
        $eventQuery = Event::query();
        if ($selectedEventId) {
            $eventQuery->where('id', $selectedEventId);
        }
        if ($academicYearId) {
            $eventQuery->where('academic_year_id', $academicYearId);
        }
        if ($dateFrom) {
            $eventQuery->whereDate('event_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $eventQuery->whereDate('event_date', '<=', $dateTo);
        }
        if ($category) {
            $eventQuery->where('event_type', $category);
        }

        $filteredEvents = $eventQuery->get();
        $filteredEventIds = $filteredEvents->pluck('id')->all();

        // 1. Participation Metrics
        $totalRegistrations = EventRegistration::whereIn('event_id', $filteredEventIds)->count();
        $confirmedRegistrations = EventRegistration::whereIn('event_id', $filteredEventIds)
            ->whereIn('status', ['confirmed', 'approved', 'attended'])
            ->count();

        // Unique Attendees (Authoritative unique participants from attendance records)
        $uniqueAttendees = AttendanceRecord::whereIn('event_id', $filteredEventIds)
            ->whereNotNull('participant_id')
            ->distinct('participant_id')
            ->count('participant_id');

        // Attendance Rate: unique attendees / confirmed registrations (or total reg if confirmed is 0)
        $attendanceDenominator = $confirmedRegistrations > 0 ? $confirmedRegistrations : ($totalRegistrations > 0 ? $totalRegistrations : 0);
        $attendanceRate = $attendanceDenominator > 0 ? round(($uniqueAttendees / $attendanceDenominator) * 100, 1) : 0;
        $noShowCount = max(0, $attendanceDenominator - $uniqueAttendees);
        $noShowRate = $attendanceDenominator > 0 ? round(($noShowCount / $attendanceDenominator) * 100, 1) : 0;

        // 2. Attendance Performance Metrics
        $checkInScans = AttendanceRecord::whereIn('event_id', $filteredEventIds)->where('action', 'in')->count();
        $checkOutScans = AttendanceRecord::whereIn('event_id', $filteredEventIds)->where('action', 'out')->count();
        $correctedScans = AttendanceRecord::whereIn('event_id', $filteredEventIds)->where('is_corrected', true)->count();
        $completionRate = $checkInScans > 0 ? round(($checkOutScans / $checkInScans) * 100, 1) : 0;

        // 3. Engagement Breakdown by Program and Year Level (subject to permissions & privacy)
        $programBreakdown = DB::table('attendance_records')
            ->join('students', 'attendance_records.participant_id', '=', 'students.id')
            ->whereIn('attendance_records.event_id', $filteredEventIds)
            ->select('students.program', DB::raw('COUNT(DISTINCT students.id) as attendee_count'))
            ->groupBy('students.program')
            ->orderByDesc('attendee_count')
            ->get();

        $yearLevelBreakdown = DB::table('attendance_records')
            ->join('students', 'attendance_records.participant_id', '=', 'students.id')
            ->whereIn('attendance_records.event_id', $filteredEventIds)
            ->whereNotNull('students.year_level')
            ->select('students.year_level', DB::raw('COUNT(DISTINCT students.id) as attendee_count'))
            ->groupBy('students.year_level')
            ->orderBy('students.year_level')
            ->get();

        // Volunteer Service for these events
        $volunteerStats = VolunteerApplication::whereHas('opportunity', function ($q) use ($filteredEventIds) {
            $q->whereIn('event_id', $filteredEventIds);
        })->where('status', 'completed')
        ->selectRaw('COUNT(DISTINCT user_id) as volunteer_count, SUM(approved_service_hours) as total_hours')
        ->first();

        // 4. Feedback and Impact (Real Survey Data)
        $feedbackCount = EventFeedback::whereIn('event_id', $filteredEventIds)->count();
        $averageRating = $feedbackCount > 0 ? round(EventFeedback::whereIn('event_id', $filteredEventIds)->avg('rating'), 2) : 0;
        $orgRating = $feedbackCount > 0 ? round(EventFeedback::whereIn('event_id', $filteredEventIds)->avg('organization_rating'), 2) : 0;
        $contentRating = $feedbackCount > 0 ? round(EventFeedback::whereIn('event_id', $filteredEventIds)->avg('content_rating'), 2) : 0;
        $feedbackSamples = EventFeedback::whereIn('event_id', $filteredEventIds)->whereNotNull('positive_feedback')->latest()->take(5)->get();

        // 5. Authoritative EXP Distribution for these events
        $expIssued = (int) XpTransaction::whereIn('source_id', $filteredEventIds)
            ->where('source_type', 'attendance')
            ->sum('amount');

        return view('admin.analytics.index', compact(
            'events',
            'academicYears',
            'selectedEventId',
            'academicYearId',
            'dateFrom',
            'dateTo',
            'category',
            'filteredEvents',
            'totalRegistrations',
            'confirmedRegistrations',
            'uniqueAttendees',
            'attendanceRate',
            'noShowRate',
            'noShowCount',
            'checkInScans',
            'checkOutScans',
            'correctedScans',
            'completionRate',
            'programBreakdown',
            'yearLevelBreakdown',
            'volunteerStats',
            'feedbackCount',
            'averageRating',
            'orgRating',
            'contentRating',
            'feedbackSamples',
            'expIssued'
        ));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $selectedEventId = $request->input('event_id');
        $eventQuery = Event::query();
        if ($selectedEventId) {
            $eventQuery->where('id', $selectedEventId);
        }
        $filteredEvents = $eventQuery->get();
        $filteredEventIds = $filteredEvents->pluck('id')->all();

        $records = AttendanceRecord::with(['event', 'student', 'attendanceSession'])
            ->whereIn('event_id', $filteredEventIds)
            ->orderByDesc('scanned_at')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="event_analytics_report_' . date('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Event Name',
                'Event Date',
                'Student Number',
                'Student Name',
                'Program',
                'Year Level',
                'Session',
                'Action',
                'Status',
                'Scanned At',
                'Is Corrected',
            ]);

            foreach ($records as $r) {
                fputcsv($handle, [
                    $r->event?->name ?? 'N/A',
                    $r->event?->event_date?->format('Y-m-d') ?? 'N/A',
                    $r->student?->student_number ?? 'Non-Student',
                    $r->student ? ($r->student->last_name . ', ' . $r->student->first_name) : 'External Guest',
                    $r->student?->program ?? 'N/A',
                    $r->student?->year_level ?? 'N/A',
                    $r->attendanceSession?->session_name ?? 'N/A',
                    strtoupper($r->action),
                    ucfirst($r->status),
                    $r->scanned_at?->format('Y-m-d H:i:s') ?? '',
                    $r->is_corrected ? 'Yes' : 'No',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
