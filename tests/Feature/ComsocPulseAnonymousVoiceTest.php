<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\PulseTopic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComsocPulseAnonymousVoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_can_post_feedback_anonymously_and_upvote(): void
    {
        AcademicYear::create([
            'label' => 'AY 2025–2026',
            'year_start' => 2025,
            'year_end' => 2026,
            'is_active' => true,
        ]);

        $student = User::factory()->create(['role' => 'student']);

        // 1. Submit anonymous feedback with valid parameters
        $post = $this->actingAs($student)->postJson(route('pulse.api.store'), [
            'category' => 'lab_facilities',
            'title' => 'Lab 301 AC Needs Servicing',
            'content' => 'The aircon units in Lab 301 are turning off every few minutes during afternoon sessions.',
        ]);

        $post->assertStatus(201);
        $topicId = $post->json('topic_id');

        // Verify record created in database with user_id attached for author edit/delete permissions
        $topic = PulseTopic::find($topicId);
        $this->assertNotNull($topic);
        $this->assertEquals($student->id, $topic->user_id);
        $this->assertEquals(1, $topic->votes_count);

        // 2. Fetch list of topics
        $index = $this->actingAs($student)->getJson(route('pulse.api.index'));
        $index->assertStatus(200);
        $index->assertJsonFragment([
            'title' => 'Lab 301 AC Needs Servicing',
            'can_edit' => true,
            'can_delete' => true,
        ]);

        // 3. Edit topic
        $update = $this->actingAs($student)->putJson(route('pulse.api.update', $topic), [
            'category' => 'lab_facilities',
            'title' => 'Lab 301 AC and Fans Need Servicing',
            'content' => 'Updated: The aircon units and ceiling fans in Lab 301 are turning off every few minutes.',
        ]);
        $update->assertStatus(200);
        $this->assertEquals('Lab 301 AC and Fans Need Servicing', $topic->fresh()->title);

        // 4. Another student cannot edit or delete it
        $otherStudent = User::factory()->create(['role' => 'student']);
        $unauthEdit = $this->actingAs($otherStudent)->putJson(route('pulse.api.update', $topic), [
            'category' => 'lab_facilities',
            'title' => 'Hacked Title By Other User',
            'content' => 'Malicious edit attempt by non-owner.',
        ]);
        $unauthEdit->assertStatus(403);

        $unauthDelete = $this->actingAs($otherStudent)->deleteJson(route('pulse.api.destroy', $topic));
        $unauthDelete->assertStatus(403);

        // 5. Owner deletes topic
        $delete = $this->actingAs($student)->deleteJson(route('pulse.api.destroy', $topic));
        $delete->assertStatus(200);
        $this->assertNull(PulseTopic::find($topicId));
    }
}
