<?php

namespace App\Http\Controllers;

use App\Models\EventRegistration;
use App\Services\QrPngRenderer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberTicketController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $student = $user->student;

        $tickets = EventRegistration::with(['event.attendanceSessions'])
            ->where(function ($q) use ($user, $student) {
                $q->where('user_id', $user->id)
                  ->orWhere('email', strtolower($user->email));
                if ($student) {
                    $q->orWhere('email', strtolower($student->email));
                }
            })
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('tickets.index', compact('tickets'));
    }

    public function show(EventRegistration $ticket): View
    {
        $user = auth()->user();
        $student = $user->student;

        $canView = $ticket->user_id === $user->id
            || strtolower($ticket->email) === strtolower($user->email)
            || ($student && strtolower($ticket->email) === strtolower($student->email))
            || $user->isAdmin();

        abort_unless($canView, 403);

        $event = $ticket->event;
        $attendanceSessions = $event->attendanceSessions()->where('is_active', true)->orderBy('order')->get();

        $attendanceRecords = collect();
        if ($ticket->qr_token) {
            $attendanceRecords = $event->attendanceRecords()
                ->where(function ($q) use ($ticket, $student) {
                    $q->where('qr_token', $ticket->qr_token)
                      ->orWhere(function ($sub) use ($ticket) {
                          $sub->where('participant_type', 'non_student')
                              ->where('participant_id', $ticket->id);
                      });
                    if ($student) {
                        $subQueryStudent = function ($sub2) use ($student) {
                            $sub2->where('participant_type', 'student')
                                 ->where('participant_id', $student->id);
                        };
                        $q->orWhere($subQueryStudent);
                    }
                })
                ->get();
        }

        return view('tickets.show', compact('ticket', 'event', 'attendanceSessions', 'attendanceRecords'));
    }

    public function downloadQr(EventRegistration $ticket)
    {
        $user = auth()->user();
        $student = $user->student;

        $canView = $ticket->user_id === $user->id
            || strtolower($ticket->email) === strtolower($user->email)
            || ($student && strtolower($ticket->email) === strtolower($student->email))
            || $user->isAdmin();

        abort_unless($canView, 403);
        abort_unless($ticket->qr_token, 404);

        $qrImage = QrPngRenderer::generate($ticket->qr_token, 300, 1);
        $filename = 'ticket_' . $ticket->ticket_code . '.png';

        return response($qrImage)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
