<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Event;
use App\Models\RewardRedemption;
use App\Models\SupportTicket;
use App\Models\SupportTicketHistory;
use App\Models\SupportTicketMessage;
use App\Models\XpTransaction;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MemberSupportTicketController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $status = $request->input('status', 'all');
        $category = $request->input('category', 'all');

        $query = SupportTicket::where('user_id', $user->id)
            ->with(['assignedStaff', 'event'])
            ->latest('updated_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($category !== 'all') {
            $query->where('category', $category);
        }

        $tickets = $query->paginate(10)->withQueryString();

        $stats = [
            'total' => SupportTicket::where('user_id', $user->id)->count(),
            'open' => SupportTicket::where('user_id', $user->id)->whereIn('status', ['open', 'in_review', 'awaiting_member'])->count(),
            'resolved' => SupportTicket::where('user_id', $user->id)->where('status', 'resolved')->count(),
            'closed' => SupportTicket::where('user_id', $user->id)->where('status', 'closed')->count(),
        ];

        return view('helpdesk.index', compact('tickets', 'stats', 'status', 'category'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $student = $user->student;

        // Populate selectable related entities for this user
        $events = Event::where('is_published', true)->orderByDesc('event_date')->take(20)->get();
        $recentXp = XpTransaction::where('user_id', $user->id)->orderByDesc('created_at')->take(20)->get();
        $recentRedemptions = RewardRedemption::where('user_id', $user->id)->with('reward')->orderByDesc('created_at')->take(10)->get();
        
        $recentAttendance = collect();
        if ($student) {
            $recentAttendance = AttendanceRecord::where('participant_id', $student->id)->with('event')->orderByDesc('scanned_at')->take(15)->get();
        }

        return view('helpdesk.create', compact('events', 'recentXp', 'recentRedemptions', 'recentAttendance'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'category' => ['required', 'string', 'in:' . implode(',', array_keys(SupportTicket::CATEGORIES))],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'priority' => ['nullable', 'string', 'in:low,normal,high,urgent'],
            'event_id' => ['nullable', 'uuid', 'exists:events,id'],
            'xp_transaction_id' => ['nullable', 'uuid', 'exists:xp_transactions,id'],
            'reward_redemption_id' => ['nullable', 'uuid', 'exists:reward_redemptions,id'],
            'attendance_record_id' => ['nullable', 'uuid', 'exists:attendance_records,id'],
            'attachment' => ['nullable', 'file', 'mimes:png,jpg,jpeg,pdf,txt', 'max:5120'], // 5MB max
        ]);

        return DB::transaction(function () use ($request, $user, $validated) {
            $ticketNumber = SupportTicket::generateTicketNumber();

            $ticket = SupportTicket::create([
                'ticket_number' => $ticketNumber,
                'user_id' => $user->id,
                'category' => $validated['category'],
                'subject' => $validated['subject'],
                'description' => $validated['description'],
                'priority' => $validated['priority'] ?? 'normal',
                'status' => 'open',
                'event_id' => $validated['event_id'] ?? null,
                'xp_transaction_id' => $validated['xp_transaction_id'] ?? null,
                'reward_redemption_id' => $validated['reward_redemption_id'] ?? null,
                'attendance_record_id' => $validated['attendance_record_id'] ?? null,
            ]);

            $attachmentPath = null;
            $attachmentName = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $attachmentName = $file->getClientOriginalName();
                $attachmentPath = $file->store('private_support_attachments/' . $ticket->id, 'local');
            }

            // Create initial message
            SupportTicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => $validated['description'],
                'is_internal_note' => false,
                'attachment_path' => $attachmentPath,
                'attachment_name' => $attachmentName,
            ]);

            // Create history audit entry
            SupportTicketHistory::create([
                'support_ticket_id' => $ticket->id,
                'actor_id' => $user->id,
                'action' => 'created',
                'from_status' => null,
                'to_status' => 'open',
                'notes' => 'Ticket submitted by member.',
            ]);

            return redirect()->route('helpdesk.show', $ticket)
                ->with('status', "Support ticket {$ticketNumber} has been logged successfully.");
        });
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        $user = $request->user();

        // Enforce member isolation: ordinary members cannot view other members' tickets
        if (!$user->isAdmin() && !$user->isStaff() && $ticket->user_id !== $user->id) {
            abort(403, 'Unauthorized access to support ticket.');
        }

        // Hide internal notes from ordinary members
        $isStaffOrAdmin = $user->isAdmin() || $user->isStaff() || $user->isSuperAdmin();
        $ticket->load(['assignedStaff', 'event', 'xpTransaction', 'rewardRedemption.reward', 'attendanceRecord.attendanceSession']);
        $messagesQuery = $ticket->messages()->with(['user.student']);
        if (!$isStaffOrAdmin) {
            $messagesQuery->where('is_internal_note', false);
        }
        $messages = $messagesQuery->orderBy('created_at', 'asc')->get();

        $histories = $ticket->histories()->with('actor')->orderBy('created_at', 'desc')->get();

        return view('helpdesk.show', compact('ticket', 'messages', 'histories', 'isStaffOrAdmin'));
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $user = $request->user();

        if (!$user->isAdmin() && !$user->isStaff() && $ticket->user_id !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        if ($ticket->status === 'closed' && !$user->isAdmin()) {
            return back()->withErrors(['message' => 'This ticket is closed. Please submit a new ticket or request an administrator to reopen it.']);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:png,jpg,jpeg,pdf,txt', 'max:5120'],
        ]);

        $attachmentPath = null;
        $attachmentName = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();
            $attachmentPath = $file->store('private_support_attachments/' . $ticket->id, 'local');
        }

        DB::transaction(function () use ($ticket, $user, $validated, $attachmentPath, $attachmentName) {
            SupportTicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => $validated['message'],
                'is_internal_note' => false,
                'attachment_path' => $attachmentPath,
                'attachment_name' => $attachmentName,
            ]);

            // If awaiting member response and member replied, shift back to in_review
            if ($ticket->status === 'awaiting_member' && $ticket->user_id === $user->id) {
                $ticket->update(['status' => 'in_review']);
                SupportTicketHistory::create([
                    'support_ticket_id' => $ticket->id,
                    'actor_id' => $user->id,
                    'action' => 'member_replied',
                    'from_status' => 'awaiting_member',
                    'to_status' => 'in_review',
                    'notes' => 'Status automatically shifted to In Review following member response.',
                ]);
            }
        });

        return back()->with('status', 'Your reply has been posted.');
    }

    public function downloadAttachment(Request $request, SupportTicketMessage $message): BinaryFileResponse
    {
        $user = $request->user();
        $ticket = $message->ticket;

        if (!$user->isAdmin() && !$user->isStaff() && $ticket->user_id !== $user->id) {
            abort(403, 'Unauthorized access to attachment.');
        }

        if ($message->is_internal_note && !$user->isAdmin() && !$user->isStaff()) {
            abort(403, 'Unauthorized access to internal attachment.');
        }

        if (!$message->attachment_path || !Storage::disk('local')->exists($message->attachment_path)) {
            abort(404, 'Attachment file not found.');
        }

        $fullPath = Storage::disk('local')->path($message->attachment_path);
        return response()->download($fullPath, $message->attachment_name ?: 'attachment');
    }
}
