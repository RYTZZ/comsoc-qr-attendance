<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mission;
use App\Models\MissionSubmission;
use App\Services\AuditLogger;
use App\Services\RewardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminMissionController extends Controller
{
    public function __construct(private RewardService $rewardService) {}

    public function index(Request $request): View
    {
        $missions = Mission::withCount('submissions')
            ->orderByDesc('created_at')
            ->paginate(15);

        $pendingSubmissions = MissionSubmission::with(['mission', 'user'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('admin.missions.index', compact('missions', 'pendingSubmissions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'objectives' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'category' => ['required', 'string', 'max:50'],
            'xp_reward' => ['required', 'integer', 'min:0'],
            'points_reward' => ['required', 'integer', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'submission_type' => ['required', 'in:text,link,file'],
            'allow_resubmission' => ['nullable', 'boolean'],
        ]);

        $validated['allow_resubmission'] = $request->boolean('allow_resubmission', true);
        $validated['status'] = 'published';

        $mission = Mission::create($validated);
        AuditLogger::log('mission.created', $mission, [], $validated);

        return back()->with('success', "Mission '{$mission->title}' created.");
    }

    public function approveSubmission(MissionSubmission $submission): RedirectResponse
    {
        if ($submission->status === 'approved') {
            return back()->with('error', 'Submission is already approved.');
        }

        if ($submission->user_id === auth()->id() && !auth()->user()->isSuperAdmin()) {
            return back()->with('error', 'Conflict of interest: You cannot approve your own mission submission.');
        }

        $user = $submission->user;
        $mission = $submission->mission;

        $submission->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => 'Approved by ' . auth()->user()->name,
        ]);

        $this->rewardService->awardForMissionCompletion(
            user: $user,
            mission: $mission,
            submission: $submission,
            actor: auth()->user()
        );

        AuditLogger::log('mission_submission.approved', $submission, [], [
            'mission_id' => $mission->id,
            'user_id' => $user->id,
            'xp_reward' => $mission->xp_reward,
            'points_reward' => $mission->points_reward,
        ]);

        \App\Services\NotificationService::send(
            $user,
            'mission_approved',
            'Mission Completed: ' . $mission->title,
            'Your submission for ' . $mission->title . ' was approved! You earned +' . $mission->xp_reward . ' XP and +' . $mission->points_reward . ' Points.',
            route('missions.show', $mission)
        );

        return back()->with('success', "Submission for '{$mission->title}' approved and rewards granted to {$user->name}.");
    }

    public function rejectSubmission(Request $request, MissionSubmission $submission): RedirectResponse
    {
        $validated = $request->validate([
            'review_notes' => ['required', 'string', 'max:1000'],
        ]);

        $submission->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $validated['review_notes'],
        ]);

        AuditLogger::log('mission_submission.rejected', $submission, [], [
            'reason' => $validated['review_notes'],
        ]);

        \App\Services\NotificationService::send(
            $submission->user,
            'mission_rejected',
            'Mission Feedback: ' . $submission->mission->title,
            'Your submission for ' . $submission->mission->title . ' was not approved: ' . $validated['review_notes'],
            route('missions.show', $submission->mission)
        );

        return back()->with('success', 'Submission marked as rejected.');
    }
}
