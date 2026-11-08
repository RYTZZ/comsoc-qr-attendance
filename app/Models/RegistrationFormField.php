<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegistrationFormField extends Model
{
    use HasFactory, HasUuids;

    public const QUESTION_TYPES = [
        'short_answer' => 'Short Answer',
        'paragraph' => 'Paragraph',
        'dropdown' => 'Dropdown / Select',
        'multiple_choice' => 'Multiple Choice',
        'checkboxes' => 'Checkboxes',
        'number' => 'Number',
        'date' => 'Date',
        'file_upload' => 'File Upload',
        'yes_no' => 'Yes / No',
    ];

    public const OPTION_TYPES = [
        'dropdown',
        'multiple_choice',
        'checkboxes',
    ];

    protected $fillable = [
        'registration_form_id',
        'field_key',
        'label',
        'help_text',
        'placeholder',
        'question_type',
        'is_required',
        'is_enabled',
        'sort_order',
        'validation_rules',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_enabled' => 'boolean',
        'sort_order' => 'integer',
        'validation_rules' => 'array',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(RegistrationForm::class, 'registration_form_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(RegistrationFormOption::class, 'registration_form_field_id')->orderBy('sort_order');
    }

    public function supportsOptions(): bool
    {
        return in_array($this->question_type, self::OPTION_TYPES, true);
    }
}

