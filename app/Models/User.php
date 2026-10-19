<?php

namespace App\Models;

use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable;

    public const POSITIONS = [
        'President' => 'President',
        'Vice President - Internal' => 'Vice President — Internal',
        'Vice President - External' => 'Vice President — External',
        'Secretary' => 'Secretary',
        'Assistant Secretary' => 'Assistant Secretary',
        'Executive Secretary' => 'Executive Secretary',
        'Treasurer' => 'Treasurer',
        'Assistant Treasurer' => 'Assistant Treasurer',
        'Auditor' => 'Auditor',
        'Public Information Officer' => 'Public Information Officer (PIO)',
        'Business Manager' => 'Business Manager',
        'Liaison Officer' => 'Liaison Officer',
        'Event Organizer' => 'Event Organizer',
        'Event Staff' => 'Event Staff',
        'Technical Staff' => 'Technical Staff',
        'Creative & Design Officer' => 'Creative & Design Officer',
        'Officer' => 'Officer',
        'Staff' => 'Staff',
    ];

    public const SYSTEM_ROLES = [
        'super_admin' => 'Super Admin (Full Administrative Authority)',
        'president' => 'President (Executive Management)',
        'executive_secretary' => 'Executive Secretary (Administrative Operations)',
        'treasurer' => 'Treasurer (Finance & Rewards)',
        'auditor' => 'Auditor (Audit & Oversight)',
        'event_staff' => 'Event Staff / Organizer (Events & Attendance)',
        'staff' => 'Staff (Operational Support)',
        'admin' => 'Admin (General Operations)',
    ];

    protected $fillable = [
        'member_id',
        'name',
        'nickname',
        'student_number',
        'username',
        'email',
        'preferred_email',
        'password',
        'role',
        'officer_position',
        'avatar_style',
        'avatar_seed',
        'can_collect_rewards',
        'student_id',
        'created_by',
        'is_active',
        'is_eligible',
        'is_activated',
        'is_registrar_imported',
        'identity_verified_at',
        'identity_verification_method',
        'activation_otp',
        'activation_otp_expires_at',
        'activation_token',
        'must_change_password',
        'last_activity_at',
        'active_session_hash',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'activation_otp',
        'activation_token',
        'active_session_hash',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'identity_verified_at' => 'datetime',
            'activation_otp_expires_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_eligible' => 'boolean',
            'is_activated' => 'boolean',
            'is_registrar_imported' => 'boolean',
            'can_collect_rewards' => 'boolean',
            'must_change_password' => 'boolean',
            'last_activity_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->member_id)) {
                $user->member_id = self::generateUniqueMemberId();
            }
        });
    }

    public static function generateUniqueMemberId(): string
    {
        do {
            $candidate = 'CMS-' . strtoupper(\Illuminate\Support\Str::random(8));
        } while (self::where('member_id', $candidate)->exists());

        return $candidate;
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function kiosk(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Kiosk::class, 'user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'reported_by');
    }

    public function profile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function seasonStats(): HasMany
    {
        return $this->hasMany(MemberSeasonStat::class);
    }

    public function expCredits(): HasMany
    {
        return $this->hasMany(ExpCredit::class);
    }

    public function xpTransactions(): HasMany
    {
        return $this->hasMany(XpTransaction::class);
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class);
    }

    public function missionSubmissions(): HasMany
    {
        return $this->hasMany(MissionSubmission::class);
    }

    public function skills(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'user_skills')->withPivot('proficiency')->withTimestamps();
    }

    public function achievements(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')->withPivot('unlocked_at')->withTimestamps();
    }

    public function jobBookmarks(): HasMany
    {
        return $this->hasMany(JobBookmark::class);
    }

    public function eventRegistrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    /**
     * Custom in-app notifications (App\Models\Notification).
     *
     * Named appNotifications() to avoid shadowing the Notifiable trait's
     * notifications() morphMany (Illuminate\Notifications\DatabaseNotification),
     * which is required by Password::sendResetLink() and the notify() pipeline.
     */
    public function appNotifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Send the password reset notification synchronously, bypassing the queue.
     *
     * Production runs on Render's free tier, which does not run a persistent
     * queue worker. Queued notifications are never processed, so reset emails
     * were silently dropped. Sending synchronously guarantees delivery as long
     * as the mail transport is reachable.
     */
    public function sendPasswordResetNotification($token): void
    {
        $notification = new ResetPasswordNotification($token);

        try {
            // Send via the mailer directly, not through the queue, so no worker is needed.
            $this->notifyNow($notification);
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            throw $e;
        } catch (\Symfony\Component\Mime\Exception\ExceptionInterface $e) {
            throw $e;
        } catch (\Throwable $e) {
            // If the failure originates in the mailer or stream transport layer, wrap as TransportException
            if (
                str_contains(get_class($e), 'Mail') ||
                str_contains(get_class($e), 'Transport') ||
                str_contains($e->getFile(), 'Mailer') ||
                str_contains($e->getFile(), 'Mail') ||
                str_contains($e->getMessage(), 'Connection') ||
                str_contains($e->getMessage(), 'SMTP')
            ) {
                throw new \Symfony\Component\Mailer\Exception\TransportException($e->getMessage(), (int) $e->getCode(), $e);
            }
            throw $e;
        }
    }

    public function assignedEvents(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_organizer_assignments', 'user_id', 'event_id')->withTimestamps();
    }

    public function notificationPreferences(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserNotificationPreference::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function announcementReceipts(): HasMany
    {
        return $this->hasMany(AnnouncementReceipt::class);
    }

    public function ownedProjects(): HasMany
    {
        return $this->hasMany(CollaborationProject::class, 'owner_id');
    }

    public function projectApplications(): HasMany
    {
        return $this->hasMany(ProjectApplication::class);
    }

    public function projectMemberships(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function volunteerApplications(): HasMany
    {
        return $this->hasMany(VolunteerApplication::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->nickname ?: $this->name;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isComsocAdmin(): bool
    {
        return in_array($this->role, ['comsoc_admin', 'president', 'executive_secretary']);
    }

    public function isPresident(): bool
    {
        return $this->role === 'president';
    }

    public function isExecutiveSecretary(): bool
    {
        return $this->role === 'executive_secretary';
    }

    public function isAuditor(): bool
    {
        return $this->role === 'auditor';
    }

    public function isEventStaff(): bool
    {
        return in_array($this->role, ['event_staff', 'staff', 'event_organizer']);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'comsoc_admin', 'admin', 'president', 'executive_secretary']);
    }

    public function isEventOrganizer(): bool
    {
        return in_array($this->role, ['event_organizer', 'staff', 'event_staff']);
    }

    public function isMember(): bool
    {
        return in_array($this->role, ['member', 'student']);
    }

    public function canCollectRewards(): bool
    {
        return $this->isSuperAdmin() || $this->isComsocAdmin() || (bool)$this->can_collect_rewards;
    }

    public function isTreasurer(): bool
    {
        return $this->role === 'treasurer';
    }

    public function isStaff(): bool
    {
        return in_array($this->role, ['staff', 'event_organizer', 'event_staff']);
    }

    public function isStudent(): bool
    {
        return in_array($this->role, ['student', 'member']);
    }

    public function isKiosk(): bool
    {
        return $this->role === 'kiosk';
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;
        $userRole = $this->role;

        // Role equivalence mapping for governance and backwards compatibility
        if (in_array($userRole, $roles, true)) {
            return true;
        }

        // Super Admin has access to all administrative and organizer roles
        if ($userRole === 'super_admin' && array_intersect(['admin', 'comsoc_admin', 'president', 'executive_secretary', 'event_organizer', 'staff', 'event_staff'], $roles)) {
            return true;
        }

        // COMSOC Admin has access to event organizer and staff roles
        if ($userRole === 'comsoc_admin' && array_intersect(['event_organizer', 'staff', 'event_staff'], $roles)) {
            return true;
        }

        // Event Staff aliases staff / event_organizer
        if (in_array($userRole, ['event_staff', 'staff', 'event_organizer'], true) && array_intersect(['event_staff', 'staff', 'event_organizer'], $roles)) {
            return true;
        }

        // President & Executive Secretary can access admin and comsoc_admin areas
        if (in_array($userRole, ['president', 'executive_secretary'], true) && array_intersect(['admin', 'comsoc_admin'], $roles)) {
            return true;
        }

        // Student / member aliases
        if (in_array($userRole, ['student', 'member'], true) && array_intersect(['student', 'member'], $roles)) {
            return true;
        }

        return false;
    }

    public function getSpendableExp(): int
    {
        $creditSum = (int) $this->expCredits()
            ->where('status', 'active')
            ->where('remaining_amount', '>', 0)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->sum('remaining_amount');

        if ($creditSum > 0) {
            return $creditSum;
        }

        return $this->getSpendablePoints();
    }

    public function getSpendablePoints(): int
    {
        $pointSum = (int) $this->pointTransactions()->sum('amount');
        if ($pointSum > 0) {
            return $pointSum;
        }

        $activeSeason = \App\Models\Season::active();
        if ($activeSeason) {
            $stat = $this->seasonStats()->where('season_id', $activeSeason->id)->first();
            if ($stat && $stat->redeemable_points > 0) {
                return (int) $stat->redeemable_points;
            }
        }

        return 0;
    }

    public function getLifetimeExp(): int
    {
        $lifetimeFromDelta = (int) $this->xpTransactions()->sum('lifetime_delta');
        if ($lifetimeFromDelta > 0) {
            return $lifetimeFromDelta;
        }

        $creditTotal = (int) $this->expCredits()->sum('initial_amount');
        if ($creditTotal > 0) {
            return $creditTotal;
        }

        return (int) $this->xpTransactions()->sum('amount');
    }

    public function getAvatarUrlAttribute(): string
    {
        return \App\Services\AvatarService::getUrl($this->avatar_seed, $this->avatar_style ?? 'adventurer');
    }

    public function getEffectiveAvatarSeedAttribute(): string
    {
        return $this->avatar_seed ?: \App\Services\AvatarService::DEFAULT_SEED;
    }

    /**
     * Check if a viewer has authorization to view student IDs.
     * STRICT SECURITY RULE: Only SUPER ADMIN is authorized.
     */
    public static function canViewStudentId(?User $viewer = null): bool
    {
        $actor = $viewer ?? auth()->user();
        return $actor !== null && $actor->role === 'super_admin';
    }

    /**
     * Return student number only if the viewer is authorized (super_admin).
     */
    public function getMaskedStudentNumber(?User $viewer = null): ?string
    {
        if (self::canViewStudentId($viewer)) {
            return $this->student_number ?? $this->student?->student_number;
        }

        return null;
    }
}
