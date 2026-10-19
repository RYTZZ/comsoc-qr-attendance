<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\AttendanceRecord;
use App\Models\CollaborationProject;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Level;
use App\Models\MemberSeasonStat;
use App\Models\Mission;
use App\Models\PointTransaction;
use App\Models\Reward;
use App\Models\Season;
use App\Models\SupportTicket;
use App\Models\VolunteerApplication;
use App\Models\VolunteerOpportunity;
use App\Models\XpTransaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $student = $user->student;
        $activeSeason = Season::active();

        $seasonStat = null;
        if ($activeSeason) {
            $seasonStat = MemberSeasonStat::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'season_id' => $activeSeason->id,
                ],
                [
                    'season_xp' => 0,
                    'redeemable_points' => 0,
                    'current_level' => 1,
                ]
            );
        }

        $spendableExp = $user->getSpendableExp();
        $lifetimeExp = $user->getLifetimeExp();

        $currentLevel = Level::forXp($lifetimeExp);
        $nextLevel = Level::where('min_xp', '>', $lifetimeExp)->orderBy('min_xp')->first();

        $levelProgress = 100;
        if ($nextLevel) {
            $xpFloor = $currentLevel->min_xp;
            $xpCeiling = $nextLevel->min_xp;
            $span = max(1, $xpCeiling - $xpFloor);
            $levelProgress = min(100, max(0, round((($lifetimeExp - $xpFloor) / $span) * 100)));
        }

        // Section B: Upcoming events with registration & capacity data
        $upcomingEvents = Event::where('event_date', '>=', today())
            ->where('is_published', true)
            ->with(['attendanceSessions', 'evaluationTemplate'])
            ->withCount('eventRegistrations')
            ->orderBy('event_date')
            ->take(4)
            ->get();

        $myEventRegistrationIds = EventRegistration::where('user_id', $user->id)
            ->pluck('status', 'event_id')
            ->all();

        $myEvaluations = \App\Models\EventEvaluation::where('user_id', $user->id)
            ->pluck('submitted_at', 'event_id')
            ->all();

        // Section C: EXP and Recent Activity
        $recentXp = XpTransaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        $recentPoints = PointTransaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        // Section D: Rewards and Achievements
        $featuredRewards = Reward::published()
            ->where(function ($q) {
                $q->whereNull('stock')->orWhere('stock', '>', 0);
            })
            ->orderBy('points_cost')
            ->take(3)
            ->get();

        $userAchievementsCount = $user->achievements()->count();
        $totalAchievements = Achievement::count();
        $recentAchievements = $user->achievements()->latest('pivot_unlocked_at')->take(2)->get();
        $activeMissions = Mission::where('status', 'published')
            ->orderBy('due_date')
            ->take(3)
            ->get();

        // Section E: Announcements and Notifications
        $allActiveAnnouncements = \App\Models\Announcement::active()
            ->latest('published_at')
            ->take(10)
            ->get();

        $filteredAnnouncements = $allActiveAnnouncements->filter(fn($ann) => $ann->isAudienceEligible($user))->values();
        $readReceipts = \App\Models\AnnouncementReceipt::where('user_id', $user->id)
            ->pluck('read_at', 'announcement_id')
            ->all();

        $unreadAnnouncementsCount = $filteredAnnouncements->reject(fn($ann) => isset($readReceipts[$ann->id]))->count();

        // Active support ticket updates for user
        $mySupportTickets = SupportTicket::where('user_id', $user->id)
            ->latest('updated_at')
            ->take(3)
            ->get();
        $openTicketsCount = SupportTicket::where('user_id', $user->id)
            ->whereIn('status', ['open', 'in_review', 'awaiting_member'])
            ->count();

        // Section F: Personal Activity Summary
        $verifiedAttendanceCount = 0;
        $recentAttendance = collect();
        if ($student) {
            $verifiedAttendanceCount = AttendanceRecord::where('participant_id', $student->id)->count();
            $recentAttendance = AttendanceRecord::where('participant_id', $student->id)
                ->with(['event', 'attendanceSession'])
                ->orderByDesc('scanned_at')
                ->take(5)
                ->get();
        }

        $approvedVolunteerHours = (float) VolunteerApplication::where('user_id', $user->id)
            ->where('status', 'completed')
            ->sum('approved_service_hours');

        $activeVolunteerOpportunities = VolunteerOpportunity::where('status', 'open')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('application_deadline')->orWhere('application_deadline', '>=', now());
            })
            ->latest('starts_at')
            ->take(3)
            ->get();

        $activeProjects = CollaborationProject::where('status', 'recruiting')
            ->where('is_recruiting', true)
            ->latest('created_at')
            ->take(3)
            ->get();

        $myActiveProjectsCount = \App\Models\ProjectMember::where('user_id', $user->id)
            ->where('is_active', true)
            ->count() + CollaborationProject::where('owner_id', $user->id)->whereIn('status', ['recruiting', 'in_progress'])->count();

        return view('dashboard', compact(
            'user',
            'student',
            'activeSeason',
            'seasonStat',
            'spendableExp',
            'lifetimeExp',
            'currentLevel',
            'nextLevel',
            'levelProgress',
            'upcomingEvents',
            'myEventRegistrationIds',
            'myEvaluations',
            'recentAttendance',
            'verifiedAttendanceCount',
            'activeMissions',
            'recentXp',
            'recentPoints',
            'featuredRewards',
            'userAchievementsCount',
            'totalAchievements',
            'recentAchievements',
            'filteredAnnouncements',
            'readReceipts',
            'unreadAnnouncementsCount',
            'mySupportTickets',
            'openTicketsCount',
            'approvedVolunteerHours',
            'activeVolunteerOpportunities',
            'activeProjects',
            'myActiveProjectsCount'
        ));
    }
}
