<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSheetIssuance;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Builds FM-BUC-SCA-017 attendance sheets from existing attendance records. */
class AttendanceSheetService
{
    public const TEMPLATE_CODE = 'FM-BUC-SCA-017';
    public const EFFECTIVITY = 'October 19, 2026';
    public const REVISION = '03';

    public function __construct(private SignatureVaultService $vault) {}

    /**
     * Attendees come ONLY from existing attendance records (check-in rows); signature registration
     * never creates or filters attendees. Matching is by user id, never by array index.
     */
    public function rows(Event $event): Collection
    {
        // Existing flows write either participant_type 'user' (dynamic QR) or 'student' (kiosk); honour both.
        $records = AttendanceRecord::where('event_id', $event->id)->where('action', 'in')
            ->get(['participant_type', 'participant_id', 'user_id']);

        $studentIds = $records->where('participant_type', 'student')->pluck('participant_id')->unique()->values();
        $userByStudent = User::whereIn('student_id', $studentIds)->get()->keyBy('student_id');

        $keyed = [];
        foreach ($records as $rec) {
            if ($rec->participant_type === 'student') {
                $uid = $userByStudent->get($rec->participant_id)?->id ?? $rec->user_id;
                $key = $uid ?: 'student:' . $rec->participant_id;
                $keyed[$key] = ['user_id' => $uid, 'student_id' => $rec->participant_id];
            } else {
                $uid = $rec->user_id ?: $rec->participant_id;
                $keyed[$uid] = ['user_id' => $uid, 'student_id' => null];
            }
        }

        $userIds = collect($keyed)->pluck('user_id')->filter()->values();
        $users = User::with('student')->whereIn('id', $userIds)->get()->keyBy('id');
        $students = \App\Models\Student::whereIn('id', collect($keyed)->pluck('student_id')->filter())->get()->keyBy('id');
        $phones = EventRegistration::where('event_id', $event->id)->whereIn('user_id', $userIds)
            ->whereNotNull('phone')->pluck('phone', 'user_id');

        return collect($keyed)->map(function ($ref, $key) use ($users, $students, $phones) {
            $u = $ref['user_id'] ? $users->get($ref['user_id']) : null;
            $s = $u?->student ?? ($ref['student_id'] ? $students->get($ref['student_id']) : null);
            if (!$u && !$s) {
                return null;
            }
            $name = $s
                ? trim($s->last_name . ', ' . $s->first_name . ($s->middle_name ? ' ' . mb_substr($s->middle_name, 0, 1) . '.' : ''))
                : $u->name;
            $course = $s ? trim(implode(' ', array_filter([$s->program, $s->year_level])) . ($s->block ? ' - ' . $s->block : '')) : '';

            return [
                'key' => (string) $key,
                'user_id' => $u?->id,
                'name' => $name,
                'position' => $u?->officer_position ?: 'Member',
                'course' => $course,
                'contact' => $u ? $phones->get($u->id, '') : '',
            ];
        })->filter()->sortBy(fn ($r) => mb_strtolower($r['name']))->values();
    }


    /** @return array{rows: Collection, missing: Collection, signatures: Collection} */
    public function check(Event $event): array
    {
        $rows = $this->rows($event);
        $signatures = $this->vault->activeFor($rows->pluck('user_id'));
        $missing = $rows->reject(fn ($r) => $r['user_id'] && $signatures->has($r['user_id']))->values();

        return compact('rows', 'missing', 'signatures');
    }

    public function activityInfo(Event $event): array
    {
        $date = $event->event_date?->format('F j, Y');
        $time = null;
        if ($event->starts_at) {
            $time = $event->starts_at->format('g:i A') . ($event->ends_at ? ' - ' . $event->ends_at->format('g:i A') : '');
        }
        $venue = collect([$event->venue_name, $event->venue_address])->filter()->implode(', ') ?: ($event->location ?: '');

        return [
            'organization' => $event->organizer ?: config('app.name'),
            'title' => $event->name,
            'datetime' => trim(($date ?? '') . ($time ? ', ' . $time : '')),
            'venue' => $venue,
        ];
    }

