<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationsAndAnnouncementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_view_their_notifications(): void
    {
        $member = User::factory()->create([
            'role' => 'student',
        ]);

        $notif = NotificationService::send(
            $member,
            'event_registration',
            'Registration Confirmed: Hackathon 2026',
            'Your digital ticket QR is ready.',
            '/tickets'
        );

        $response = $this->actingAs($member)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('Registration Confirmed: Hackathon 2026');
        $response->assertSee('Your digital ticket QR is ready.');
    }

    public function test_member_can_mark_notification_as_read_and_filter_by_unread(): void
    {
        $member = User::factory()->create([
            'role' => 'student',
        ]);

        $notif1 = NotificationService::send($member, 'level_up', 'Level 2 Unlocked', 'Good job.');
        $notif2 = NotificationService::send($member, 'announcement', 'General Assembly Notice', 'All members attend.');

        $readResponse = $this->actingAs($member)->post(route('notifications.read', $notif1));
        $readResponse->assertRedirect();

        $this->assertNotNull($notif1->fresh()->read_at);
        $this->assertNull($notif2->fresh()->read_at);

        $unreadFilter = $this->actingAs($member)->get(route('notifications.index', ['filter' => 'unread']));
        $unreadFilter->assertOk();
        $unreadFilter->assertSee('General Assembly Notice');
        $unreadFilter->assertDontSee('Level 2 Unlocked');

        $readAllResponse = $this->actingAs($member)->post(route('notifications.read-all'));
        $readAllResponse->assertRedirect();

        $this->assertNotNull($notif2->fresh()->read_at);
    }

    public function test_member_cannot_access_or_modify_other_members_notifications(): void
    {
        $memberA = User::factory()->create(['role' => 'student']);
        $memberB = User::factory()->create(['role' => 'student']);

        $notifB = NotificationService::send($memberB, 'balance_adjusted', 'Secret adjustment', 'Points added.');

        $readAttempt = $this->actingAs($memberA)->post(route('notifications.read', $notifB));
        $readAttempt->assertForbidden();

        $deleteAttempt = $this->actingAs($memberA)->delete(route('notifications.destroy', $notifB));
        $deleteAttempt->assertForbidden();
    }

    public function test_member_can_browse_active_announcements(): void
    {
        $member = User::factory()->create(['role' => 'student']);

        $activeAnn = Announcement::create([
            'title' => 'Computing Society General Assembly 2026',
            'body' => 'Welcome to the semester general assembly! Check agenda and schedule.',
            'priority' => 'high',
            'category' => 'events',
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);

        $inactiveAnn = Announcement::create([
            'title' => 'Draft Unpublished Memo',
            'body' => 'Internal draft.',
            'priority' => 'normal',
            'category' => 'general',
            'is_active' => false,
        ]);

        $response = $this->actingAs($member)->get(route('announcements.index'));

        $response->assertOk();
        $response->assertSee('Computing Society General Assembly 2026');
        $response->assertDontSee('Draft Unpublished Memo');

        $detailResponse = $this->actingAs($member)->get(route('announcements.show', $activeAnn));
        $detailResponse->assertOk();
        $detailResponse->assertSee('Welcome to the semester general assembly!');
    }

    public function test_admin_can_publish_and_broadcast_announcements(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $studentA = User::factory()->create(['role' => 'student']);
        $studentB = User::factory()->create(['role' => 'student']);

        $postResponse = $this->actingAs($admin)->post(route('admin.announcements.store'), [
            'title' => 'Season 1 Grand Finals Schedule',
            'body' => 'Season 1 will officially conclude on Friday with XP rewards distributed.',
            'priority' => 'urgent',
            'category' => 'gamification',
            'is_active' => '1',
            'notify_members' => '1',
        ]);

        $postResponse->assertRedirect(route('admin.announcements.index'));

        $this->assertDatabaseHas('announcements', [
            'title' => 'Season 1 Grand Finals Schedule',
            'priority' => 'urgent',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $studentA->id,
            'type' => 'announcement',
            'title' => 'Announcement: Season 1 Grand Finals Schedule',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $studentB->id,
            'type' => 'announcement',
            'title' => 'Announcement: Season 1 Grand Finals Schedule',
        ]);

        $ann = Announcement::where('title', 'Season 1 Grand Finals Schedule')->first();

        $toggleResponse = $this->actingAs($admin)->post(route('admin.announcements.toggle-active', $ann));
        $toggleResponse->assertRedirect();

        $this->assertFalse($ann->fresh()->is_active);
    }
}
