<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AchievementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminAchievementController extends Controller
{
    public function index(): View
    {
        $achievements = Achievement::withCount('users')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $students = User::where('role', 'student')->orderBy('name')->take(100)->get();

        return view('admin.achievements.index', [
            'achievements' => $achievements,
            'students' => $students,
            'categories' => Achievement::CATEGORIES,
            'criteriaTypes' => Achievement::CRITERIA_TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'criteria_type' => ['required', 'string', 'max:100'],
            'criteria_threshold' => ['required', 'integer', 'min:1'],
            'xp_bonus' => ['required', 'integer', 'min:0'],
            'points_bonus' => ['required', 'integer', 'min:0'],
            'icon' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slug = Str::slug($validated['title']) . '-' . Str::random(4);

        $achievement = Achievement::create([
            'slug' => $slug,
            'title' => $validated['title'],
            'category' => $validated['category'],
            'criteria_type' => $validated['criteria_type'],
            'criteria_threshold' => $validated['criteria_threshold'],
            'xp_bonus' => $validated['xp_bonus'],
            'points_bonus' => $validated['points_bonus'],
            'icon' => $validated['icon'] ?: 'award',
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'created_achievement',
            'details' => json_encode([
                'achievement_id' => $achievement->id,
                'title' => $achievement->title,
                'criteria_type' => $achievement->criteria_type,
            ]),
        ]);

        return redirect()->route('admin.achievements.index')->with('success', 'Achievement created successfully.');
    }

    public function update(Request $request, Achievement $achievement): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'criteria_type' => ['required', 'string', 'max:100'],
            'criteria_threshold' => ['required', 'integer', 'min:1'],
            'xp_bonus' => ['required', 'integer', 'min:0'],
            'points_bonus' => ['required', 'integer', 'min:0'],
            'icon' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $achievement->update([
            'title' => $validated['title'],
            'category' => $validated['category'],
            'criteria_type' => $validated['criteria_type'],
            'criteria_threshold' => $validated['criteria_threshold'],
            'xp_bonus' => $validated['xp_bonus'],
            'points_bonus' => $validated['points_bonus'],
            'icon' => $validated['icon'] ?: 'award',
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'updated_achievement',
            'details' => json_encode([
                'achievement_id' => $achievement->id,
                'title' => $achievement->title,
            ]),
        ]);

        return redirect()->route('admin.achievements.index')->with('success', 'Achievement updated successfully.');
    }

    public function awardMember(
        Request $request,
        Achievement $achievement,
        AchievementService $achievementService
    ): RedirectResponse {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $targetUser = User::findOrFail($validated['user_id']);

        $awarded = $achievementService->award(
            user: $targetUser,
            achievement: $achievement,
            actor: $request->user()
        );

        if (!$awarded) {
            return back()->with('error', 'Member already unlocked this achievement.');
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'manually_awarded_achievement',
            'details' => json_encode([
                'achievement_id' => $achievement->id,
                'target_user_id' => $targetUser->id,
            ]),
        ]);

        return redirect()->route('admin.achievements.index')->with('success', "Achievement '{$achievement->title}' awarded to {$targetUser->name}!");
    }
}
