<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSheetIssuance extends Model
{
    use HasUuids;

    protected $fillable = [
        'event_id', 'issued_by', 'storage_path', 'pdf_sha256', 'attendee_count',
        'missing_signature_count', 'signature_refs', 'reissue_of_id', 'reissue_reason', 'issued_at',
    ];

    protected $casts = [
        'signature_refs' => 'array',
        'issued_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
