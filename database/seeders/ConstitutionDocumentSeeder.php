<?php

namespace Database\Seeders;

use App\Models\OrganizationDocument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ConstitutionDocumentSeeder extends Seeder
{
    /**
     * Seed the official Computing Society Constitutional and By-Laws document.
     */
    public function run(): void
    {
        $relativePath = 'documents/cbl/computing-society-constitution-and-by-laws-v1.pdf';
        $disk = Storage::disk('local');

        // Check if the file exists on the local private storage
        $fullPath = storage_path('app/private/' . $relativePath);
        if (!file_exists($fullPath)) {
            $fallbackPath = storage_path('app/' . $relativePath);
            if (file_exists($fallbackPath)) {
                $fullPath = $fallbackPath;
            }
        }

        $fileSize = file_exists($fullPath) ? filesize($fullPath) : 387050;
        $fileHash = file_exists($fullPath) ? hash_file('sha256', $fullPath) : '04841ea99c8c424716b73f43f84920bf8eee8a7048311b683941825d99ea7439';

        OrganizationDocument::updateOrCreate(
            ['code' => OrganizationDocument::CODE_CBL],
            [
                'title' => 'COMSOC Constitutional and By-Laws',
                'description' => 'The official governing document establishing the organization\'s governing principles, policies, structure, and procedures for the Computing Society of Sorsogon State University Bulan Campus.',
                'file_path' => $relativePath,
                'original_filename' => 'COMPUTING SOCIETY CBL.pdf',
                'file_size' => $fileSize,
                'mime_type' => 'application/pdf',
                'file_hash' => $fileHash,
                'version' => 1,
                'is_published' => true,
                'published_at' => '2025-09-15 00:00:00', // Approved and amended date
            ]
        );
    }
}
