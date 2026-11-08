<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityRegistrationAnswer extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'activity_registration_id',
        'activity_session_registration_id',
        'registration_form_field_id',
        'field_key',
        'field_label',
        'question_type',
        'answer_text',
        'answer_json',
        'file_path',
        'file_original_name',
        'file_mime_type',
        'file_size',
    ];

    protected $casts = [
        'answer_json' => 'array',
        'file_size' => 'integer',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(ActivityRegistration::class, 'activity_registration_id');
    }

    public function sessionRegistration(): BelongsTo
    {
        return $this->belongsTo(ActivitySessionRegistration::class, 'activity_session_registration_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(RegistrationFormField::class, 'registration_form_field_id');
    }

    public function displayValue(): string
    {
        if ($this->question_type === 'file_upload') {
            return $this->file_original_name ?: ($this->file_path ? basename($this->file_path) : 'No file');
        }

        if (is_array($this->answer_json) && !empty($this->answer_json)) {
            return implode(', ', $this->answer_json);
        }

        return (string) ($this->answer_text ?? '');
    }
}

