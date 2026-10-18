<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SupportTicket;
use App\Models\SupportTicketHistory;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminSupportTicketController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->input('status', 'all');
        $category = $request->input('category', 'all');
        $priority = $request->input('priority', 'all');
        $assignedTo = $request->input('assigned_to', 'all');
        $search = $request->input('search');

        $query = SupportTicket::with(['user.student', 'assignedStaff', 'event'])
            ->latest('updated_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($category !== 'all') {
            $query->where('category', $category);
        }

        if ($priority !== 'all') {
            $query->where('priority', $priority);
        }

        if ($assignedTo === 'unassigned') {
            $query->whereNull('assigned_to');
        } elseif ($assignedTo !== 'all') {
            $query->where('assigned_to', $assignedTo);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('student_number', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $tickets = $query->paginate(15)->withQueryString();

        $staffUsers = User::whereIn('role', ['admin', 'comsoc_admin', 'super_admin', 'staff'])->orderBy('name')->get();

        $counts = [
            'open' => SupportTicket::where('status', 'open')->count(),
            'in_review' => SupportTicket::where('status', 'in_review')->count(),
            'awaiting_member' => SupportTicket::where('status', 'awaiting_member')->count(),
            'resolved' => SupportTicket::where('status', 'resolved')->count(),
            'closed' => SupportTicket::where('status', 'closed')->count(),
            'unassigned' => SupportTicket::whereNull('assigned_to')->whereNotIn('status', ['resolved', 'closed'])->count(),
        ];

        return view('admin.support.index', compact('tickets', 'staffUsers', 'counts', 'status', 'category', 'priority', 'assignedTo', 'search'));
    }

    public function show(SupportTicket $ticket): View
    {
        $ticket->load(['user.student', 'assignedStaff', 'resolvedByUser', 'event', 'xpTransaction', 'rewardRedemption.reward', 'attendanceRecord.attendanceSession']);
        $messages = $ticket->messages()->with('user')->get();
        $histories = $ticket->histories()->with('actor')->get();
        $staffUsers = User::whereIn('role', ['admin', 'comsoc_admin', 'super_admin', 'staff'])->orderBy('name')->get();

        return view('admin.support.show', compact('ticket', 'messages', 'histories', 'staffUsers'));
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:5000'],
            'is_internal_note' => ['nullable', 'boolean'],
            'shift_status' => ['nullable', 'string', 'in:open,in_review,awaiting_member,resolved,closed'],
            'attachment' => ['nullable', 'file', 'mimes:png,jpg,jpeg,pdf,txt', 'max:5120'],
        ]);

        $isInternal = $request->boolean('is_internal_note');
        $attachmentPath = null;
        $attachmentName = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();
            $attachmentPath = $file->store('private_support_attachments/' . $ticket->id, 'local');
        }

        DB::transaction(function () use ($ticket, $user, $validated, $isInternal, $attachmentPath, $attachmentName, $request) {
            SupportTicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => $validated['message'],
                'is_internal_note' => $isInternal,
                'attachment_path' => $attachmentPath,
                'attachment_name' => $attachmentName,
            ]);

            // If shifting status
            if ($request->filled('shift_status') && $request->shift_status !== $ticket->status) {
                $oldStatus = $ticket->status;
                $newStatus = $request->shift_status;

                $updateData = ['status' => $newStatus];
                if ($newStatus === 'resolved') {
                    $updateData['resolved_by'] = $user->id;
                    $updateData['resolved_at'] = now();
                } elseif ($newStatus === 'closed') {
                    $updateData['closed_at'] = now();
                }

                $ticket->update($updateData);

                SupportTicketHistory::create([
                    'support_ticket_id' => $ticket->id,
                    'actor_id' => $user->id,
                    'action' => 'status_changed',
                    'from_status' => $oldStatus,
                    'to_status' => $newStatus,
                    'notes' => $isInternal ? 'Status shifted via internal note.' : 'Status shifted via staff reply.',
                ]);
            }

            // Auto-assign to acting staff if currently unassigned
            if (!$ticket->assigned_to) {
                $ticket->update(['assigned_to' => $user->id]);
            }

            // Notify member if public reply
            if (!$isInternal) {
                NotificationService::send(
                    $ticket->user_id,
                    'support_ticket_reply',
                    "Update on Ticket #{$ticket->ticket_number}",
                    "COMSOC Staff replied to your support request: {$ticket->subject}",
                    route('helpdesk.show', $ticket)
                );
            }
        });

        return back()->with('status', $isInternal ? 'Internal note added.' : 'Public reply posted to student.');
    }

    public function assign(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'assigned_to' => ['required', 'uuid', 'exists:users,id'],
        ]);

        $assignee = User::findOrFail($validated['assigned_to']);
        $oldAssignee = $ticket->assignedStaff?->name ?? 'Unassigned';

        $ticket->update(['assigned_to' => $assignee->id]);

        SupportTicketHistory::create([
            'support_ticket_id' => $ticket->id,
            'actor_id' => auth()->id(),
            'action' => 'assigned',
            'from_status' => $ticket->status,
            'to_status' => $ticket->status,
            'notes' => "Reassigned from {$oldAssignee} to {$assignee->name}.",
        ]);

        return back()->with('status', "Ticket assigned to {$assignee->name}.");
    }

    public function resolve(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'resolution_code' => ['required', 'string', 'max:50'],
            'resolution_summary' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        DB::transaction(function () use ($ticket, $validated) {
            $ticket->update([
                'status' => 'resolved',
                'resolution_code' => $validated['resolution_code'],
                'resolution_summary' => $validated['resolution_summary'],
                'resolved_by' => auth()->id(),
                'resolved_at' => now(),
            ]);

            SupportTicketHistory::create([
                'support_ticket_id' => $ticket->id,
                'actor_id' => auth()->id(),
                'action' => 'resolved',
                'from_status' => 'in_review',
                'to_status' => 'resolved',
                'notes' => "Resolved [Code: {$validated['resolution_code']}]: {$validated['resolution_summary']}",
            ]);

            NotificationService::send(
                $ticket->user_id,
                'support_ticket_resolved',
                "Ticket #{$ticket->ticket_number} Resolved",
                "Your support request has been marked as resolved: {$validated['resolution_summary']}",
                route('helpdesk.show', $ticket)
            );
        });

        return back()->with('status', "Ticket #{$ticket->ticket_number} marked as resolved.");
    }

    public function escalate(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'escalation_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        DB::transaction(function () use ($ticket, $validated) {
            $ticket->update([
                'is_escalated' => true,
                'escalation_reason' => $validated['escalation_reason'],
                'priority' => 'urgent',
            ]);

            SupportTicketHistory::create([
                'support_ticket_id' => $ticket->id,
                'actor_id' => auth()->id(),
                'action' => 'escalated',
                'from_status' => $ticket->status,
                'to_status' => $ticket->status,
                'notes' => "Escalated to Super Admin: {$validated['escalation_reason']}",
            ]);
        });

        return back()->with('status', "Ticket escalated to Super Admin.");
    }
}
