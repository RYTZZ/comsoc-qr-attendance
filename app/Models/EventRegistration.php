<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EventRegistration extends Model
{
    use HasFactory, HasUuids;

    public const TSHIRT_SIZES = ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL'];

    public const PROGRAMS = [
        'Bachelor of Science in Information Technology (BSIT)',
        'Bachelor of Science in Computer Science (BSCS)',
        'Bachelor of Science in Information Systems (BSIS)',
        'Bachelor of Technical-Vocational Teacher Education (BTVTEd)',
        'Bachelor of Library and Information Science (BLIS)',
    ];

    public const YEAR_LEVELS = [
        'Grade 11',
        'Grade 12',
        '1st Year',
        '2nd Year',
        '3rd Year',
        '4th Year',
        'Graduate / Other',
    ];

    public const FOOD_RESTRICTIONS = [
        'None',
        'Vegetarian',
        'Allergies',
        'Other',
    ];

    protected $fillable = [
        'event_id',
        'full_name',
        'email',
        'organization_id',
        'organization',
        'custom_organization',
        'phone',
        'tshirt_size',
        'year_level',
        'program',
        'food_restrictions',
        'food_restriction_details',
        'confirmed',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'qr_token',
        'qr_expires_at',
    ];

    protected $casts = [
        'confirmed' => 'boolean',
        'reviewed_at' => 'datetime',
        'qr_expires_at' => 'datetime',
    ];

    public function organizationRecord(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approve(User $admin): void
    {
        $this->update([
            'status' => 'approved',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'qr_token' => Str::random(48),
            'qr_expires_at' => $this->event->ends_at ?? $this->event->event_date->endOfDay(),
        ]);
    }

    public function reject(User $admin, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function isQrValid(): bool
    {
        return $this->status === 'approved'
            && $this->qr_token
            && $this->qr_expires_at
            && now()->lt($this->qr_expires_at);
    }
}
