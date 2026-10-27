<?php

namespace App\Http\Controllers;

use App\Models\CollaborationProject;
use App\Models\ProjectApplication;
use App\Models\ProjectMember;
use App\Models\ProjectMilestone;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProjectCollaborationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $category = $request->input('category', 'all');
        $stage = $request->input('stage', 'all');
        $search = $request->input('search');
        $tab = $request->input('tab', 'discover'); // discover, my_projects, applied

        $query = CollaborationProject::with(['owner.student', 'activeMembers'])
            ->withCount('activeMembers');

        if ($tab === 'my_projects') {
            $query->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                  ->orWhereHas('activeMembers', fn($m) => $m->where('user_id', $user->id));
            });
        } elseif ($tab === 'applied') {
            $query->whereHas('applications', fn($a) => $a->where('user_id', $user->id));
        } else {
            // Discover: only recruiting or in_progress, not moderated
            $query->whereIn('status', ['recruiting', 'in_progress']);
        }

        if ($category !== 'all' && !empty($category)) {
            $query->where('category', $category);
        }

        if ($stage !== 'all' && !empty($stage)) {
            $query->where('stage', $stage);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('problem_statement', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $projects = $query->latest('created_at')->paginate(12)->withQueryString();

        return view('projects.index', compact('projects', 'category', 'stage', 'search', 'tab'));
    }

    public function create(): View
    {
        return view('projects.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'problem_statement' => ['required', 'string', 'min:10', 'max:1000'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'category' => ['required', 'string', 'in:' . implode(',', array_keys(CollaborationProject::CATEGORIES))],
            'skills_needed' => ['nullable', 'string', 'max:500'],
            'preferred_roles' => ['nullable', 'string', 'max:500'],
            'team_size_target' => ['required', 'integer', 'min:2', 'max:15'],
            'stage' => ['required', 'string', 'in:' . implode(',', array_keys(CollaborationProject::STAGES))],
            'expected_commitment' => ['required', 'string', 'max:100'],
            'repository_url' => ['nullable', 'url', 'max:500'],
            'demo_url' => ['nullable', 'url', 'max:500'],
            'application_deadline' => ['nullable', 'date', 'after:now'],
        ]);

        // Clean skills and roles as array
        $skillsArray = !empty($validated['skills_needed'])
            ? array_filter(array_map('trim', explode(',', $validated['skills_needed'])))
            : [];
        $rolesArray = !empty($validated['preferred_roles'])
            ? array_filter(array_map('trim', explode(',', $validated['preferred_roles'])))
            : [];

        $project = DB::transaction(function () use ($user, $validated, $skillsArray, $rolesArray) {
            $proj = CollaborationProject::create([
                'owner_id' => $user->id,
                'title' => $validated['title'],
                'problem_statement' => $validated['problem_statement'],
                'description' => $validated['description'],
                'category' => $validated['category'],
                'skills_needed' => $skillsArray,
                'preferred_roles' => $rolesArray,
                'team_size_target' => $validated['team_size_target'],
                'stage' => $validated['stage'],
                'expected_commitment' => $validated['expected_commitment'],
                'repository_url' => $validated['repository_url'] ?? null,
                'demo_url' => $validated['demo_url'] ?? null,
                'application_deadline' => $validated['application_deadline'] ?? null,
                'status' => 'recruiting',
                'is_recruiting' => true,
            ]);

            // Add owner as Lead in project_members
            ProjectMember::create([
                'project_id' => $proj->id,
                'user_id' => $user->id,
                'role' => 'Project Lead / Founder',
                'is_active' => true,
            ]);

            return $proj;
        });

        return redirect()->route('projects.show', $project)->with('status', 'Project listing published successfully.');
    }

    public function show(Request $request, CollaborationProject $project): View
    {
        $user = $request->user();
        $project->load(['owner.student', 'activeMembers.user.student', 'milestones']);

        $myApplication = ProjectApplication::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->first();

        $isOwner = $project->owner_id === $user->id;
        $isMember = $project->isMember($user);

        $pendingApplications = collect();
        if ($isOwner || $user->isAdmin()) {
            $pendingApplications = $project->applications()
                ->with('user.student')
                ->where('status', 'pending')
                ->latest()
                ->get();
        }

        return view('projects.show', compact('project', 'myApplication', 'isOwner', 'isMember', 'pendingApplications'));
    }

    public function apply(Request $request, CollaborationProject $project): RedirectResponse
    {
        $user = $request->user();

        if ($project->owner_id === $user->id || $project->isMember($user)) {
            return back()->withErrors(['message' => 'You are already a member or owner of this project.']);
        }

        if (!$project->is_recruiting || $project->status !== 'recruiting') {
            return back()->withErrors(['message' => 'This project is currently not accepting applications.']);
        }

        // Enforce team capacity
        if ($project->activeMembers()->count() >= $project->team_size_target) {
            return back()->withErrors(['message' => 'Team capacity is full.']);
        }

        $validated = $request->validate([
            'preferred_role' => ['required', 'string', 'max:80'],
            'motivation' => ['required', 'string', 'min:10', 'max:1000'],
            'skills_summary' => ['nullable', 'string', 'max:1000'],
        ]);

        // Prevent duplicate application
        $existing = ProjectApplication::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing && $existing->status !== 'withdrawn') {
            return back()->withErrors(['message' => 'You have already applied to this project.']);
        }

        DB::transaction(function () use ($project, $user, $validated, $existing) {
            if ($existing) {
                $existing->update([
                    'preferred_role' => $validated['preferred_role'],
                    'motivation' => $validated['motivation'],
                    'skills_summary' => $validated['skills_summary'] ?? null,
                    'status' => 'pending',
                ]);
            } else {
                ProjectApplication::create([
                    'project_id' => $project->id,
                    'user_id' => $user->id,
                    'preferred_role' => $validated['preferred_role'],
                    'motivation' => $validated['motivation'],
                    'skills_summary' => $validated['skills_summary'] ?? null,
                    'status' => 'pending',
                ]);
            }

            // Notify owner
            NotificationService::send(
                $project->owner_id,
                'project_application',
                'New Project Collaborator Application',
                "{$user->name} applied to join '{$project->title}' as {$validated['preferred_role']}.",
                route('projects.show', $project)
            );
        });

        return back()->with('status', 'Your application to join the project team has been submitted.');
    }

    public function decideApplication(Request $request, ProjectApplication $application): RedirectResponse
    {
        $user = $request->user();
        $project = $application->project;

        if ($project->owner_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $action = $request->input('action'); // accept, decline
        $feedback = $request->input('feedback');

        if (!in_array($action, ['accept', 'decline'])) {
            return back()->withErrors(['message' => 'Invalid decision action.']);
        }

        try {
            DB::transaction(function () use ($application, $project, $user, $action, $feedback) {
                if ($action === 'accept') {
                    if ($project->activeMembers()->count() >= $project->team_size_target) {
                        throw new \Exception('Team target capacity has already been reached.');
                    }

                    $application->update([
                        'status' => 'accepted',
                        'feedback' => $feedback,
                        'decided_at' => now(),
                        'decided_by' => $user->id,
                    ]);

                    ProjectMember::firstOrCreate(
                        [
                            'project_id' => $project->id,
                            'user_id' => $application->user_id,
                        ],
                        [
                            'role' => $application->preferred_role,
                            'is_active' => true,
                        ]
                    );

                    NotificationService::send(
                        $application->user_id,
                        'project_accepted',
                        'Application Accepted!',
                        "Congratulations! You were accepted into project '{$project->title}' as {$application->preferred_role}.",
                        route('projects.show', $project)
                    );
                } else {
                    $application->update([
                        'status' => 'declined',
                        'feedback' => $feedback,
                        'decided_at' => now(),
                        'decided_by' => $user->id,
                    ]);

                    NotificationService::send(
                        $application->user_id,
                        'project_declined',
                        'Application Update',
                        "Your application to join '{$project->title}' was reviewed: {$feedback}",
                        route('projects.show', $project)
                    );
                }
            });
        } catch (\Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }

        return back()->with('status', "Application {$action}ed successfully.");
    }

    public function addMilestone(Request $request, CollaborationProject $project): RedirectResponse
    {
        $user = $request->user();
        if ($project->owner_id !== $user->id && !$user->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'due_date' => ['nullable', 'date'],
        ]);

        ProjectMilestone::create([
            'project_id' => $project->id,
            'title' => $validated['title'],
            'due_date' => $validated['due_date'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('status', 'Milestone added.');
    }

    public function destroy(Request $request, CollaborationProject $project): RedirectResponse
    {
        $user = $request->user();

        // Must be owner or have admin privileges
        $isOwner = $project->owner_id === $user->id;
        $isPrivileged = $user->isAdmin() || $user->isSuperAdmin() || $user->isComsocAdmin();

        if (!$isOwner && !$isPrivileged) {
            abort(403, 'You are not authorized to delete this project collaboration post.');
        }

        DB::transaction(function () use ($project) {
            $project->applications()->delete();
            $project->members()->delete();
            $project->milestones()->delete();
            $project->delete();
        });

        return redirect()->route('projects.index')->with('status', 'Project collaboration post deleted successfully.');
    }
}
