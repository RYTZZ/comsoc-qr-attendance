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
use Illuminate\Support\Str;
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
        $seasonPoints = $user->getSpendableExp();
        $lifetimeXp = $user->getLifetimeExp();

        $currentLevel = Level::forXp($lifetimeXp);
        $nextLevel = Level::where('min_xp', '>', $lifetimeXp)->orderBy('min_xp')->first();
        $levelProgress = 100;
        if ($nextLevel) {
            $span = max(1, $nextLevel->min_xp - $currentLevel->min_xp);
            $levelProgress = min(100, max(0, round((($lifetimeXp - $currentLevel->min_xp) / $span) * 100)));
        }

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
        $avatarPresets = \App\Services\AvatarService::getPresets();
        $skillCategories = \App\Services\ProfileMetadataService::getSkillCategories();
        $interestCategories = \App\Services\ProfileMetadataService::getInterestCategories();
        $signatureStatus = app(\App\Services\SignatureVaultService::class)->signatureStatus($user);
        $profileCompletion = app(\App\Services\ProfileCompletionService::class)->evaluate($user);

        return view('profile.edit', compact(
            'user',
            'profile',
            'student',
            'signatureStatus',
            'profileCompletion',
            'activeSeason',
            'seasonXp',
            'seasonPoints',
            'lifetimeXp',
            'currentLevel',
            'nextLevel',
            'levelProgress',
            'attendanceHistory',
            'xpHistory',
            'pointHistory',
            'allSkills',
            'avatarPresets',
            'skillCategories',
            'interestCategories'
        ));
    }

    public function update(MemberProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $previousEmail = $user->email;
        $emailChanged = false;

        DB::transaction(function () use ($user, $validated, $request, &$emailChanged) {
            $user->name = $validated['name'];
            $emailChanged = strtolower(trim((string) $user->email)) !== strtolower(trim((string) $validated['email']));
            $user->email = strtolower(trim((string) $validated['email']));

            if ($emailChanged) {
                $user->email_verified_at = null;
                if ($user->student) {
                    $user->student->update(['email' => $user->email]);
                }
            }

            if (isset($validated['avatar_seed'])) {
                $user->avatar_style = $validated['avatar_style'] ?? 'adventurer';
                $user->avatar_seed = \App\Services\AvatarService::sanitizeSeed($validated['avatar_seed']);
            }

            if (isset($validated['username']) && trim($validated['username']) !== '') {
                $user->username = strtolower(trim($validated['username']));
            }

            $user->save();

            // Normalize and parse interests
            $rawInterests = $validated['interests'] ?? null;
            $interestsArray = \App\Services\ProfileMetadataService::normalizeList($rawInterests);

            // Determine visibility & backward compatibility with is_public
            $visibility = $validated['visibility'] ?? null;
            if (!$visibility) {
                $visibility = $request->has('is_public') 
                    ? ($request->boolean('is_public') ? 'public' : 'private')
                    : 'public';
            }
            $isPublic = ($visibility === 'public');

            $profile = UserProfile::firstOrCreate(['user_id' => $user->id]);
            $profile->update([
                'bio' => $validated['bio'] ?? null,
                'github_url' => $validated['github_url'] ?? null,
                'linkedin_url' => $validated['linkedin_url'] ?? null,
                'interests' => $interestsArray,
                'visibility' => $visibility,
                'is_public' => $isPublic,
            ]);

            $skillIds = [];
            $rawSkills = $validated['skills'] ?? [];
            $normalizedSkills = \App\Services\ProfileMetadataService::normalizeList($rawSkills);

            foreach ($normalizedSkills as $skillName) {
                $skill = Skill::firstOrCreate(['name' => $skillName]);
                $skillIds[$skill->id] = [
                    'id' => (string) Str::uuid(),
                    'proficiency' => 'intermediate',
                ];
            }
            $user->skills()->sync($skillIds);
        });

        if ($emailChanged) {
            app(\App\Services\EmailVerificationOtpService::class)->handleEmailAddressChanged(
                $user,
                $previousEmail,
                $request->ip()
            );
        }

        $rewardResult = app(\App\Services\ProfileCompletionService::class)
            ->evaluateAndAwardIfComplete($user, $request->user());

        $redirect = Redirect::route('profile.edit')->with('status', 'profile-updated');
        if (! empty($rewardResult['awarded'])) {
            $redirect->with('profile_completion_rewarded', [
                'title' => \App\Services\ProfileCompletionService::NOTIFICATION_TITLE,
                'message' => \App\Services\ProfileCompletionService::NOTIFICATION_MESSAGE,
                'exp_awarded' => $rewardResult['exp_awarded'],
                'points_awarded' => $rewardResult['points_awarded'],
                'reference_key' => $rewardResult['reference_key'] ?? null,
            ]);
        }

        return $redirect;
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
