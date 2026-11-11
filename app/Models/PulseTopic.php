<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PulseTopic extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'category',
        'title',
        'content',
        'blind_session_hash',
        'status',
        'official_response',
        'responded_by',
        'responded_at',
        'votes_count',
        'is_pinned',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'votes_count' => 'integer',
        'responded_at' => 'datetime',
    ];

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PulseVote::class);
    }

    public static function generateBlindHash(string $userId, string $actionType): string
    {
        // Daily salted hash: prevents linking a single user across days, while enforcing 1 vote per topic per day
        $salt = config('app.key') . date('Y-m-d');
        return hash('sha256', $userId . $salt . $actionType);
    }
}
