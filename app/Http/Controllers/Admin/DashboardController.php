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
        $stats = $this->getDashboardStats($activeYear);

        return view('admin.dashboard', compact('stats', 'activeYear'));
    }

    private function getDashboardStats(?AcademicYear $activeYear): array
    {
        $totalStudents = Student::count();
        $activeMembers = $activeYear
            ? Membership::where('academic_year_id', $activeYear->id)->where('status', 'active')->count()
            : 0;

        return [
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
            'program_breakdown' => $this->getProgramBreakdown(),
            'year_level_breakdown' => $this->getYearLevelBreakdown(),
            'recent_scans' => $this->getRecentScans(),
        ];
    }

    private function getProgramBreakdown()
    {
        return Student::all()
            ->groupBy(fn($s) => $s->program ?: 'BSIT')
            ->map(fn($group, $key) => (object)['prog' => $key, 'count' => $group->count()])
            ->sortByDesc('count')
            ->values();
    }

    private function getYearLevelBreakdown()
    {
        return Student::whereNotNull('year_level')
            ->get()
            ->groupBy('year_level')
            ->map(fn($group, $key) => (object)['year_level' => $key, 'count' => $group->count()])
            ->sortBy('year_level')
            ->values();
    }

    private function getRecentScans()
    {
        return AttendanceRecord::with(['student', 'event', 'attendanceSession'])
            ->latest('scanned_at')
            ->take(6)
            ->get();
    }
}
