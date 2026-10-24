<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StudentRegistrationRequest;
use App\Models\AcademicYear;
use App\Models\Membership;
use App\Models\MemberSeasonStat;
use App\Models\Season;
use App\Models\Student;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StudentRegisterController extends Controller
{
    public function create(): View
    {
        $programs = Student::ALLOWED_PROGRAMS;
        $years = Student::ALLOWED_YEARS;
        $blocks = Student::BLOCKS;
        $avatarPresets = \App\Services\AvatarService::getPresets();

        return view('auth.signup', compact('programs', 'years', 'blocks', 'avatarPresets'));
    }

    public function store(StudentRegistrationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated, $request) {
            $formattedYear = $validated['year'] . ' Year';

            $email = strtolower(trim($validated['email']));
            $isInstitutional = str_ends_with($email, '@sorsu.edu.ph');

            $student = Student::create([
                'student_number' => trim($validated['student_id']),
                'first_name' => trim($validated['first_name']),
                'last_name' => trim($validated['last_name']),
                'middle_name' => !empty($validated['middle_name']) ? trim($validated['middle_name']) : null,
                'email' => $email,
                'program' => $validated['program'],
                'year_level' => $formattedYear,
                'block' => $validated['block'],
                'is_registrar_imported' => false,
            ]);

            $fullName = trim("{$student->first_name} {$student->last_name}");
            $username = strtolower(trim($validated['username']));

            $avatarSeed = \App\Services\AvatarService::sanitizeSeed($validated['avatar_seed']);
            $avatarStyle = $validated['avatar_style'] ?? 'adventurer';

            $user = User::create([
                'member_id' => User::generateUniqueMemberId(),
                'name' => $fullName,
                'username' => $username,
                'email' => $email,
                'password' => Hash::make($validated['password']),
                'role' => 'student', // Never grant officer privileges during self-registration
                'avatar_style' => $avatarStyle,
                'avatar_seed' => $avatarSeed,
                'adventurer_setup_at' => now(),
                'student_id' => $student->id,
                'student_number' => $student->student_number,
                'is_active' => true,
                'is_eligible' => true,
                'is_activated' => true,
                'is_registrar_imported' => false,
                'identity_verified_at' => $isInstitutional ? now() : null,
                'identity_verification_method' => $isInstitutional ? 'institutional_email' : 'pending_student_id_check',
                'must_change_password' => false,
            ]);

            UserProfile::create([
                'user_id' => $user->id,
                'bio' => null,
                'interests' => [],
                'collaboration_preferences' => [],
                'is_public' => true,
            ]);

            $activeSeason = Season::active();
            if ($activeSeason) {
                MemberSeasonStat::firstOrCreate(
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

            $activeAcademicYear = AcademicYear::active();
            if ($activeAcademicYear) {
                Membership::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'academic_year_id' => $activeAcademicYear->id,
                    ],
                    [
                        'status' => 'inactive',
                        'fee_paid' => null,
                    ]
                );
            }

            $vault = app(\App\Services\SignatureVaultService::class);
            $vault->store($user, $validated['signature'], false);

            AuditLogger::log('signature.registered', $user, [], ['source' => 'signup_wizard']);
            AuditLogger::log('signature.consent_acknowledged', $user, [], [
                'consent_version' => config('signatures.consent_version', 'v1'),
                'acknowledged_at' => now()->toIso8601String(),
                'source' => 'signup_wizard',
            ]);

            AuditLogger::log('student.registered', $user, [], [
                'student_id' => $student->id,
                'student_number' => $student->student_number,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        app(\App\Services\SessionSecurityService::class)->handleLogin($user, $request);

        return redirect()->route('dashboard')
            ->with('status', 'Registration successful! Welcome to the COMSOC HUB community.')
            ->with('success', 'Registration successful! Welcome to the COMSOC HUB community.');
    }
}
