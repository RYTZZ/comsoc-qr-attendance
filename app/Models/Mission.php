<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mission extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'title',
        'description',
        'objectives',
        'instructions',
        'category',
        'xp_reward',
        'points_reward',
        'status',
        'due_date',
        'submission_type',
        'allow_resubmission',
    ];

    protected $casts = [
        'xp_reward' => 'integer',
        'points_reward' => 'integer',
        'due_date' => 'datetime',
        'allow_resubmission' => 'boolean',
    ];

    public function submissions(): HasMany
    {
        return $this->hasMany(MissionSubmission::class);
    }
}
