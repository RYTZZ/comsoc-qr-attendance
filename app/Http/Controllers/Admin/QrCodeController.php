<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Membership;
use App\Models\QrCode;
use App\Services\AuditLogger;
use App\Services\MemberCardService;
use App\Services\QrCodeService;
use App\Services\QrPngRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ZipArchive;

class QrCodeController extends Controller
{
    public function __construct(private QrCodeService $service) {}

    public function index(Request $request): View
    {
        $academicYears = AcademicYear::orderByDesc('year_start')->get();
        $activeYear = AcademicYear::active();

        $qrCodes = QrCode::with(['membership.student', 'membership.academicYear', 'card'])
            ->when($request->academic_year_id, fn($q) => $q->whereHas('membership', fn($m) => $m->where('academic_year_id', $request->academic_year_id)))
            ->when(!$request->academic_year_id && $activeYear, fn($q) => $q->whereHas('membership', fn($m) => $m->where('academic_year_id', $activeYear->id)))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->batch, fn($q) => $q->where('batch_number', $request->batch))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $filterYearId = $request->academic_year_id ?? $activeYear?->id;

        $missingQrCount = \App\Models\Membership::where('status', 'active')
            ->when($filterYearId, fn($q) => $q->where('academic_year_id', $filterYearId))
            ->whereDoesntHave('qrCodes', fn($q) => $q->where('status', 'active'))
            ->count();

        return view('admin.qr-codes.index', compact('qrCodes', 'academicYears', 'activeYear', 'missingQrCount', 'filterYearId'));
    }

    public function generate(Request $request): RedirectResponse
    {
        $activeYear = AcademicYear::active();
        $yearId = $request->academic_year_id ?? $activeYear?->id;

        if (!$yearId) {
            return back()->with('error', 'No active academic year configured.');
        }

        $academicYear = \App\Models\AcademicYear::findOrFail($yearId);

        $lastBatch = QrCode::whereHas('membership', fn($q) => $q->where('academic_year_id', $yearId))
            ->max('batch_number') ?? 0;
        $batchNumber = $lastBatch + 1;

        $memberships = Membership::where('academic_year_id', $yearId)
            ->where('status', 'active')
            ->whereDoesntHave('qrCodes', fn($q) => $q->where('status', 'active'))
            ->get();

        $generated = 0;
        $failed = 0;
        foreach ($memberships as $membership) {
            try {
                $this->service->generateForMembership($membership, $batchNumber);
                $generated++;
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        AuditLogger::log('qr.batch_generated', null, [], [
            'academic_year_id' => $yearId,
            'batch_number' => $batchNumber,
            'generated' => $generated,
            'failed' => $failed,
        ]);

        $batchLabel = str_pad($batchNumber, 3, '0', STR_PAD_LEFT);
        $message = "QR Generation Complete — Academic Year: {$academicYear->label} | Batch: {$batchLabel} | Generated: {$generated} | Already Existing: 0 | Failed: {$failed}";

        return back()->with('success', $message);
    }

    public function download(Request $request)
    {
        $activeYear = AcademicYear::active();
        $yearId = $request->academic_year_id ?? $activeYear?->id;

        if (!$yearId) {
            return back()->with('error', 'No active academic year configured.');
        }

        $batchNumber = $request->batch_number;
        if (!$batchNumber) {
            $batchNumber = QrCode::whereHas('membership', fn($q) => $q->where('academic_year_id', $yearId))
                ->max('batch_number');
        }

        if (!$batchNumber) {
            return back()->with('error', 'No QR batches found for this academic year.');
        }

        $qrCodes = QrCode::with(['membership.student', 'membership.academicYear', 'card'])
            ->whereHas('membership', fn($q) => $q->where('academic_year_id', $yearId))
            ->where('batch_number', $batchNumber)
            ->get();

        if ($qrCodes->isEmpty()) {
            return back()->with('error', 'No QR codes found for this batch.');
        }

        $zipPath = storage_path('app/private/qr_batch_' . $batchNumber . '_' . time() . '.zip');
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $cardService = app(MemberCardService::class);
        $format = strtolower($request->query('format', 'png'));

        foreach ($qrCodes as $qrCode) {
            $student = $qrCode->membership->student;
            $membership = $qrCode->membership;

            if ($format === 'jpg' || $format === 'jpeg') {
                $cardData = $cardService->generateJpeg($membership);
                $ext = 'jpg';
            } elseif ($format === 'pdf') {
                $cardData = $cardService->generatePdf($membership);
                $ext = 'pdf';
            } else {
                $cardData = $cardService->generatePng($membership);
                $ext = 'png';
            }

            $studentIdentifier = $student ? "{$student->student_number}_{$student->last_name}" : "membership_{$membership->id}";
            $filename = sanitize_filename("{$studentIdentifier}_card.{$ext}");
            $zip->addFromString($filename, $cardData);
        }

        $this->buildBatchMetadataCsv($zip, $qrCodes);
        $zip->close();

        AuditLogger::log('qr.batch_downloaded', null, [], [
            'academic_year_id' => $yearId,
            'batch_number' => $batchNumber,
            'count' => $qrCodes->count(),
        ]);

        return response()->download($zipPath, "qr_batch_{$batchNumber}.zip")->deleteFileAfterSend();
    }

    public function revoke(Request $request, QrCode $qrCode): RedirectResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);

        if (!$qrCode->isActive()) {
            return back()->with('error', 'This QR code is not active.');
        }

        $this->service->revoke($qrCode, $request->reason, auth()->user());

        AuditLogger::log('qr.revoked', $qrCode, ['status' => 'active'], [
            'status' => 'revoked',
            'reason' => $request->reason,
        ], $request->reason);

        return back()->with('success', 'QR code revoked.');
    }

    public function reissue(Request $request, QrCode $qrCode): RedirectResponse
    {
        if ($qrCode->isActive()) {
            return back()->with('error', 'QR code is still active. Revoke it first.');
        }

        $lastBatch = QrCode::whereHas('membership', fn($q) => $q->where('academic_year_id', $qrCode->membership->academic_year_id))
            ->max('batch_number') ?? 0;

        $newQr = $this->service->reissue($qrCode, $lastBatch + 1, auth()->user());

        AuditLogger::log('qr.reissued', $newQr, [], [
            'old_qr_id' => $qrCode->id,
            'new_qr_id' => $newQr->id,
            'membership_id' => $qrCode->membership_id,
        ]);

        return back()->with('success', 'New QR code issued.');
    }

    private function buildBatchMetadataCsv(ZipArchive $zip, $qrCodes): void
    {
        $lines = ["Batch Number,Student Name,Student Number,Membership Number,QR Status,QR Token (first 8)"];
        foreach ($qrCodes as $qr) {
            $student = $qr->membership?->student;
            $studentName = $student?->full_name ?? 'N/A';
            $studentNumber = $student?->student_number ?? 'N/A';
            $lines[] = implode(',', [
                $qr->batch_number,
                '"' . str_replace('"', '""', $studentName) . '"',
                $studentNumber,
                $qr->membership?->membership_number ?? '',
                $qr->status,
                substr($qr->token, 0, 8) . '...',
            ]);
        }
        $zip->addFromString('batch_metadata.csv', implode("\n", $lines));
    }
}
