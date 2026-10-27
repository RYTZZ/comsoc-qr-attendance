<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberSupportTicketReplyPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_staff_reply_persists_and_is_visible_to_ticket_owner(): void
    {
        // 1. Create student member and staff
        $studentUser = User::factory()->create([
            'role' => 'student',
            'username' => 'student_alice',
        ]);

        $staffUser = User::factory()->create([
            'role' => 'admin',
            'username' => 'officer_bob',
        ]);

        $otherStudent = User::factory()->create([
            'role' => 'student',
            'username' => 'student_charlie',
        ]);

        // 2. Student creates a support ticket
        $ticket = SupportTicket::create([
            'ticket_number' => 'TCK-2026-0001',
            'user_id' => $studentUser->id,
            'assigned_to' => $staffUser->id,
            'category' => 'event_attendance',
            'subject' => 'Missing Time-Out Record',
            'description' => 'I timed in at 8 AM but scanner battery died before time out.',
            'status' => 'open',
            'priority' => 'normal',
        ]);

        SupportTicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $studentUser->id,
            'message' => 'I timed in at 8 AM but scanner battery died before time out.',
            'is_internal_note' => false,
        ]);

        // 3. Staff submits a reply via admin endpoint
        $replyResponse = $this->actingAs($staffUser)->post(route('admin.support.reply', $ticket), [
            'message' => 'Hello Alice, we verified your attendance from the manual sheet and approved your time-out.',
            'is_internal_note' => 0,
            'shift_status' => 'in_review',
        ]);

        $replyResponse->assertSessionHas('status');

        // Verify message was saved in database
        $this->assertDatabaseHas('support_ticket_messages', [
            'support_ticket_id' => $ticket->id,
            'user_id' => $staffUser->id,
            'message' => 'Hello Alice, we verified your attendance from the manual sheet and approved your time-out.',
            'is_internal_note' => false,
        ]);

        // 4. Ticket owner views ticket conversation
        $viewResponse = $this->actingAs($studentUser)->get(route('helpdesk.show', $ticket));
        $viewResponse->assertOk();
        $viewResponse->assertSee('Hello Alice, we verified your attendance from the manual sheet and approved your time-out.');
        $viewResponse->assertSee('Staff');

        // 5. Subsequent refresh / new request retains the message
        $refreshResponse = $this->actingAs($studentUser)->get(route('helpdesk.show', $ticket));
        $refreshResponse->assertOk();
        $refreshResponse->assertSee('Hello Alice, we verified your attendance from the manual sheet and approved your time-out.');

        // 6. Another member CANNOT view this ticket (Authorization protection)
        $unauthorizedResponse = $this->actingAs($otherStudent)->get(route('helpdesk.show', $ticket));
        $unauthorizedResponse->assertStatus(403);
    }
}
