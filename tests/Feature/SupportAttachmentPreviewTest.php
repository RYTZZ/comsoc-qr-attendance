<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupportAttachmentPreviewTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private function makeTicket(User $owner): SupportTicket
    {
        return SupportTicket::create([
            'ticket_number' => 'TKT-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'user_id' => $owner->id,
            'category' => 'other',
            'subject' => 'Test ticket',
            'description' => 'Test description',
            'status' => 'open',
            'priority' => 'normal',
        ]);
    }

    private function makeMessage(string $path, string $content, string $name, ?SupportTicket $ticket = null): SupportTicketMessage
    {
        Storage::disk('local')->put($path, $content);
        $owner = User::factory()->create(['role' => 'student']);
        $ticket ??= $this->makeTicket($owner);

        return SupportTicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $ticket->user_id,
            'message' => 'See attached',
            'is_internal_note' => false,
            'attachment_path' => $path,
            'attachment_name' => $name,
        ]);
    }

    private function url(SupportTicketMessage $m, ?SupportTicket $t = null): string
    {
        return route('helpdesk.attachment.preview', [$t ?? $m->ticket, $m]);
    }

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'email_verified_at' => now()]);
    }

    public function test_super_admin_previews_image_inline(): void
    {
        $m = $this->makeMessage('private_support_attachments/t/a.png', base64_decode(self::PNG), 'IMG_0310.png');
        $r = $this->actingAs($this->superAdmin())->get($this->url($m));
        $r->assertOk();
        $this->assertSame('image/png', $r->headers->get('Content-Type'));
        $this->assertStringStartsWith('inline', $r->headers->get('Content-Disposition'));
        $this->assertStringNotContainsString('attachment', $r->headers->get('Content-Disposition'));
        $this->assertSame('nosniff', $r->headers->get('X-Content-Type-Options'));
    }

    public function test_super_admin_previews_pdf_inline(): void
    {
        $m = $this->makeMessage('private_support_attachments/t/a.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF", 'doc.pdf');
        $r = $this->actingAs($this->superAdmin())->get($this->url($m));
        $r->assertOk();
        $this->assertSame('application/pdf', $r->headers->get('Content-Type'));
        $this->assertStringStartsWith('inline', $r->headers->get('Content-Disposition'));
    }

    public function test_non_super_admins_are_forbidden(): void
    {
        $m = $this->makeMessage('private_support_attachments/t/b.png', base64_decode(self::PNG), 'b.png');
        foreach (['student', 'admin', 'staff'] as $role) {
            $u = User::factory()->create(['role' => $role, 'email_verified_at' => now()]);
            $this->actingAs($u)->get($this->url($m))->assertForbidden();
        }
        $owner = User::find($m->ticket->user_id);
        $this->actingAs($owner)->get($this->url($m))->assertForbidden();
        auth()->logout();
        $this->get($this->url($m))->assertRedirect();
    }

    public function test_message_from_other_ticket_is_not_exposed(): void
    {
        $m = $this->makeMessage('private_support_attachments/t/c.png', base64_decode(self::PNG), 'c.png');
        $other = $this->makeTicket(User::factory()->create(['role' => 'student']));
        $this->actingAs($this->superAdmin())->get($this->url($m, $other))->assertNotFound();
    }

    public function test_missing_file_returns_404(): void
    {
        $m = $this->makeMessage('private_support_attachments/t/d.png', base64_decode(self::PNG), 'd.png');
        Storage::disk('local')->delete($m->attachment_path);
        $this->actingAs($this->superAdmin())->get($this->url($m))->assertNotFound();
    }

    public function test_unsupported_type_and_spoofed_content_are_rejected(): void
    {
        $txt = $this->makeMessage('private_support_attachments/t/e.txt', 'hello', 'e.txt');
        $this->actingAs($this->superAdmin())->get($this->url($txt))->assertStatus(415);

        $spoof = $this->makeMessage('private_support_attachments/t/f.png', '<html><script>alert(1)</script></html>', 'f.png');
        $this->actingAs($this->superAdmin())->get($this->url($spoof))->assertStatus(415);
    }

    public function test_super_admin_page_shows_preview_button_instead_of_download(): void
    {
        $m = $this->makeMessage('private_support_attachments/t/g.png', base64_decode(self::PNG), 'IMG_0310.png');
        $r = $this->actingAs($this->superAdmin())->get(route('admin.support.show', $m->ticket));
        $r->assertOk();
        $r->assertSee('Preview IMG_0310.png');
        $r->assertDontSee('Download IMG_0310.png');
    }

    public function test_existing_download_policy_is_unchanged(): void
    {
        $m = $this->makeMessage('private_support_attachments/t/h.png', base64_decode(self::PNG), 'h.png');
        $stranger = User::factory()->create(['role' => 'student', 'email_verified_at' => now()]);
        $this->actingAs($stranger)->get(route('helpdesk.attachment', $m))->assertForbidden();
        $this->actingAs(User::find($m->ticket->user_id))->get(route('helpdesk.attachment', $m))->assertOk();
    }
}
