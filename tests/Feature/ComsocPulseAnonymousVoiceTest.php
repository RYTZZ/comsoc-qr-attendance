<?php

namespace Tests\Feature;

use App\Models\PulseTopic;
use App\Models\PulseVote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComsocPulseAnonymousVoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_can_post_feedback_anonymously_and_upvote(): void
    {
        $studentA = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $studentB = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        // 1. Student A posts anonymous feedback
        $res = $this->actingAs($studentA)
            ->postJson(route('pulse.api.store'), [
                'category' => 'lab_facilities',
                'title' => 'Lab 3 Router Network Drops During Practicals',
                'content' => 'The main network switch in Lab 3 disconnects every 15-20 minutes when 30 students are compiling code.',
            ])
            ->assertCreated();

        $topicId = $res->json('topic_id');
        $this->assertNotNull($topicId);

        $topic = PulseTopic::findOrFail($topicId);
        $this->assertSame('lab_facilities', $topic->category);
        $this->assertSame('under_review', $topic->status);
        $this->assertSame(1, $topic->votes_count);

        // Verify zero user_id is stored in PulseVote or PulseTopic
        $this->assertDatabaseMissing('pulse_votes', ['voter_hash' => $studentA->id]);

        // 2. Student B upvotes the topic
        $resVote = $this->actingAs($studentB)
            ->postJson(route('pulse.api.vote', $topic))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'action' => 'added',
                'votes_count' => 2,
                'has_voted' => true,
            ]);

        $this->assertSame(2, $topic->fresh()->votes_count);

        // 3. Officer responds officially
        $this->actingAs($admin)
            ->postJson(route('pulse.api.respond', $topic), [
                'status' => 'resolved',
                'official_response' => 'The university network administrator replaced the faulty switch in Lab 3 today.',
                'is_pinned' => true,
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'topic' => [
                    'status' => 'resolved',
                    'is_pinned' => true,
                ],
            ]);

        $this->assertSame('resolved', $topic->fresh()->status);
        $this->assertTrue($topic->fresh()->is_pinned);
    }
}
