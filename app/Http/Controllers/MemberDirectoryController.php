<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\MemberDirectoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MemberDirectoryController extends Controller
{
    public const PROJECT_CATEGORIES = [
        'Web Development',
        'Mobile Apps',
        'Artificial Intelligence & ML',
        'Cybersecurity & Networking',
        'Game Development',
        'UI/UX Design',
        'Embedded Systems & IoT',
        'Data Science & Analytics',
    ];

    public function index(Request $request, MemberDirectoryService $directoryService): View
    {
        $user = $request->user();
        $tab = $request->input('tab', 'directory');
        $search = $request->input('search');
        $skillId = $request->input('skill_id');
        $projectCategory = $request->input('project_category');
        $programCode = $request->input('program');
        $yearLevel = $request->input('year_level');

        $skills = Skill::orderBy('name')->get();
        $programs = Program::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();

        $members = $directoryService->searchDirectory(
            search: $search,
            skillId: $skillId,
            projectCategory: $projectCategory,
            program: $programCode,
            yearLevel: $yearLevel,
            perPage: 12,
            page: (int) $request->input('page', 1)
        );

        $matches = collect();
        if ($tab === 'matches') {
            $matches = $directoryService->findCollaboratorMatches($user, limit: 12);
        }

        return view('directory.index', [
            'tab' => $tab,
            'members' => $members,
            'matches' => $matches,
            'skills' => $skills,
            'programs' => $programs,
            'yearLevels' => \App\Models\Student::YEAR_LEVELS,
            'projectCategories' => self::PROJECT_CATEGORIES,
            'search' => $search,
            'selectedSkill' => $skillId,
            'selectedCategory' => $projectCategory,
            'selectedProgram' => $programCode,
            'selectedYearLevel' => $yearLevel,
        ]);
    }

    public function show(Request $request, User $user): View
    {
        if (!in_array($user->role, ['student', 'member']) || !$user->profile?->is_public) {
            abort(404);
        }

        $user->load(['student', 'skills', 'achievements', 'seasonStats.season']);

        return view('directory.show', [
            'member' => $user,
        ]);
    }

    public function editProfile(Request $request): View
    {
        $user = $request->user();
        $profile = $user->profile ?? UserProfile::firstOrCreate(['user_id' => $user->id]);
        $allSkills = Skill::orderBy('name')->get();
        $userSkillIds = $user->skills->pluck('id')->all();

        return view('directory.edit-profile', [
            'user' => $user,
            'profile' => $profile,
            'allSkills' => $allSkills,
            'userSkillIds' => $userSkillIds,
            'projectCategories' => self::PROJECT_CATEGORIES,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'bio' => ['nullable', 'string', 'max:1000'],
            'github_url' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'interests' => ['nullable', 'string', 'max:500'],
            'tech_stack' => ['nullable', 'string', 'max:500'],
            'collaboration_preferences' => ['nullable', 'string', 'max:500'],
            'project_categories' => ['nullable', 'array'],
            'project_categories.*' => ['string'],
            'skill_ids' => ['nullable', 'array'],
            'skill_ids.*' => ['exists:skills,id'],
            'is_public' => ['nullable', 'boolean'],
            'open_to_collaborate' => ['nullable', 'boolean'],
        ]);

        $interestsArray = !empty($validated['interests'])
            ? array_values(array_filter(array_map('trim', explode(',', $validated['interests']))))
            : [];

        $techStackArray = !empty($validated['tech_stack'])
            ? array_values(array_filter(array_map('trim', explode(',', $validated['tech_stack']))))
            : [];

        $collabArray = !empty($validated['collaboration_preferences'])
            ? array_values(array_filter(array_map('trim', explode(',', $validated['collaboration_preferences']))))
            : [];

        $profile = UserProfile::firstOrCreate(['user_id' => $user->id]);
        $profile->update([
            'bio' => $validated['bio'] ?? null,
            'github_url' => $validated['github_url'] ?? null,
            'linkedin_url' => $validated['linkedin_url'] ?? null,
            'interests' => $interestsArray,
            'tech_stack' => $techStackArray,
            'collaboration_preferences' => $collabArray,
            'project_categories' => $validated['project_categories'] ?? [],
            'is_public' => $request->boolean('is_public'),
            'open_to_collaborate' => $request->boolean('open_to_collaborate'),
        ]);

        if (!empty($validated['skill_ids']) && is_array($validated['skill_ids'])) {
            $syncData = [];
            foreach ($validated['skill_ids'] as $skillId) {
                $syncData[$skillId] = [
                    'id' => (string) Str::uuid(),
                    'proficiency' => 'intermediate',
                ];
            }
            $user->skills()->sync($syncData);
        } else {
            $user->skills()->detach();
        }

        return redirect()->route('directory.profile.edit')->with('success', 'Your collaborator profile and skills have been updated successfully!');
    }
}
