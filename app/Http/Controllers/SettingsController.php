<?php

namespace App\Http\Controllers;

use App\Models\UserNotificationPreference;
use App\Models\UserProfile;
use App\Services\SessionSecurityService;
use App\Services\SignatureVaultService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Display the dedicated Settings page with all sections:
     * 1. Account Information
     * 2. Profile Preferences & Visibility
     * 3. Security, Password & Active Sessions
     * 4. Notification Preferences
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $user->load(['student', 'profile', 'notificationPreferences']);

        $profile = $user->profile ?? UserProfile::firstOrCreate([
            'user_id' => $user->id,
        ], [
            'bio' => null,
            'interests' => [],
            'collaboration_preferences' => [],
            'is_public' => true,
        ]);

        $notificationPreferences = $user->notificationPreferences ?? UserNotificationPreference::firstOrCreate([
            'user_id' => $user->id,
        ], [
            'notify_expiration_7d' => true,
            'notify_expiration_1d' => true,
            'notify_redemption' => true,
            'notify_achievements' => true,
            'notify_events' => true,
        ]);

        $signatureStatus = app(SignatureVaultService::class)->signatureStatus($user);

        // Fetch recent active sessions for security overview
        $sessionService = app(SessionSecurityService::class);
        $activeSessions = $sessionService->getActiveSessions($user);

        return view('settings.index', compact(
            'user',
            'profile',
            'notificationPreferences',
            'signatureStatus',
            'activeSessions'
        ));
    }

    /**
     * Update account info & profile preferences.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'bio' => ['nullable', 'string', 'max:1000'],
            'visibility' => ['required', 'string', 'in:public,members_only,private'],
            'github_url' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
        ]);

        DB::transaction(function () use ($user, $validated) {
            $emailChanged = $user->email !== $validated['email'];
            $user->name = $validated['name'];
            $user->email = $validated['email'];

            if ($emailChanged) {
                $user->email_verified_at = null;
                if ($user->student) {
                    $user->student->update(['email' => $validated['email']]);
                }
            }
            $user->save();

            $isPublic = ($validated['visibility'] === 'public');
            $profile = UserProfile::firstOrCreate(['user_id' => $user->id]);
            $profile->update([
                'bio' => $validated['bio'] ?? null,
                'visibility' => $validated['visibility'],
                'is_public' => $isPublic,
                'github_url' => $validated['github_url'] ?? null,
                'linkedin_url' => $validated['linkedin_url'] ?? null,
            ]);
        });

        return redirect()->route('settings.index')
            ->with('status', 'Profile and account settings updated successfully.')
            ->with('success', 'Profile and account settings updated successfully.');
    }

    /**
     * Update notification preferences.
     */
    public function updateNotifications(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'notify_expiration_7d' => ['sometimes', 'boolean'],
            'notify_expiration_1d' => ['sometimes', 'boolean'],
            'notify_redemption' => ['sometimes', 'boolean'],
            'notify_achievements' => ['sometimes', 'boolean'],
            'notify_events' => ['sometimes', 'boolean'],
        ]);

        $prefs = UserNotificationPreference::firstOrCreate(['user_id' => $user->id]);
        $prefs->update([
            'notify_expiration_7d' => $request->boolean('notify_expiration_7d'),
            'notify_expiration_1d' => $request->boolean('notify_expiration_1d'),
            'notify_redemption' => $request->boolean('notify_redemption'),
            'notify_achievements' => $request->boolean('notify_achievements'),
            'notify_events' => $request->boolean('notify_events'),
        ]);

        return redirect()->route('settings.index')
            ->with('status', 'Notification preferences updated.')
            ->with('success', 'Notification preferences updated.');
    }

    /**
     * Update account password securely.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = $request->user();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('settings.index')
            ->with('status', 'password-updated')
            ->with('success', 'Password updated successfully.');
    }
}
