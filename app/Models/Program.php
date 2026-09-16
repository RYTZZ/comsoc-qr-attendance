<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory, HasUuids;

    public const DEFAULTS = [
        [
            'name' => 'Bachelor of Science in Information Technology',
            'abbreviation' => 'BSIT',
            'display_name' => 'Bachelor of Science in Information Technology (BSIT)',
            'sort_order' => 1,
        ],
        [
            'name' => 'Bachelor of Science in Computer Science',
            'abbreviation' => 'BSCS',
            'display_name' => 'Bachelor of Science in Computer Science (BSCS)',
            'sort_order' => 2,
        ],
        [
            'name' => 'Bachelor of Science in Information Systems',
            'abbreviation' => 'BSIS',
            'display_name' => 'Bachelor of Science in Information Systems (BSIS)',
            'sort_order' => 3,
        ],
        [
            'name' => 'Bachelor of Technical-Vocational Teacher Education',
            'abbreviation' => 'BTVTEd',
            'display_name' => 'Bachelor of Technical-Vocational Teacher Education (BTVTEd)',
            'sort_order' => 4,
        ],
        [
            'name' => 'Bachelor of Library and Information Science',
            'abbreviation' => 'BLIS',
            'display_name' => 'Bachelor of Library and Information Science (BLIS)',
            'sort_order' => 5,
        ],
    ];

    protected $fillable = [
        'name',
        'abbreviation',
        'display_name',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public static function activeOptions(): array
    {
        if (static::count() === 0) {
            static::seedDefaults();
        }

        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('display_name')
            ->toArray();
    }

    public static function seedDefaults(): void
    {
        foreach (self::DEFAULTS as $item) {
            static::firstOrCreate(
                ['display_name' => $item['display_name']],
                [
                    'name' => $item['name'],
                    'abbreviation' => $item['abbreviation'],
                    'sort_order' => $item['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}

