<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Membership extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'student_id',
        'academic_year_id',
        'status',
        'fee_paid',
        'paid_at',
        'membership_number',
    ];

    protected $casts = ['paid_at' => 'date'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class);
    }

    public function activeQrCode(): HasOne
    {
        return $this->hasOne(QrCode::class)->where('status', 'active');
    }

    public function latestQrCode(): HasOne
    {
        return $this->hasOne(QrCode::class)->ofMany(['created_at' => 'MAX']);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    protected static function booted(): void
    {
        static::creating(function (self $membership) {
            if (empty($membership->membership_number)) {
                $year = AcademicYear::find($membership->academic_year_id);
                $count = static::where('academic_year_id', $membership->academic_year_id)->count() + 1;
                $membership->membership_number = 'MEM-' . $year->year_start . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}
