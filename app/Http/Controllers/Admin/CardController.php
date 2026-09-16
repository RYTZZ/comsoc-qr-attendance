<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\QrCode;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CardController extends Controller
{
    public function index(Request $request): View
    {
        $cards = Card::with(['qrCode.membership.student', 'claimedByUser', 'reissuedByUser'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->batch, fn($q) => $q->whereHas('qrCode', fn($qr) => $qr->where('batch_number', $request->batch)))
            ->when($request->search, fn($q) => $q->whereHas('qrCode.membership.student', fn($s) =>
                $s->where('student_number', 'like', "%{$request->search}%")
                ->orWhere('last_name', 'ilike', "%{$request->search}%")
            ))
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.cards.index', compact('cards'));
    }

    public function markClaimed(Card $card): RedirectResponse
    {
        if ($card->status !== 'for_claiming') {
            return back()->with('error', 'Card is not in "For Claiming" status.');
        }

        $card->update([
            'status' => 'claimed',
            'claimed_by' => auth()->id(),
            'claimed_at' => now(),
        ]);

        AuditLogger::log('card.claimed', $card, ['status' => 'for_claiming'], ['status' => 'claimed']);

        return back()->with('success', 'Card marked as claimed.');
    }

    public function markLost(Request $request, Card $card): RedirectResponse
    {
        $request->validate(['notes' => 'nullable|string|max:500']);

        $card->update([
            'status' => 'lost',
            'notes' => $request->notes,
        ]);

        AuditLogger::log('card.lost', $card, ['status' => $card->getOriginal('status')], ['status' => 'lost']);

        return back()->with('success', 'Card marked as lost.');
    }

    public function reissue(Card $card): RedirectResponse
    {
        if (!in_array($card->status, ['lost', 'claimed'])) {
            return back()->with('error', 'Only lost or claimed cards can be reissued.');
        }

        $card->update([
            'status' => 'reissued',
            'reissued_by' => auth()->id(),
            'reissued_at' => now(),
        ]);

        AuditLogger::log('card.reissued', $card, [], ['status' => 'reissued', 'reissued_by' => auth()->id()]);

        return back()->with('success', 'Card reissue recorded.');
    }

    public function download(Request $request, Card $card)
    {
        $card->loadMissing(['qrCode.membership.student', 'qrCode.membership.academicYear']);
        $membership = $card->qrCode?->membership;

        abort_unless($membership, 404, 'Membership record not found for this card.');

        $format = strtolower($request->query('format', 'png'));
        $service = app(\App\Services\MemberCardService::class);
        $student = $membership->student;
        $baseName = sanitize_filename("{$student->student_number}_{$student->last_name}_card");

        if ($format === 'jpg' || $format === 'jpeg') {
            $data = $service->generateJpeg($membership);
            return response($data, 200, [
                'Content-Type' => 'image/jpeg',
                'Content-Disposition' => "attachment; filename=\"{$baseName}.jpg\"",
            ]);
        }

        if ($format === 'pdf') {
            $data = $service->generatePdf($membership);
            return response($data, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$baseName}.pdf\"",
            ]);
        }

        $data = $service->generatePng($membership);
        return response($data, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => "attachment; filename=\"{$baseName}.png\"",
        ]);
    }
}
