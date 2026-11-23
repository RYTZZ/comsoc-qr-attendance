<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TriviaAutomationLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'action',
        'status',
        'week_key',
        'trivia_session_id',
        'summary',
        'metadata',
        'executed_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'executed_at' => 'datetime',
        ];
    }
}
