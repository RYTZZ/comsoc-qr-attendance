<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    use HasFactory;

    protected $fillable = [
        'level',
        'title',
        'min_xp',
        'badge_icon',
    ];

    protected $casts = [
        'level' => 'integer',
        'min_xp' => 'integer',
    ];

    public static function forXp(int $xp): self
    {
        $level = static::where('min_xp', '<=', $xp)
            ->orderByDesc('level')
            ->first();

        if ($level) {
            return $level;
        }

        $first = static::orderBy('level')->first();
        if ($first) {
            return $first;
        }

        return new static([
            'level' => 1,
            'title' => 'Novice Explorer',
            'min_xp' => 0,
            'badge_icon' => 'sparkles',
        ]);
    }
}
