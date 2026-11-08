<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegistrationForm extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    public const CATEGORIES = [
        'Workshop',
        'Multi-Workshop Event',
        'IDEATHON',
        'Hackathon',
        'Seminar',
        'Bootcamp',
        'Competition',
        'Industry Familiarization Tour',
        'General COMSOC Activity',
    ];

    protected $fillable = [
        'title',
        'slug',
        'description',
        'intended_categories',
        'status',
        'version',
        'published_snapshot',
        'is_template',
        'parent_form_id',
        'created_by',
        'updated_by',
        'published_at',
        'archived_at',
    ];

    protected $casts = [
        'intended_categories' => 'array',
        'version' => 'integer',
        'published_snapshot' => 'array',
        'is_template' => 'boolean',
        'published_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function parentForm(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_form_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(RegistrationFormField::class, 'registration_form_id')->orderBy('sort_order');
    }

    public function enabledFields(): HasMany
    {
        return $this->hasMany(RegistrationFormField::class, 'registration_form_id')
            ->where('is_enabled', true)
            ->orderBy('sort_order');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'registration_form_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ActivitySession::class, 'registration_form_id');
    }

    public function buildSnapshot(): array
    {
        $this->loadMissing(['fields.options']);

        return [
            'form_id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'version' => $this->version,
            'captured_at' => now()->toIso8601String(),
            'fields' => $this->fields
                ->where('is_enabled', true)
                ->sortBy('sort_order')
                ->values()
                ->map(fn (RegistrationFormField $field) => [
                    'id' => $field->id,
                    'field_key' => $field->field_key,
                    'label' => $field->label,
                    'help_text' => $field->help_text,
                    'placeholder' => $field->placeholder,
                    'question_type' => $field->question_type,
                    'is_required' => (bool) $field->is_required,
                    'is_enabled' => (bool) $field->is_enabled,
                    'sort_order' => (int) $field->sort_order,
                    'validation_rules' => $field->validation_rules ?? [],
                    'options' => $field->options
                        ->where('is_enabled', true)
                        ->sortBy('sort_order')
                        ->values()
                        ->map(fn (RegistrationFormOption $opt) => [
                            'id' => $opt->id,
                            'label' => $opt->label,
                            'value' => $opt->value,
                            'sort_order' => (int) $opt->sort_order,
                        ])->all(),
                ])->all(),
        ];
    }

    public function getActiveSchema(): array
    {
        if (!empty($this->published_snapshot['fields'])) {
            return $this->published_snapshot;
        }

        return $this->buildSnapshot();
    }
}

