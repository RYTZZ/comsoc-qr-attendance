<?php

namespace App\Services;

use App\Models\Level;
use App\Models\MemberSeasonStat;
use App\Models\PointTransaction;
use App\Models\ProfileCompletionReward;
use App\Models\Season;
use App\Models\Student;
use App\Models\User;
use App\Models\XpTransaction;
use App\Rules\UsernameRule;
use App\Support\RowLock;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ProfileCompletionService
{
    public const SOURCE = 'profile_completion';

    public const EXP_REWARD = 100;

    public const POINTS_REWARD = 50;

    public const THRESHOLD = 100;

    public const NOTIFICATION_TITLE = 'Profile Completed!';

    public const NOTIFICATION_MESSAGE = "Congratulations! You've completed your COMSOC Hub profile. Your rewards have been credited successfully.";

    private ?Closure $expCreditHook = null;

    private ?Closure $pointsCreditHook = null;

    public function __construct(
        private ExpLedgerService $expLedgerService
    ) {}

    /**
     * Build the deterministic member-specific transaction reference.
     */
    public static function referenceKeyFor(User|string $user): string
    {
        $userId = $user instanceof User ? (string) $user->id : (string) $user;

        return self::SOURCE.':'.$userId;
    }

    /**
     * Optional testing/lifecycle hooks for simulating failures inside the atomic reward transaction.
     */
    public function setExpCreditHook(?Closure $hook): self
    {
        $this->expCreditHook = $hook;

        return $this;
    }

    public function setPointsCreditHook(?Closure $hook): self
    {
        $this->pointsCreditHook = $hook;

        return $this;
    }

    /**
     * Calculate profile completion percentage and field breakdown server-side.
     */
    public function evaluate(User $user): array
    {
        $user->loadMissing(['profile', 'student', 'skills', 'profileCompletionReward']);

        $profile = $user->profile;
        $student = $user->student;

        $requirements = [
            // Category 1: Personal Information
            [
                'key' => 'name',
                'label' => 'Full Name',
                'category' => 'Personal Information',
                'description' => 'Your full display name (not a raw student ID number).',
                'completed' => $this->isValidFullName($user),
                'editable_by_member' => true,
                'action_url' => route('profile.edit').'#personal-info',
                'action_label' => 'Update Name',
            ],
            [
                'key' => 'username',
                'label' => 'Adventurer Username',
                'category' => 'Personal Information',
                'description' => 'Unique 3–20 character handle using letters, numbers, or underscores.',
                'completed' => $this->isValidUsername($user),
                'editable_by_member' => true,
                'action_url' => route('profile.edit').'#personal-info',
                'action_label' => 'Set Username',
            ],
            [
                'key' => 'avatar',
                'label' => 'Adventurer Avatar',
                'category' => 'Personal Information',
                'description' => 'Selected valid COMSOC Adventurer avatar.',
                'completed' => $this->isValidAvatar($user),
                'editable_by_member' => true,
                'action_url' => route('profile.edit').'#avatar-section',
                'action_label' => 'Choose Avatar',
            ],

            // Category 2: Academic Information (Administrator-managed, read-only for members)
            [
                'key' => 'academic_record',
                'label' => 'Verified Academic Record (Program & Year Level)',
                'category' => 'Academic Information',
                'description' => 'Official COMSOC student record with valid degree program and year level.',
                'completed' => $this->isValidAcademicRecord($student),
                'editable_by_member' => false,
                'action_url' => route('profile.edit').'#academic-info',
                'action_label' => 'View Academic Info',
            ],

            // Category 3: Contact Information
            [
                'key' => 'email',
                'label' => 'Verified Email Address',
                'category' => 'Contact Information',
                'description' => 'Valid email address verified on your COMSOC Hub account.',
                'completed' => $this->isValidVerifiedEmail($user),
                'editable_by_member' => true,
                'action_url' => route('profile.edit').'#contact-info',
                'action_label' => 'Verify Email',
            ],

            // Category 4: About Me
            [
                'key' => 'bio',
                'label' => 'About Me / Bio',
                'category' => 'About Me',
                'description' => 'A short introduction about your goals, projects, or background.',
                'completed' => $this->isValidBio($profile?->bio),
                'editable_by_member' => true,
                'action_url' => route('profile.edit').'#about-me',
                'action_label' => 'Write Bio',
            ],
            [
                'key' => 'skills',
                'label' => 'Technical Skills',
                'category' => 'About Me',
                'description' => 'At least one technical skill selected on your member profile.',
                'completed' => $this->hasValidSkills($user),
                'editable_by_member' => true,
                'action_url' => route('profile.edit').'#about-me',
                'action_label' => 'Add Skills',
            ],
            [
                'key' => 'interests',
                'label' => 'Areas of Interest',
                'category' => 'About Me',
                'description' => 'At least one technology or community interest tag.',
                'completed' => $this->hasValidInterests($profile?->interests),
                'editable_by_member' => true,
                'action_url' => route('profile.edit').'#about-me',
                'action_label' => 'Add Interests',
            ],
        ];

        $totalCount = count($requirements);
        $completedFields = array_values(array_filter($requirements, fn (array $item) => $item['completed'] === true));
        $missingFields = array_values(array_filter($requirements, fn (array $item) => $item['completed'] === false));
        $completedCount = count($completedFields);

        if ($completedCount === 0) {
            $percentage = 0;
        } elseif ($completedCount === $totalCount) {
            $percentage = 100;
        } else {
            $percentage = min(99, (int) floor(($completedCount / $totalCount) * 100));
        }

        $isComplete = $completedCount === $totalCount && $percentage === self::THRESHOLD;

        $categories = [];
        foreach ($requirements as $req) {
            $cat = $req['category'];
            if (! isset($categories[$cat])) {
                $categories[$cat] = [
                    'name' => $cat,
                    'total' => 0,
                    'completed' => 0,
                    'is_complete' => false,
                    'items' => [],
                ];
            }
            $categories[$cat]['total']++;
            if ($req['completed']) {
                $categories[$cat]['completed']++;
            }
            $categories[$cat]['items'][] = $req;
            $categories[$cat]['is_complete'] = $categories[$cat]['completed'] === $categories[$cat]['total'];
        }

        $rewardRecord = $this->getRewardRecord($user);
        $rewardIssued = $rewardRecord !== null || $this->hasLedgerReward($user);

        return [
            'percentage' => $percentage,
            'threshold' => self::THRESHOLD,
            'is_complete' => $isComplete,
            'completed_count' => $completedCount,
            'total_count' => $totalCount,
            'requirements' => $requirements,
            'completed_fields' => $completedFields,
            'missing_fields' => $missingFields,
            'categories' => $categories,
            'reward_issued' => $rewardIssued,
            'reward_record' => $rewardRecord,
            'exp_reward' => self::EXP_REWARD,
            'points_reward' => self::POINTS_REWARD,
            'source' => self::SOURCE,
            'reference_key' => self::referenceKeyFor($user),
            'notification_title' => self::NOTIFICATION_TITLE,
            'notification_message' => self::NOTIFICATION_MESSAGE,
        ];
    }

    /**
     * Evaluate profile completion after a profile update and automatically issue
     * 100 EXP and 50 Points exactly once if all eligibility & ownership checks pass.
     */
    public function evaluateAndAwardIfComplete(User $user, ?User $actor = null): array
    {
        // Ownership verification: only the authenticated member owning the profile may trigger issuance
        if ($actor !== null && (string) $actor->id !== (string) $user->id) {
            return [
                'awarded' => false,
                'reason' => 'unauthorized_actor',
                'exp_awarded' => 0,
                'points_awarded' => 0,
                'evaluation' => $this->evaluate($user),
            ];
        }

        $freshUser = $user->fresh(['profile', 'student', 'skills', 'profileCompletionReward']) ?? $user;
        $evaluation = $this->evaluate($freshUser);

        if ($evaluation['reward_issued']) {
            return [
                'awarded' => false,
                'reason' => 'already_rewarded',
                'exp_awarded' => 0,
                'points_awarded' => 0,
                'evaluation' => $evaluation,
            ];
        }

        if (! $evaluation['is_complete'] || $evaluation['percentage'] < self::THRESHOLD) {
            return [
                'awarded' => false,
                'reason' => 'incomplete_profile',
                'exp_awarded' => 0,
                'points_awarded' => 0,
                'evaluation' => $evaluation,
            ];
        }

        $referenceKey = self::referenceKeyFor($freshUser);

        try {
            $txResult = DB::transaction(function () use ($freshUser, $referenceKey) {
                /** @var User $lockedUser */
                $lockedUser = RowLock::acquire(User::class, $freshUser->id);
                $lockedUser->load(['profile', 'student', 'skills', 'profileCompletionReward']);

                // Re-verify duplicate prevention inside row lock
                if ($this->hasExistingRewardInsideTransaction($lockedUser, $referenceKey)) {
                    return [
                        'awarded' => false,
                        'reason' => 'already_rewarded',
                        'exp_awarded' => 0,
                        'points_awarded' => 0,
                    ];
                }

                // Re-verify 100% completion inside row lock
                $lockedEvaluation = $this->evaluate($lockedUser);
                if (! $lockedEvaluation['is_complete'] || $lockedEvaluation['percentage'] < self::THRESHOLD) {
                    return [
                        'awarded' => false,
                        'reason' => 'incomplete_profile',
                        'exp_awarded' => 0,
                        'points_awarded' => 0,
                    ];
                }

                $activeSeason = Season::where('is_active', true)->first();

                // 1. Credit 100 EXP
                if ($this->expCreditHook !== null) {
                    ($this->expCreditHook)($lockedUser, self::EXP_REWARD, $referenceKey);
                }

                $expResult = $this->expLedgerService->creditExp(
                    user: $lockedUser,
                    amount: self::EXP_REWARD,
                    sourceType: self::SOURCE,
                    sourceId: (string) $lockedUser->id,
                    description: 'Profile Completion Reward (100% COMSOC Hub Profile)',
                    expirationDays: null,
                    idempotencyKey: $referenceKey,
                    actor: $lockedUser
                );

                if (empty($expResult['awarded']) || empty($expResult['transaction'])) {
                    throw new RuntimeException('Failed to credit Profile Completion EXP transaction.');
                }

                /** @var XpTransaction $xpTransaction */
                $xpTransaction = $expResult['transaction'];

                if ($activeSeason && $xpTransaction->season_id === null) {
                    $xpTransaction->update(['season_id' => $activeSeason->id]);
                }

                // 2. Credit 50 Points
                if ($this->pointsCreditHook !== null) {
                    ($this->pointsCreditHook)($lockedUser, self::POINTS_REWARD, $referenceKey);
                }

                $pointTransaction = PointTransaction::create([
                    'user_id' => $lockedUser->id,
                    'season_id' => $activeSeason?->id,
                    'amount' => self::POINTS_REWARD,
                    'transaction_type' => self::SOURCE,
                    'source_type' => self::SOURCE,
                    'source_id' => (string) $lockedUser->id,
                    'description' => 'Profile Completion Reward (100% COMSOC Hub Profile)',
                    'idempotency_key' => $referenceKey,
                    'created_by' => $lockedUser->id,
                ]);

                if (! $pointTransaction || ! $pointTransaction->id) {
                    throw new RuntimeException('Failed to credit Profile Completion Points transaction.');
                }

                // 3. Synchronize active season stats (XP, Level, and Redeemable Points)
                if ($activeSeason) {
                    $stat = MemberSeasonStat::where('user_id', $lockedUser->id)
                        ->where('season_id', $activeSeason->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $stat) {
                        $stat = MemberSeasonStat::create([
                            'user_id' => $lockedUser->id,
                            'season_id' => $activeSeason->id,
                            'season_xp' => 0,
                            'redeemable_points' => 0,
                            'current_level' => 1,
                        ]);
                    }

                    $newSeasonXp = (int) $stat->season_xp + self::EXP_REWARD;
                    $newPoints = (int) $stat->redeemable_points + self::POINTS_REWARD;
                    $appropriateLevel = Level::forXp($newSeasonXp);

                    $stat->update([
                        'season_xp' => $newSeasonXp,
                        'redeemable_points' => $newPoints,
                        'current_level' => $appropriateLevel && $appropriateLevel->level_number > $stat->current_level
                            ? $appropriateLevel->level_number
                            : $stat->current_level,
                    ]);
                }

                // 4. Record authoritative one-time ProfileCompletionReward ledger row
                $reward = ProfileCompletionReward::create([
                    'user_id' => $lockedUser->id,
                    'source' => self::SOURCE,
                    'reference_key' => $referenceKey,
                    'xp_transaction_id' => $xpTransaction->id,
                    'point_transaction_id' => $pointTransaction->id,
                    'exp_awarded' => self::EXP_REWARD,
                    'points_awarded' => self::POINTS_REWARD,
                    'completion_percentage' => self::THRESHOLD,
                    'awarded_at' => now(),
                ]);

                AuditLogger::log('profile_completion.reward_issued', $reward, [], [
                    'user_id' => $lockedUser->id,
                    'source' => self::SOURCE,
                    'reference_key' => $referenceKey,
                    'exp_awarded' => self::EXP_REWARD,
                    'points_awarded' => self::POINTS_REWARD,
                    'xp_transaction_id' => $xpTransaction->id,
                    'point_transaction_id' => $pointTransaction->id,
                ]);

                return [
                    'awarded' => true,
                    'reason' => 'credited',
                    'exp_awarded' => self::EXP_REWARD,
                    'points_awarded' => self::POINTS_REWARD,
                    'reference_key' => $referenceKey,
                    'reward' => $reward,
                    'xp_transaction' => $xpTransaction,
                    'point_transaction' => $pointTransaction,
                ];
            });
        } catch (QueryException $e) {
            if ($this->isUniqueConstraintViolation($e)) {
                return [
                    'awarded' => false,
                    'reason' => 'already_rewarded',
                    'exp_awarded' => 0,
                    'points_awarded' => 0,
                    'evaluation' => $this->evaluate($user->fresh(['profile', 'student', 'skills', 'profileCompletionReward']) ?? $user),
                ];
            }

            throw $e;
        }

        $updatedEvaluation = $this->evaluate($user->fresh(['profile', 'student', 'skills', 'profileCompletionReward']) ?? $user);
        $txResult['evaluation'] = $updatedEvaluation;

        // 5. Notify the member only AFTER the database transaction has successfully committed
        if (! empty($txResult['awarded'])) {
            try {
                NotificationService::send(
                    user: $freshUser,
                    type: self::SOURCE,
                    title: self::NOTIFICATION_TITLE,
                    body: self::NOTIFICATION_MESSAGE.' (+100 EXP, +50 Points)',
                    targetUrl: route('profile.edit')
                );
            } catch (Throwable) {
                // Do not fail or rollback committed rewards if notification dispatch encounters an issue
            }
        }

        return $txResult;
    }

    /**
     * Check whether a member has already received the one-time profile completion reward.
     */
    public function hasReceivedReward(User|string $user): bool
    {
        $userObj = $user instanceof User ? $user : User::find($user);
        if (! $userObj) {
            return false;
        }

        return $this->getRewardRecord($userObj) !== null || $this->hasLedgerReward($userObj);
    }

    public function getRewardRecord(User $user): ?ProfileCompletionReward
    {
        if ($user->relationLoaded('profileCompletionReward') && $user->profileCompletionReward !== null) {
            return $user->profileCompletionReward;
        }

        return ProfileCompletionReward::where('user_id', $user->id)
            ->orWhere('reference_key', self::referenceKeyFor($user))
            ->first();
    }

    private function hasLedgerReward(User $user): bool
    {
        $referenceKey = self::referenceKeyFor($user);

        return XpTransaction::where('idempotency_key', $referenceKey)
            ->orWhere(function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('source_type', self::SOURCE);
            })
            ->exists()
            || PointTransaction::where('idempotency_key', $referenceKey)
                ->orWhere(function ($q) use ($user) {
                    $q->where('user_id', $user->id)->where('source_type', self::SOURCE);
                })
                ->exists();
    }

    private function hasExistingRewardInsideTransaction(User $user, string $referenceKey): bool
    {
        if (ProfileCompletionReward::where('user_id', $user->id)->orWhere('reference_key', $referenceKey)->lockForUpdate()->exists()) {
            return true;
        }

        if (XpTransaction::where('idempotency_key', $referenceKey)->orWhere(fn ($q) => $q->where('user_id', $user->id)->where('source_type', self::SOURCE))->lockForUpdate()->exists()) {
            return true;
        }

        if (PointTransaction::where('idempotency_key', $referenceKey)->orWhere(fn ($q) => $q->where('user_id', $user->id)->where('source_type', self::SOURCE))->lockForUpdate()->exists()) {
            return true;
        }

        return false;
    }

    private function isValidFullName(User $user): bool
    {
        $name = is_string($user->name) ? trim($user->name) : '';
        if ($name === '' || mb_strlen($name) < 2) {
            return false;
        }

        if ($user->looksLikeStudentId($name)) {
            return false;
        }

        return true;
    }

    private function isValidUsername(User $user): bool
    {
        $username = is_string($user->username) ? trim($user->username) : '';
        if ($username === '') {
            return false;
        }

        $len = strlen($username);
        if ($len < 3 || $len > 20) {
            return false;
        }

        if (! preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return false;
        }

        if (in_array(strtolower($username), UsernameRule::RESERVED_USERNAMES, true)) {
            return false;
        }

        if ($user->looksLikeStudentId($username)) {
            return false;
        }

        return true;
    }

    private function isValidAvatar(User $user): bool
    {
        $seed = is_string($user->avatar_seed) ? trim($user->avatar_seed) : '';
        if ($seed === '') {
            return false;
        }

        if (($user->avatar_style ?? 'adventurer') !== AvatarService::STYLE) {
            return false;
        }

        $sanitized = AvatarService::sanitizeSeed($seed);

        return $sanitized !== '' && $sanitized === $seed;
    }

    private function isValidAcademicRecord(?Student $student): bool
    {
        if (! $student) {
            return false;
        }

        $program = is_string($student->program) ? trim($student->program) : '';
        $yearLevel = is_string($student->year_level) ? trim($student->year_level) : '';

        if ($program === '' || $yearLevel === '') {
            return false;
        }

        $normalizedYear = Student::normalizeYearLevel($yearLevel);
        if (! in_array($normalizedYear, Student::YEAR_LEVELS, true)) {
            return false;
        }

        return true;
    }

    private function isValidVerifiedEmail(User $user): bool
    {
        $email = is_string($user->email) ? trim($user->email) : '';
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        return $user->email_verified_at !== null;
    }

    private function isValidBio(?string $bio): bool
    {
        if (! is_string($bio)) {
            return false;
        }

        return trim($bio) !== '';
    }

    private function hasValidSkills(User $user): bool
    {
        if ($user->skills && $user->skills->isNotEmpty()) {
            return true;
        }

        $techStack = $user->profile?->tech_stack;
        if (is_array($techStack)) {
            $nonEmpty = array_filter($techStack, fn ($item) => is_string($item) && trim($item) !== '');

            return count($nonEmpty) > 0;
        }

        return false;
    }

    private function hasValidInterests(mixed $interests): bool
    {
        if (! is_array($interests)) {
            return false;
        }

        $valid = array_filter($interests, fn ($item) => is_string($item) && trim($item) !== '');

        return count($valid) > 0;
    }

    private function isUniqueConstraintViolation(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? $e->getCode());
        $driverCode = (int) ($e->errorInfo[1] ?? 0);
        $message = strtolower($e->getMessage());

        return $sqlState === '23000'
            || $sqlState === '23505'
            || in_array($driverCode, [19, 1062], true)
            || str_contains($message, 'unique constraint')
            || str_contains($message, 'duplicate entry');
    }
}
