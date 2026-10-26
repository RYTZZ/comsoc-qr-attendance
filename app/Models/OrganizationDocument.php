<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class OrganizationDocument extends Model
{
    use HasFactory;

    public const CODE_CBL = 'cbl';

    protected $fillable = [
        'code',
        'title',
        'description',
        'file_path',
        'original_filename',
        'file_size',
        'mime_type',
        'file_hash',
        'version',
        'is_published',
        'published_at',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'version' => 'integer',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Check if the document file actually exists on disk.
     */
    public function fileExists(): bool
    {
        return !empty($this->file_path) && Storage::disk('local')->exists($this->file_path);
    }

    /**
     * Get human-readable file size format.
     */
    public function formattedFileSize(): string
    {
        $bytes = $this->file_size;
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        return number_format($bytes / (1024 ** $power), 2) . ' ' . $units[$power];
    }
}
