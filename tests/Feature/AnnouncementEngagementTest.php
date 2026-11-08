<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\AnnouncementComment;
use App\Models\AnnouncementCommentReport;
use App\Models\AnnouncementReaction;
use App\Models\Notification;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementEngagementTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(string $firstName = 'John', string $lastName = 'Doe'): User
    {
        $user = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
        ]);

        Student::create([
            'user_id' => $user->id,
            'student_number' => 'SN-' . uniqid(),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'gender' => 'male',
            'program' => 'BSCS',
            'year_level' => 3,
            'block' => 'A',
            'is_active' => true,
        ]);

        return $user;
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    private function createAnnouncement(?User $poster = null): Announcement
    {
        return Announcement::create([
            'title' => 'Important General Assembly',
            'body' => 'We invite all Computing Society students to join our general assembly.',
            'priority' => 'high',
            'category' => 'general',
            'target_audience_type' => 'all',
            'is_active' => true,
            'published_at' => now()->subHour(),
            'posted_by' => $poster?->id,
        ]);
    }

    public function test_user_can_react_with_only_allowed_six_reactions(): void
    {
        $author = $this->createAdmin();
        $student = $this->createMember('Alice', 'Smith');
        $announcement = $this->createAnnouncement($author);

        $allowed = ['like', 'love', 'haha', 'wow', 'celebrate', 'support'];

        // 1. Invalid reaction rejected
        $this->actingAs($student)
            ->postJson(route('announcements.reactions.toggle', $announcement), [
                'reaction_type' => 'dislike',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reaction_type');

        // 2. React with 'love'
        $res = $this->actingAs($student)
            ->postJson(route('announcements.reactions.toggle', $announcement), [
                'reaction_type' => 'love',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'action' => 'added',
                'user_reaction' => 'love',
                'total_reactions' => 1,
            ]);

        $this->assertSame(1, AnnouncementReaction::where('announcement_id', $announcement->id)->count());

        // 3. Toggle same reaction removes it
        $this->actingAs($student)
            ->postJson(route('announcements.reactions.toggle', $announcement), [
                'reaction_type' => 'love',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'action' => 'removed',
                'user_reaction' => null,
                'total_reactions' => 0,
            ]);

        $this->assertSame(0, AnnouncementReaction::where('announcement_id', $announcement->id)->count());

        // 4. React with 'celebrate'
        $this->actingAs($student)
            ->postJson(route('announcements.reactions.toggle', $announcement), [
                'reaction_type' => 'celebrate',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'action' => 'added',
                'user_reaction' => 'celebrate',
            ]);

        // 5. Replace reaction with 'support'
        $this->actingAs($student)
            ->postJson(route('announcements.reactions.toggle', $announcement), [
                'reaction_type' => 'support',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'action' => 'changed',
                'user_reaction' => 'support',
                'total_reactions' => 1,
            ]);

        $this->assertSame(1, AnnouncementReaction::where('announcement_id', $announcement->id)->count());
        $this->assertSame('support', AnnouncementReaction::where('announcement_id', $announcement->id)->value('reaction_type'));

        // 6. Verify breakdown endpoint
        $resEngagement = $this->actingAs($student)
            ->getJson(route('announcements.engagement', $announcement))
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'user_reaction',
                'total_reactions',
                'breakdown' => [
                    'like', 'love', 'haha', 'wow', 'celebrate', 'support',
                ],
                'recent_reactors',
                'comments_count',
            ]);

        $this->assertSame('support', $resEngagement->json('user_reaction'));
        $this->assertSame(1, $resEngagement->json('total_reactions'));
        $this->assertSame(1, $resEngagement->json('breakdown.support.count'));
    }

    public function test_user_can_submit_edit_and_delete_comment(): void
    {
        $student = $this->createMember('Bob', 'Builder');
        $announcement = $this->createAnnouncement();

        // 1. Submit main comment
        $res = $this->actingAs($student)
            ->postJson(route('announcements.comments.store', $announcement), [
                'content' => 'Excited for this announcement!',
            ])
            ->assertCreated()
            ->assertJson([
                'success' => true,
                'comment' => [
                    'content' => 'Excited for this announcement!',
                    'status' => 'active',
                    'can_edit' => true,
                    'can_delete' => true,
                ],
            ]);

        $commentId = $res->json('comment.id');

        // 2. Edit own comment
        $this->actingAs($student)
            ->putJson(route('announcements.comments.update', $commentId), [
                'content' => 'Updated: Very excited for this assembly!',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'comment' => [
                    'content' => 'Updated: Very excited for this assembly!',
                ],
            ]);

        // 3. Other student cannot edit or delete it
        $otherStudent = $this->createMember('Charlie', 'Brown');
        $this->actingAs($otherStudent)
            ->putJson(route('announcements.comments.update', $commentId), [
                'content' => 'Hacked content',
            ])
            ->assertForbidden();

        $this->actingAs($otherStudent)
            ->deleteJson(route('announcements.comments.destroy', $commentId))
            ->assertForbidden();

        // 4. Owner deletes own comment
        $this->actingAs($student)
            ->deleteJson(route('announcements.comments.destroy', $commentId))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('announcement_comments', ['id' => $commentId]);
    }

    public function test_threaded_reply_system_enforces_one_level_nesting_and_prevents_orphans(): void
    {
        $studentA = $this->createMember('Student', 'One');
        $studentB = $this->createMember('Student', 'Two');
        $announcement = $this->createAnnouncement();

        // 1. Main comment
        $resMain = $this->actingAs($studentA)
            ->postJson(route('announcements.comments.store', $announcement), [
                'content' => 'Is there any attire required?',
            ])
            ->assertCreated();

        $mainId = $resMain->json('comment.id');

        // 2. Reply to main comment
        $resReply = $this->actingAs($studentB)
            ->postJson(route('announcements.comments.store', $announcement), [
                'parent_id' => $mainId,
                'content' => 'Please wear the official COMSOC organization shirt.',
            ])
            ->assertCreated()
            ->assertJson([
                'success' => true,
                'comment' => [
                    'parent_id' => $mainId,
                    'content' => 'Please wear the official COMSOC organization shirt.',
                ],
            ]);

        $replyId = $resReply->json('comment.id');

        // 3. Attempting to reply to a reply is rejected (only 1-level threading allowed)
        $this->actingAs($studentA)
            ->postJson(route('announcements.comments.store', $announcement), [
                'parent_id' => $replyId,
                'content' => 'Nested deeper than 1 level is forbidden.',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('parent_id');

        // 4. Deleting parent soft-deletes its replies to prevent orphaned threads
        $this->actingAs($studentA)
            ->deleteJson(route('announcements.comments.destroy', $mainId))
            ->assertOk();

        $this->assertSoftDeleted('announcement_comments', ['id' => $mainId]);
        $this->assertSoftDeleted('announcement_comments', ['id' => $replyId]);
    }

    public function test_comment_reporting_and_administrator_moderation(): void
    {
        $admin = $this->createAdmin();
        $studentA = $this->createMember('Inappropriate', 'User');
        $studentB = $this->createMember('Good', 'Citizen');
        $announcement = $this->createAnnouncement($admin);

        // Submit comment
        $resComment = $this->actingAs($studentA)
            ->postJson(route('announcements.comments.store', $announcement), [
                'content' => 'Some objectionable text here.',
            ])
            ->assertCreated();

        $commentId = $resComment->json('comment.id');

        // Student reports comment
        $this->actingAs($studentB)
            ->postJson(route('announcements.comments.report', $commentId), [
                'reason' => 'harassment',
                'details' => 'Offensive behavior',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('announcement_comment_reports', [
            'comment_id' => $commentId,
            'reporter_id' => $studentB->id,
            'reason' => 'harassment',
        ]);

        // Student cannot moderate
        $this->actingAs($studentB)
            ->postJson(route('announcements.comments.moderate', $commentId), [
                'action' => 'hide',
            ])
            ->assertForbidden();

        // Admin can hide comment
        $this->actingAs($admin)
            ->postJson(route('announcements.comments.moderate', $commentId), [
                'action' => 'hide',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'comment' => ['status' => 'hidden'],
            ]);

        $this->assertSame('hidden', AnnouncementComment::find($commentId)->status);

        // Admin can unhide
        $this->actingAs($admin)
            ->postJson(route('announcements.comments.moderate', $commentId), [
                'action' => 'unhide',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'comment' => ['status' => 'active'],
            ]);

        $this->assertSame('active', AnnouncementComment::find($commentId)->status);
    }

    public function test_mentionable_users_search_and_mention_notifications(): void
    {
        $sender = $this->createMember('Alice', 'Sender');
        $receiver = $this->createMember('Charlie', 'Receiver');
        $receiver->update(['username' => 'charlie_r']);
        $announcement = $this->createAnnouncement();

        // 1. Search mentionable users
        $resSearch = $this->actingAs($sender)
            ->getJson(route('announcements.mentionable-users', ['q' => 'Charlie']))
            ->assertOk()
            ->assertJsonStructure(['users']);

        $users = $resSearch->json('users');
        $this->assertNotEmpty($users);
        $this->assertSame($receiver->id, $users[0]['id']);

        // 2. Post comment with mention
        $content = "Hey @[Charlie Receiver](user:{$receiver->id}) check this out!";
        $resComment = $this->actingAs($sender)
            ->postJson(route('announcements.comments.store', $announcement), [
                'content' => $content,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $receiver->id,
            'type' => 'announcement_mention',
        ]);
    }
}

