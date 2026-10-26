<?php

namespace App\Http\Controllers;

use App\Models\OrganizationDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConstitutionController extends Controller
{
    /**
     * Display the member-facing Constitutional and By-Laws page.
     */
    public function index(): View
    {
        $document = OrganizationDocument::where('code', OrganizationDocument::CODE_CBL)->first();

        // Check if document is available and valid
        $isAvailable = $document && $document->is_published && $document->fileExists();

        return view('constitution.index', [
            'document' => $document,
            'isAvailable' => $isAvailable,
        ]);
    }

    /**
     * Stream the PDF inline for in-browser preview.
     * Enforces authentication and authorization.
     */
    public function preview(Request $request): Response|StreamedResponse
    {
        $document = OrganizationDocument::where('code', OrganizationDocument::CODE_CBL)->first();

        if (!$document || !$document->fileExists()) {
            abort(404, 'The requested document is not currently available on the server.');
        }

        // Ordinary members can only view published documents
        $user = $request->user();
        $canManage = $user && ($user->isSuperAdmin() || $user->isAdmin());

        if (!$document->is_published && !$canManage) {
            abort(403, 'The document is currently unpublished and cannot be accessed.');
        }

        $disk = Storage::disk('local');
        $fileStream = $disk->readStream($document->file_path);

        if (!$fileStream) {
            abort(500, 'Unable to open document stream.');
        }

        $filename = 'Computing-Society-Constitution-and-By-Laws-v' . $document->version . '.pdf';

        return response()->stream(function () use ($fileStream) {
            fpassthru($fileStream);
            if (is_resource($fileStream)) {
                fclose($fileStream);
            }
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, private, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Download the original PDF document with proper headers.
     */
    public function download(Request $request): StreamedResponse|Response
    {
        $document = OrganizationDocument::where('code', OrganizationDocument::CODE_CBL)->first();

        if (!$document || !$document->fileExists()) {
            abort(404, 'The requested document file was not found on the server.');
        }

        $user = $request->user();
        $canManage = $user && ($user->isSuperAdmin() || $user->isAdmin());

        if (!$document->is_published && !$canManage) {
            abort(403, 'The document is currently unpublished and cannot be downloaded.');
        }

        $disk = Storage::disk('local');
        $fileStream = $disk->readStream($document->file_path);

        if (!$fileStream) {
            abort(500, 'Unable to open file for download.');
        }

        $safeDownloadName = $document->original_filename ?: 'Computing-Society-Constitution-and-By-Laws.pdf';
        if (!str_ends_with(strtolower($safeDownloadName), '.pdf')) {
            $safeDownloadName .= '.pdf';
        }

        return response()->stream(function () use ($fileStream) {
            fpassthru($fileStream);
            if (is_resource($fileStream)) {
                fclose($fileStream);
            }
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . addslashes($safeDownloadName) . '"',
            'Content-Length' => (string) $document->file_size,
            'Cache-Control' => 'private, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
