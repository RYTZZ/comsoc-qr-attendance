<?php

namespace App\Services;

class AvatarService
{
    public const STYLE = 'adventurer';

    public const PRESET_SEEDS = [
        'Felix' => 'Felix',
        'Aneka' => 'Aneka',
        'Callie' => 'Callie',
        'Milo' => 'Milo',
        'Zoe' => 'Zoe',
        'Jasper' => 'Jasper',
        'Aria' => 'Aria',
        'Leo' => 'Leo',
        'Luna' => 'Luna',
        'Orion' => 'Orion',
        'Maya' => 'Maya',
        'Finn' => 'Finn',
        'Nova' => 'Nova',
        'Kai' => 'Kai',
        'Cora' => 'Cora',
        'Silas' => 'Silas',
    ];

    public const DEFAULT_SEED = 'Felix';

    /**
     * Get the avatar URL for a given seed and style.
     */
    public static function getUrl(?string $seed = null, string $style = self::STYLE): string
    {
        $safeStyle = $style === self::STYLE ? self::STYLE : self::STYLE;
        $safeSeed = !empty($seed) ? rawurlencode(trim($seed)) : self::DEFAULT_SEED;

        return "https://api.dicebear.com/9.x/{$safeStyle}/svg?seed={$safeSeed}";
    }

    /**
     * Check if a seed is in the preset list, or return default.
     */
    public static function sanitizeSeed(?string $seed): string
    {
        $trimmed = trim((string) $seed);
        if ($trimmed === '') {
            return self::DEFAULT_SEED;
        }

        // Allow alphanumeric, underscores, hyphens, up to 50 chars
        if (preg_match('/^[a-zA-Z0-9_\s-]+$/', $trimmed)) {
            return substr($trimmed, 0, 50);
        }

        return self::DEFAULT_SEED;
    }

    /**
     * Get all preset avatars with their preview URLs.
     */
    public static function getPresets(): array
    {
        $presets = [];
        foreach (self::PRESET_SEEDS as $key => $seed) {
            $presets[] = [
                'name' => $key,
                'seed' => $seed,
                'url' => self::getUrl($seed),
            ];
        }

        return $presets;
    }
}
