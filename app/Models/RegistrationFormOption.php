<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationFormOption extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'registration_form_field_id',
        'label',
        'value',
        'sort_order',
        'is_enabled',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_enabled' => 'boolean',
    ];

    public function field(): BelongsTo
    {
        return $this->belongsTo(RegistrationFormField::class, 'registration_form_field_id');
    }
}

