<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrganizationDocument;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminConstitutionController extends Controller
{
    /**
     * Show the administrator management view for Constitutional and By-Laws.
     */
    public function index(): View
    {
        $document = OrganizationDocument::where('code', OrganizationDocument::CODE_CBL)
            ->with('uploader')
            ->first();

        return view('admin.constitution.index', [
            'document' => $document,
        ]);
    }

    /**
     * Upload a new document or replace the existing approved document.
     */
    public function upload(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'document' => [
                'required',
                'file',
                'mimes:pdf',
                'max:20480', // 20 MB max
            ],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $file = $request->file('document');

        // Verify PDF Magic Bytes (%PDF-) for strict validation
        $handle = fopen($file->getRealPath(), 'rb');
        $header = fread($handle, 5);
        fclose($handle);

        if ($header !== '%PDF-') {
            return back()->withInput()->withErrors([
                'document' => 'The uploaded file is not a valid PDF document.',
            ]);
        }

        $document = OrganizationDocument::where('code', OrganizationDocument::CODE_CBL)->first();
        $isReplacement = $document !== null;
        $oldValues = $document ? $document->toArray() : [];

        $version = $document ? ($document->version + 1) : 1;
        $uniqueFilename = 'cbl-v' . $version . '-' . Str::random(10) . '.pdf';
        $storedPath = $file->storeAs('documents/cbl', $uniqueFilename, 'local');

        $sha256 = hash_file('sha256', $file->getRealPath());
        $fileSize = $file->getSize();
        $originalName = $file->getClientOriginalName();
        $isPublished = $request->boolean('is_published', true);

        if ($document) {
            // Delete previous physical file if it exists and differs
            if (!empty($document->file_path) && Storage::disk('local')->exists($document->file_path)) {
                Storage::disk('local')->delete($document->file_path);
            }

            $document->update([
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'file_path' => $storedPath,
                'original_filename' => $originalName,
                'file_size' => $fileSize,
                'mime_type' => 'application/pdf',
                'file_hash' => $sha256,
                'version' => $version,
                'is_published' => $isPublished,
                'published_at' => $isPublished ? now() : null,
                'uploaded_by' => $request->user()->id,
            ]);
        } else {
            $document = OrganizationDocument::create([
                'code' => OrganizationDocument::CODE_CBL,
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'file_path' => $storedPath,
                'original_filename' => $originalName,
                'file_size' => $fileSize,
                'mime_type' => 'application/pdf',
                'file_hash' => $sha256,
                'version' => 1,
                'is_published' => $isPublished,
                'published_at' => $isPublished ? now() : null,
                'uploaded_by' => $request->user()->id,
            ]);
        }

        AuditLogger::log(
            $isReplacement ? 'constitution.replaced' : 'constitution.uploaded',
            $document,
            $oldValues,
            $document->fresh()->toArray(),
            $isReplacement ? "Replaced Constitutional and By-Laws to version {$version}." : "Initial upload of Constitutional and By-Laws."
        );

        return redirect()->route('admin.constitution.index')
            ->with('status', $isReplacement ? "Constitutional and By-Laws successfully updated to Version {$version}!" : "Constitutional and By-Laws successfully uploaded!");
    }

    /**
     * Update document metadata (title, description).
     */
    public function updateMetadata(Request $request): RedirectResponse
    {
        $document = OrganizationDocument::where('code', OrganizationDocument::CODE_CBL)->firstOrFail();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $oldValues = $document->only(['title', 'description']);
        $document->update($validated);

        AuditLogger::log(
            'constitution.metadata_updated',
            $document,
            $oldValues,
            $validated,
            'Updated Constitutional and By-Laws metadata.'
        );

        return redirect()->route('admin.constitution.index')
            ->with('status', 'Document details updated successfully.');
    }

    /**
     * Toggle document published status.
     */
    public function togglePublish(): RedirectResponse
    {
        $document = OrganizationDocument::where('code', OrganizationDocument::CODE_CBL)->firstOrFail();

        $oldStatus = $document->is_published;
        $newStatus = !$oldStatus;

        $document->update([
            'is_published' => $newStatus,
            'published_at' => $newStatus ? ($document->published_at ?? now()) : $document->published_at,
        ]);

        AuditLogger::log(
            $newStatus ? 'constitution.published' : 'constitution.unpublished',
            $document,
            ['is_published' => $oldStatus],
            ['is_published' => $newStatus],
            $newStatus ? 'Published Constitutional and By-Laws document.' : 'Unpublished Constitutional and By-Laws document.'
        );

        return redirect()->route('admin.constitution.index')
            ->with('status', $newStatus ? 'Document is now published and accessible to members.' : 'Document is now unpublished and hidden from members.');
    }
}
