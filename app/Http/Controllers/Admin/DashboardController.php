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

        $totalEventRegistrations = \App\Models\EventRegistration::count();
        $totalAttendanceScans = AttendanceRecord::count();

        $totalMissions = \App\Models\Mission::count();
        $approvedSubmissions = \App\Models\MissionSubmission::where('status', 'approved')->count();
        $pendingSubmissions = \App\Models\MissionSubmission::where('status', 'pending')->count();
        $totalSubmissions = \App\Models\MissionSubmission::count();
        $missionCompletionRate = $totalSubmissions > 0 ? round(($approvedSubmissions / $totalSubmissions) * 100, 1) : 0;

        $totalXpDistributed = (int) \App\Models\XpTransaction::sum('amount');
        $totalPointsDistributed = (int) \App\Models\PointTransaction::where('amount', '>', 0)->sum('amount');
        $totalPointsSpent = abs((int) \App\Models\PointTransaction::where('amount', '<', 0)->sum('amount'));

        $pendingRedemptions = \App\Models\RewardRedemption::where('status', 'pending')->count();
        $fulfilledRedemptions = \App\Models\RewardRedemption::where('status', 'fulfilled')->count();
        $availableInventory = (int) \App\Models\Reward::where('is_active', true)->sum('stock');

        $activeJobOpportunities = \App\Models\JobListing::where('is_active', true)->count();
        $activeAnnouncements = \App\Models\Announcement::active()->count();
        $openSupportTickets = \App\Models\SupportTicket::whereIn('status', ['open', 'in_review', 'awaiting_member'])->count();
        $activeProjects = \App\Models\CollaborationProject::whereIn('status', ['recruiting', 'in_progress'])->count();
        $pendingVolunteers = \App\Models\VolunteerApplication::where('status', 'applied')->count();

        $recentAuditLogs = \App\Models\AuditLog::with('user')->latest()->take(5)->get();

        return [
            'total_students' => $totalStudents,
            'active_members' => $activeMembers,
            'membership_rate' => $totalStudents > 0 ? round(($activeMembers / $totalStudents) * 100, 1) : 0,
            'total_events' => $activeYear
                ? Event::where('academic_year_id', $activeYear->id)->count()
                : 0,
            'open_incidents' => Incident::where('status', 'open')->count(),
            'open_support_tickets' => $openSupportTickets,
            'active_projects' => $activeProjects,
            'pending_volunteers' => $pendingVolunteers,
            'todays_scans' => AttendanceRecord::whereDate('scanned_at', today())->count(),
            'total_event_registrations' => $totalEventRegistrations,
            'total_attendance_scans' => $totalAttendanceScans,
            'total_missions' => $totalMissions,
            'pending_submissions' => $pendingSubmissions,
            'approved_submissions' => $approvedSubmissions,
            'mission_completion_rate' => $missionCompletionRate,
            'total_xp_distributed' => $totalXpDistributed,
            'total_points_distributed' => $totalPointsDistributed,
            'total_points_spent' => $totalPointsSpent,
            'pending_redemptions' => $pendingRedemptions,
            'fulfilled_redemptions' => $fulfilledRedemptions,
            'available_inventory' => $availableInventory,
            'active_job_opportunities' => $activeJobOpportunities,
            'active_announcements' => $activeAnnouncements,
            'recent_audit_logs' => $recentAuditLogs,
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
