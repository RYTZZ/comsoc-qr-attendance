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

        $stats = [
            'total_students' => Student::count(),
            'active_members' => $activeYear
                ? Membership::where('academic_year_id', $activeYear->id)->where('status', 'active')->count()
                : 0,
            'total_events' => $activeYear
                ? Event::where('academic_year_id', $activeYear->id)->count()
                : 0,
            'open_incidents' => Incident::where('status', 'open')->count(),
            'todays_scans' => AttendanceRecord::whereDate('scanned_at', today())->count(),
            'upcoming_events' => Event::where('event_date', '>=', today())
                ->where('is_published', true)
                ->orderBy('event_date')
                ->take(5)
                ->get(),
        ];

        return view('admin.dashboard', compact('stats', 'activeYear'));
    }
}
