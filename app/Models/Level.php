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

    /**
     * Canonical 20 Level Badges Collection definition.
     * Acts as the authoritative specification of titles, thresholds, badge names, icons, and tiers.
     */
    public const CANONICAL_LEVELS = [
        1  => ['level' => 1,  'title' => 'Code Initiate',          'min_xp' => 0,      'badge_name' => 'First Commit',        'badge_icon' => 'code',        'tier' => 'Bronze'],
        2  => ['level' => 2,  'title' => 'Logic Apprentice',       'min_xp' => 100,    'badge_name' => 'Logic Seeker',        'badge_icon' => 'brain',       'tier' => 'Bronze'],
        3  => ['level' => 3,  'title' => 'Code Vanguard',          'min_xp' => 250,    'badge_name' => 'Vanguard',            'badge_icon' => 'shield-check','tier' => 'Bronze'],
        4  => ['level' => 4,  'title' => 'Byte Explorer',          'min_xp' => 450,    'badge_name' => 'Byte Explorer',       'badge_icon' => 'compass',     'tier' => 'Silver'],
        5  => ['level' => 5,  'title' => 'Syntax Specialist',      'min_xp' => 700,    'badge_name' => 'Syntax Master',       'badge_icon' => 'terminal',    'tier' => 'Silver'],
        6  => ['level' => 6,  'title' => 'Digital Engineer',       'min_xp' => 1000,   'badge_name' => 'Digital Builder',     'badge_icon' => 'cpu',         'tier' => 'Silver'],
        7  => ['level' => 7,  'title' => 'Algorithm Strategist',   'min_xp' => 1400,   'badge_name' => 'Algorithm Ace',       'badge_icon' => 'binary',      'tier' => 'Blue'],
        8  => ['level' => 8,  'title' => 'Systems Innovator',      'min_xp' => 1900,   'badge_name' => 'System Architect',    'badge_icon' => 'network',     'tier' => 'Blue'],
        9  => ['level' => 9,  'title' => 'Cybernetic Expert',      'min_xp' => 2500,   'badge_name' => 'Cyber Expert',        'badge_icon' => 'fingerprint', 'tier' => 'Blue'],
        10 => ['level' => 10, 'title' => 'Code Legend',            'min_xp' => 3200,   'badge_name' => 'Code Legend',         'badge_icon' => 'trophy',      'tier' => 'Purple'],
        11 => ['level' => 11, 'title' => 'Tech Virtuoso',          'min_xp' => 4000,   'badge_name' => 'Tech Virtuoso',       'badge_icon' => 'gem',         'tier' => 'Purple'],
        12 => ['level' => 12, 'title' => 'Innovation Architect',   'min_xp' => 5000,   'badge_name' => 'Innovation Master',   'badge_icon' => 'lightbulb',   'tier' => 'Purple'],
        13 => ['level' => 13, 'title' => 'Digital Pioneer',        'min_xp' => 6200,   'badge_name' => 'Digital Pioneer',     'badge_icon' => 'rocket',      'tier' => 'Gold'],
        14 => ['level' => 14, 'title' => 'Quantum Vanguard',       'min_xp' => 7600,   'badge_name' => 'Quantum Vanguard',    'badge_icon' => 'orbit',       'tier' => 'Gold'],
        15 => ['level' => 15, 'title' => 'Grandmaster Developer',  'min_xp' => 9200,   'badge_name' => 'Grandmaster',         'badge_icon' => 'crown',       'tier' => 'Gold'],
        16 => ['level' => 16, 'title' => 'Cyber Sovereign',        'min_xp' => 11000,  'badge_name' => 'Cyber Sovereign',     'badge_icon' => 'shield',      'tier' => 'Legendary'],
        17 => ['level' => 17, 'title' => 'Legendary Architect',    'min_xp' => 13000,  'badge_name' => 'Legendary Architect', 'badge_icon' => 'medal',       'tier' => 'Legendary'],
        18 => ['level' => 18, 'title' => 'Digital Overlord',       'min_xp' => 15500,  'badge_name' => 'Digital Overlord',    'badge_icon' => 'zap',         'tier' => 'Legendary'],
        19 => ['level' => 19, 'title' => 'Technology Titan',       'min_xp' => 18500,  'badge_name' => 'Technology Titan',    'badge_icon' => 'flame',       'tier' => 'Elite'],
        20 => ['level' => 20, 'title' => 'Computing Legend',       'min_xp' => 22000,  'badge_name' => 'Computing Legend',    'badge_icon' => 'award',       'tier' => 'Elite'],
    ];

    public const MAX_LEVEL = 20;

    /**
     * Resolve authoritative badge name for this level.
     */
    public function getBadgeNameAttribute(): string
    {
        return self::CANONICAL_LEVELS[$this->level]['badge_name'] ?? ('Level ' . $this->level . ' Badge');
    }

    /**
     * Resolve tier name (Bronze, Silver, Blue, Purple, Gold, Legendary, Elite).
     */
    public function getTierAttribute(): string
    {
        return self::tierForLevel($this->level);
    }

    /**
     * Static helper to resolve tier for a given level number.
     */
    public static function tierForLevel(int $level): string
    {
        return match (true) {
            $level >= 19 => 'Elite',
            $level >= 16 => 'Legendary',
            $level >= 13 => 'Gold',
            $level >= 10 => 'Purple',
            $level >= 7  => 'Blue',
            $level >= 4  => 'Silver',
            default      => 'Bronze',
        };
    }

    /**
     * Visual styling tokens (colors, gradients, border, glow) for a given tier.
     */
    public static function tierStyling(string $tier): array
    {
        return match (strtolower($tier)) {
            'silver' => [
                'tier' => 'Silver',
                'bg_gradient' => 'from-slate-700 via-slate-800 to-slate-900',
                'border' => 'border-slate-400',
                'border_color' => '#94A3B8',
                'text' => 'text-slate-200',
                'accent' => 'text-slate-300',
                'badge_bg' => 'bg-slate-800/80',
                'glow' => 'shadow-slate-500/20',
                'pill_bg' => 'bg-slate-700/60 text-slate-200 border-slate-500',
            ],
            'blue' => [
                'tier' => 'Blue',
                'bg_gradient' => 'from-blue-900 via-sky-950 to-[#0B1120]',
                'border' => 'border-sky-500',
                'border_color' => '#0ea5e9',
                'text' => 'text-sky-300',
                'accent' => 'text-sky-400',
                'badge_bg' => 'bg-sky-950/80',
                'glow' => 'shadow-sky-500/25',
                'pill_bg' => 'bg-sky-900/60 text-sky-200 border-sky-600',
            ],
            'purple' => [
                'tier' => 'Purple',
                'bg_gradient' => 'from-purple-900 via-violet-950 to-[#0B1120]',
                'border' => 'border-purple-500',
                'border_color' => '#a855f7',
                'text' => 'text-purple-300',
                'accent' => 'text-purple-400',
                'badge_bg' => 'bg-purple-950/80',
                'glow' => 'shadow-purple-500/30',
                'pill_bg' => 'bg-purple-900/60 text-purple-200 border-purple-600',
            ],
            'gold' => [
                'tier' => 'Gold',
                'bg_gradient' => 'from-amber-700 via-yellow-800 to-[#172033]',
                'border' => 'border-amber-400',
                'border_color' => '#f59e0b',
                'text' => 'text-amber-300',
                'accent' => 'text-amber-400',
                'badge_bg' => 'bg-amber-950/80',
                'glow' => 'shadow-amber-500/30',
                'pill_bg' => 'bg-amber-800/60 text-amber-200 border-amber-500',
            ],
            'legendary' => [
                'tier' => 'Legendary',
                'bg_gradient' => 'from-[#560E0F] via-rose-950 to-[#0B1120]',
                'border' => 'border-rose-500',
                'border_color' => '#f43f5e',
                'text' => 'text-rose-300',
                'accent' => 'text-[#df747a]',
                'badge_bg' => 'bg-rose-950/80',
                'glow' => 'shadow-rose-500/35',
                'pill_bg' => 'bg-[#560E0F] text-rose-100 border-[#8F3033]',
            ],
            'elite', 'ultimate' => [
                'tier' => 'Elite',
                'bg_gradient' => 'from-amber-500 via-[#560E0F] to-violet-950',
                'border' => 'border-amber-300',
                'border_color' => '#fcd34d',
                'text' => 'text-amber-200',
                'accent' => 'text-amber-300',
                'badge_bg' => 'bg-amber-950/90',
                'glow' => 'shadow-amber-400/40',
                'pill_bg' => 'bg-gradient-to-r from-amber-600 to-[#560E0F] text-white border-amber-400',
            ],
            default => [ // Bronze (Levels 1-3)
                'tier' => 'Bronze',
                'bg_gradient' => 'from-amber-900 via-[#172033] to-[#0B1120]',
                'border' => 'border-amber-700/80',
                'border_color' => '#b45309',
                'text' => 'text-amber-400',
                'accent' => 'text-amber-500',
                'badge_bg' => 'bg-amber-950/60',
                'glow' => 'shadow-amber-800/20',
                'pill_bg' => 'bg-amber-950/60 text-amber-300 border-amber-800',
            ],
        };
    }

    /**
     * Find level instance for given cumulative EXP.
     */
    public static function forXp(int $xp): self
    {
        $level = static::where('min_xp', '<=', $xp)
            ->orderByDesc('level')
            ->first();

        if ($level) {
            return $level;
        }

        // Canonical fallback if DB is unseeded
        $canonical = collect(self::CANONICAL_LEVELS)
            ->filter(fn ($item) => $item['min_xp'] <= $xp)
            ->sortByDesc('level')
            ->first() ?? self::CANONICAL_LEVELS[1];

        return new static([
            'level' => $canonical['level'],
            'title' => $canonical['title'],
            'min_xp' => $canonical['min_xp'],
            'badge_icon' => $canonical['badge_icon'],
        ]);
    }

    /**
     * Return all 20 canonical level definitions merged with DB records if present.
     */
    public static function getCollection(): array
    {
        $dbLevels = static::orderBy('level')->get()->keyBy('level');

        $collection = [];
        foreach (self::CANONICAL_LEVELS as $lvlNum => $canon) {
            $dbRecord = $dbLevels->get($lvlNum);
            $collection[$lvlNum] = [
                'level' => $lvlNum,
                'title' => $dbRecord?->title ?? $canon['title'],
                'min_xp' => $dbRecord?->min_xp ?? $canon['min_xp'],
                'badge_name' => $canon['badge_name'],
                'badge_icon' => $dbRecord?->badge_icon ?? $canon['badge_icon'],
                'tier' => $canon['tier'],
            ];
        }

        return $collection;
    }
}
