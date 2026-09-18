<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\Event;
use App\Models\Incident;
use App\Models\Membership;
use App\Models\Student;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $activeYear = AcademicYear::active();

        $totalStudents = Student::count();
        $activeMembers = $activeYear
            ? Membership::where('academic_year_id', $activeYear->id)->where('status', 'active')->count()
            : 0;

        $programBreakdown = Student::selectRaw('COALESCE(program, "BSIT") as prog, COUNT(*) as count')
            ->groupBy('prog')
            ->orderByDesc('count')
            ->get();

        $yearLevelBreakdown = Student::whereNotNull('year_level')
            ->selectRaw('year_level, COUNT(*) as count')
            ->groupBy('year_level')
            ->orderBy('year_level')
            ->get();

        $recentScans = AttendanceRecord::with(['student', 'event', 'attendanceSession'])
            ->latest('scanned_at')
            ->take(6)
            ->get();

        $stats = [
            'total_students' => $totalStudents,
            'active_members' => $activeMembers,
            'membership_rate' => $totalStudents > 0 ? round(($activeMembers / $totalStudents) * 100, 1) : 0,
            'total_events' => $activeYear
                ? Event::where('academic_year_id', $activeYear->id)->count()
                : 0,
            'open_incidents' => Incident::where('status', 'open')->count(),
            'todays_scans' => AttendanceRecord::whereDate('scanned_at', today())->count(),
            'upcoming_events' => Event::where('event_date', '>=', today())
                ->where('is_published', true)
                ->withCount('attendanceRecords')
                ->orderBy('event_date')
                ->take(5)
                ->get(),
            'program_breakdown' => $programBreakdown,
            'year_level_breakdown' => $yearLevelBreakdown,
            'recent_scans' => $recentScans,
        ];

        return view('admin.dashboard', compact('stats', 'activeYear'));
    }
}
