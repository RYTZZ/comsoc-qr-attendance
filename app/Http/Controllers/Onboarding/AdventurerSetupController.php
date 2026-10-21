<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\UsernameRule;
use App\Services\AuditLogger;
use App\Services\AvatarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdventurerSetupController extends Controller
{
    /**
     * Display the dedicated Adventurer Avatar and Username setup page.
     */
    public function show(Request $request): View
    {
        $user = $request->user();
        $avatarPresets = AvatarService::getPresets();
        $currentSeed = $user->avatar_seed ?: AvatarService::DEFAULT_SEED;
        $currentUsername = $user->username ?: '';

        return view('auth.adventurer-setup', [
            'user' => $user,
            'avatarPresets' => $avatarPresets,
            'currentSeed' => $currentSeed,
            'currentUsername' => $currentUsername,
            'presetSeeds' => array_keys(AvatarService::PRESET_SEEDS),
        ]);
    }

    /**
     * Save the chosen Adventurer Avatar and unique Username.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'username' => ['required', 'string', new UsernameRule($user->id)],
            'avatar_seed' => ['required', 'string', 'in:' . implode(',', array_keys(AvatarService::PRESET_SEEDS))],
            'avatar_style' => ['nullable', 'string', 'in:adventurer'],
        ], [
            'username.required' => 'Please enter a username to represent your adventurer identity.',
            'avatar_seed.required' => 'Please select an adventurer avatar.',
            'avatar_seed.in' => 'The selected avatar is not part of the official adventurer collection.',
        ]);

        DB::transaction(function () use ($user, $validated, $request) {
            $user->username = strtolower(trim($validated['username']));
            $user->avatar_seed = AvatarService::sanitizeSeed($validated['avatar_seed']);
            $user->avatar_style = 'adventurer';
            $user->adventurer_setup_at = now();
            $user->save();

            AuditLogger::log('adventurer.identity_updated', $user, [], [
                'username' => $user->username,
                'avatar_seed' => $user->avatar_seed,
                'ip' => $request->ip(),
            ]);
        });

        return redirect()->route('dashboard')
            ->with('status', 'Your adventurer identity has been successfully created! Welcome to the COMSOC HUB community.')
            ->with('success', 'Your adventurer identity has been successfully created! Welcome to the COMSOC HUB community.');
    }

    /**
     * Check username availability in real-time.
     */
    public function checkUsername(Request $request): JsonResponse
    {
        $username = trim((string) $request->query('username', ''));
        $user = $request->user();

        if ($username === '') {
            return response()->json([
                'available' => false,
                'message' => 'Please enter a username.',
            ]);
        }

        if (strlen($username) < 3 || strlen($username) > 20) {
            return response()->json([
                'available' => false,
                'message' => 'Username must be between 3 and 20 characters.',
            ]);
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return response()->json([
                'available' => false,
                'message' => 'Letters, numbers, and underscores only.',
            ]);
        }

        $lower = strtolower($username);

        if (in_array($lower, UsernameRule::RESERVED_USERNAMES, true)) {
            return response()->json([
                'available' => false,
                'message' => 'This username is reserved for system roles.',
            ]);
        }

        $exists = User::whereRaw('LOWER(username) = ?', [$lower])
            ->when($user, fn($q) => $q->where('id', '!=', $user->id))
            ->exists();

        if ($exists) {
            return response()->json([
                'available' => false,
                'message' => 'This username is already taken.',
            ]);
        }

        return response()->json([
            'available' => true,
            'message' => 'Username is available!',
        ]);
    }
}