    /** Decrypt signatures in memory only and assemble template data (matched by user id). */
    public function viewData(Event $event, Collection $rows, Collection $signatures): array
    {
        $rows = $rows->map(function ($r) use ($signatures) {
            $sig = $r['user_id'] ? $signatures->get($r['user_id']) : null;
            $png = $sig ? $this->vault->decrypt($sig) : null;
            $r['signature'] = $png ? 'data:image/png;base64,' . base64_encode($png) : null;
            unset($png);
            return $r;
        });

        $asset = fn (string $f) => 'data:image/png;base64,' . base64_encode(file_get_contents(resource_path("pdf-assets/sca017/$f")));

        return [
            'info' => $this->activityInfo($event),
            'rows' => $rows,
            'logoLeft' => $asset('logo-left.png'),
            'logoRight' => $asset('logo-right.png'),
            'banner' => $asset('footer-banner.png'),
            'code' => self::TEMPLATE_CODE,
            'effectivity' => self::EFFECTIVITY,
            'revision' => self::REVISION,
        ];
    }

    /** Render the PDF. Returns raw PDF bytes. */
    public function render(Event $event, Collection $rows, Collection $signatures): string
    {
        $pdf = Pdf::loadView('pdf.attendance-sheet', $this->viewData($event, $rows, $signatures))
            ->setPaper([0, 0, 612, 936]); // 8.5 x 13 in, as in the DOCX (w:pgSz 12240x18720)

        $pdf->render();
        $canvas = $pdf->getDomPDF()->getCanvas();
        $font = $pdf->getDomPDF()->getFontMetrics()->getFont('helvetica');
        $canvas->page_text(474, 843, '{PAGE_NUM} of {PAGE_COUNT}', $font, 8, [0, 0, 0]);

        return $pdf->output();
    }

    public function latestIssuance(Event $event): ?AttendanceSheetIssuance
    {
        return AttendanceSheetIssuance::where('event_id', $event->id)->latest('issued_at')->first();
    }

    /** Generate, archive (encrypted, private disk) and record an issuance. */
    public function issue(Event $event, User $by, ?string $reissueReason = null, ?AttendanceSheetIssuance $previous = null): array
    {
        ['rows' => $rows, 'missing' => $missing, 'signatures' => $signatures] = $this->check($event);
        $pdf = $this->render($event, $rows, $signatures);

        $path = 'attendance-sheets/' . $event->id . '/' . (string) \Illuminate\Support\Str::uuid() . '.pdf.enc';
        $disk = Storage::disk(config('signatures.archive_disk'));

        $issuance = DB::transaction(function () use ($event, $by, $pdf, $path, $rows, $missing, $signatures, $reissueReason, $previous, $disk) {
            $disk->put($path, $this->vault->encryptBytes($pdf));

            return AttendanceSheetIssuance::create([
                'event_id' => $event->id,
                'issued_by' => $by->id,
                'storage_path' => $path,
                'pdf_sha256' => hash('sha256', $pdf),
                'attendee_count' => $rows->count(),
                'missing_signature_count' => $missing->count(),
                'signature_refs' => $rows->mapWithKeys(fn ($r) => [$r['key'] => $r['user_id'] ? $signatures->get($r['user_id'])?->id : null])->all(),
                'reissue_of_id' => $previous?->id,
                'reissue_reason' => $reissueReason,
                'issued_at' => now(),
            ]);
        });

        return [$issuance, $pdf, $missing];
    }

    /** Read the archived PDF, verifying its recorded hash. Null if missing/tampered/undecryptable. */
    public function readArchive(AttendanceSheetIssuance $issuance): ?string
    {
        $disk = Storage::disk(config('signatures.archive_disk'));
        if (!$disk->exists($issuance->storage_path)) {
            return null;
        }
        $pdf = $this->vault->decryptBytes($disk->get($issuance->storage_path));

        return ($pdf !== null && hash_equals($issuance->pdf_sha256, hash('sha256', $pdf))) ? $pdf : null;
    }
}
