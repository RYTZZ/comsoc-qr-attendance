<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    use HasFactory, HasUuids;

    public const YEAR_LEVELS = [
        '1st Year',
        '2nd Year',
        '3rd Year',
        '4th Year',
    ];

    protected $fillable = ['student_number', 'last_name', 'first_name', 'middle_name', 'program', 'year_level'];

    public static function normalizeYearLevel(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $trimmed = trim($raw);
        if ($trimmed === '') {
            return null;
        }

        $clean = strtolower($trimmed);
        $clean = preg_replace('/\s+/', ' ', $clean);

        if (in_array($clean, ['1', '1st', '1st year', '1st yr', 'first', 'first year', 'first yr', '1st yr.', 'yr 1', 'year 1'], true)) {
            return '1st Year';
        }

        if (in_array($clean, ['2', '2nd', '2nd year', '2nd yr', 'second', 'second year', 'second yr', '2nd yr.', 'yr 2', 'year 2'], true)) {
            return '2nd Year';
        }

        if (in_array($clean, ['3', '3rd', '3rd year', '3rd yr', 'third', 'third year', 'third yr', '3rd yr.', 'yr 3', 'year 3'], true)) {
            return '3rd Year';
        }

        if (in_array($clean, ['4', '4th', '4th year', '4th yr', 'fourth', 'fourth year', 'fourth yr', '4th yr.', 'yr 4', 'year 4'], true)) {
            return '4th Year';
        }

        if (in_array($trimmed, self::YEAR_LEVELS, true)) {
            return $trimmed;
        }

        return null;
    }

    public static function normalizeProgram(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $trimmed = trim($raw);
        if ($trimmed === '') {
            return null;
        }

        $clean = strtolower($trimmed);
        $clean = preg_replace('/\s+/', ' ', $clean);

        if ($clean === 'bsit' || str_contains($clean, 'information technology')) {
            return 'BSIT';
        }

        if ($clean === 'bscs' || str_contains($clean, 'computer science')) {
            return 'BSCS';
        }

        if ($clean === 'bsis' || str_contains($clean, 'information systems') || str_contains($clean, 'information system')) {
            return 'BSIS';
        }

        if ($clean === 'btvted' || str_contains($clean, 'technical-vocational') || str_contains($clean, 'technical vocational')) {
            return 'BTVTEd';
        }

        if ($clean === 'blis' || str_contains($clean, 'library and information science') || str_contains($clean, 'library & information science')) {
            return 'BLIS';
        }

        return $trimmed;
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function getFullNameAttribute(): string
    {
        $middle = $this->middle_name ? " {$this->middle_name}" : '';
        return "{$this->last_name}, {$this->first_name}{$middle}";
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function membershipForYear(string $academicYearId): ?Membership
    {
        return $this->memberships()->where('academic_year_id', $academicYearId)->first();
    }
}
