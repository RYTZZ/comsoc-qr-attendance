<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MissionSubmission extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'mission_id',
        'user_id',
        'submission_text',
        'proof_url',
        'file_path',
        'status',
        'review_notes',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public const PROOF_DIRECTORY = 'mission-proofs';
    public const PROOF_DISK = 'public';

    protected static function booted(): void
    {
        static::deleted(function (MissionSubmission $submission) {
            $path = $submission->file_path;
            if (!$path) {
                return;
            }

            DB::afterCommit(fn () => static::deleteProofFileIfUnreferenced($path));
        });
    }

    public static function isSafeProofPath(?string $path): bool
    {
        if (!$path || str_contains($path, '..') || str_contains($path, '\\') || str_starts_with($path, '/')) {
            return false;
        }

        return str_starts_with($path, self::PROOF_DIRECTORY . '/');
    }

    public static function deleteProofFileIfUnreferenced(?string $path): bool
    {
        if (!static::isSafeProofPath($path)) {
            return false;
        }

        if (static::where('file_path', $path)->exists()) {
            return false;
        }

        try {
            $disk = Storage::disk(self::PROOF_DISK);
            if ($disk->exists($path)) {
                return $disk->delete($path);
            }
        } catch (\Throwable $e) {
            Log::warning('Mission proof cleanup failed', ['exception' => get_class($e)]);
        }

        return false;
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
