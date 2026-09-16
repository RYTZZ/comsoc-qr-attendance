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

    protected $fillable = ['student_number', 'last_name', 'first_name', 'middle_name', 'program', 'year_level'];

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
