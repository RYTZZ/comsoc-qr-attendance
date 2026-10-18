<?php

namespace App\Http\Controllers;

use App\Http\Requests\MemberProfileUpdateRequest;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\Level;
use App\Models\MemberSeasonStat;
use App\Models\PointTransaction;
use App\Models\Season;
use App\Models\Skill;
use App\Models\UserProfile;
use App\Models\XpTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $user->load(['student', 'profile', 'skills', 'achievements']);

        $profile = $user->profile ?? UserProfile::firstOrCreate([
            'user_id' => $user->id,
        ], [
            'bio' => null,
            'interests' => [],
            'collaboration_preferences' => [],
            'is_public' => true,
        ]);

        $activeSeason = Season::active();
        $seasonStat = null;
        if ($activeSeason) {
            $seasonStat = MemberSeasonStat::where('user_id', $user->id)
                ->where('season_id', $activeSeason->id)
                ->first();
        }

        $seasonXp = $seasonStat ? $seasonStat->season_xp : 0;
        $seasonPoints = $seasonStat ? $seasonStat->redeemable_points : 0;
        $lifetimeXp = (int) XpTransaction::where('user_id', $user->id)->sum('amount');
        if ($lifetimeXp < $seasonXp) {
            $lifetimeXp = $seasonXp;
        }

        $currentLevel = Level::forXp($seasonXp);

        $student = $user->student;
        $attendanceHistory = collect();
        if ($student) {
            $attendanceHistory = AttendanceRecord::where('participant_id', $student->id)
                ->with(['event', 'attendanceSession'])
                ->orderByDesc('scanned_at')
                ->take(10)
                ->get();
        }

        $xpHistory = XpTransaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        $pointHistory = PointTransaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        $allSkills = Skill::orderBy('name')->get();

        return view('profile.edit', compact(
            'user',
            'profile',
            'student',
            'activeSeason',
            'seasonXp',
            'seasonPoints',
            'lifetimeXp',
            'currentLevel',
            'attendanceHistory',
            'xpHistory',
            'pointHistory',
            'allSkills'
        ));
    }

    public function update(MemberProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        DB::transaction(function () use ($user, $validated) {
            $user->name = $validated['name'];
            $emailChanged = $user->email !== $validated['email'];
            $user->email = $validated['email'];

            if ($emailChanged) {
                $user->email_verified_at = null;
                if ($user->student) {
                    $user->student->update(['email' => $validated['email']]);
                }
            }
            $user->save();

            $interestsArray = [];
            if (!empty($validated['interests'])) {
                $interestsArray = array_values(array_filter(array_map('trim', explode(',', $validated['interests']))));
            }

            $profile = UserProfile::firstOrCreate(['user_id' => $user->id]);
            $profile->update([
                'bio' => $validated['bio'] ?? null,
                'github_url' => $validated['github_url'] ?? null,
                'linkedin_url' => $validated['linkedin_url'] ?? null,
                'interests' => $interestsArray,
                'is_public' => $validated['is_public'] ?? true,
            ]);

            if (isset($validated['skills']) && is_array($validated['skills'])) {
                $skillIds = [];
                foreach ($validated['skills'] as $skillName) {
                    $cleanedName = trim($skillName);
                    if ($cleanedName !== '') {
                        $skill = Skill::firstOrCreate(['name' => $cleanedName]);
                        $skillIds[$skill->id] = ['proficiency' => 'intermediate'];
                    }
                }
                $user->skills()->sync($skillIds);
            }
        });

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($user->role === 'super_admin') {
            $otherSuperAdmins = \App\Models\User::where('role', 'super_admin')
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->count();

            if ($otherSuperAdmins === 0) {
                return back()->withErrors(['password' => 'Cannot delete the only active Super Admin account.']);
            }
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
