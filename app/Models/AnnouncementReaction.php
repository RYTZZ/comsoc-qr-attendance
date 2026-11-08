<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementReaction extends Model
{
    use HasFactory, HasUuids;

    public const TYPES = [
        'like' => [
            'key' => 'like',
            'emoji' => '👍',
            'label' => 'Like',
            'purpose' => 'General appreciation or acknowledgment',
        ],
        'love' => [
            'key' => 'love',
            'emoji' => '❤️',
            'label' => 'Love',
            'purpose' => 'Strong appreciation or support',
        ],
        'haha' => [
            'key' => 'haha',
            'emoji' => '😂',
            'label' => 'Haha',
            'purpose' => 'Humor or amusement',
        ],
        'wow' => [
            'key' => 'wow',
            'emoji' => '😮',
            'label' => 'Wow',
            'purpose' => 'Surprise or excitement',
        ],
        'celebrate' => [
            'key' => 'celebrate',
            'emoji' => '🎉',
            'label' => 'Celebrate',
            'purpose' => 'Congratulations or celebration',
        ],
        'support' => [
            'key' => 'support',
            'emoji' => '🙏',
            'label' => 'Support',
            'purpose' => 'Gratitude or encouragement',
        ],
    ];

    public static function allowedTypes(): array
    {
        return array_keys(self::TYPES);
    }

    public static function isValidType(string $type): bool
    {
        return array_key_exists($type, self::TYPES);
    }

    public static function getEmoji(string $type): string
    {
        return self::TYPES[$type]['emoji'] ?? '👍';
    }

    public static function getLabel(string $type): string
    {
        return self::TYPES[$type]['label'] ?? 'Like';
    }

    protected $fillable = [
        'announcement_id',
        'user_id',
        'reaction_type',
    ];

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

