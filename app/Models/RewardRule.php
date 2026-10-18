<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RewardRule extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'activity_type',
        'name',
        'xp_amount',
        'points_amount',
        'is_active',
    ];

    protected $casts = [
        'xp_amount' => 'integer',
        'points_amount' => 'integer',
        'is_active' => 'boolean',
    ];
}
