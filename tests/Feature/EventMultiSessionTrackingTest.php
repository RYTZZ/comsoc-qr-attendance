<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Event;
use App\Models\EventTrack;
use App\Models\EventTrackAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventMultiSessionTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function getAcademicYear(): AcademicYear
    {
        return AcademicYear::firstOrCreate(
            ['label' => '2026-2027'],
            ['year_start' => 2026, 'year_end' => 2027, 'is_active' => true]
        );
    }

    public function test_members_can_view_tracks_and_register_for_workshops(): void
    {
        $ay = $this->getAcademicYear();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $studentUser = User::factory()->create(['role' => 'student', 'is_active' => true]);

        $event = Event::create([
            'name' => 'Annual CS Summit',
            'academic_year_id' => $ay->id,
            'created_by' => $admin->id,
            'description' => 'Summit description',
            'event_date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'venue' => 'ICT Hall',
            'status' => 'published',
            'is_published' => true,
        ]);

        $track = EventTrack::create([
            'event_id' => $event->id,
            'title' => 'Hands-on Machine Learning with PyTorch',
            'track_category' => 'AI & Data',
            'speaker_name' => 'Prof. Alan Turing',
            'room_or_venue' => 'Lab 401',
            'starts_at' => now()->addHours(2),
            'ends_at' => now()->addHours(4),
            'capacity_limit' => 30,
            'exp_points' => 75,
            'is_active' => true,
        ]);

        // 1. List tracks
        $res = $this->actingAs($studentUser)
            ->getJson(route('events.tracks.index', $event))
            ->assertOk()
            ->assertJsonStructure(['tracks']);

        $this->assertCount(1, $res->json('tracks'));
        $this->assertSame('Hands-on Machine Learning with PyTorch', $res->json('tracks.0.title'));

        // 2. Pre-register for workshop
        $this->actingAs($studentUser)
            ->postJson(route('events.tracks.register', $track))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'seats_remaining' => 29,
            ]);

        $this->assertDatabaseHas('event_track_attendances', [
            'event_track_id' => $track->id,
            'user_id' => $studentUser->id,
            'status' => 'registered',
        ]);

        // 3. Officer scans student into workshop
        $this->actingAs($admin)
            ->postJson(route('events.tracks.scan', $track), [
                'user_id' => $studentUser->id,
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('event_track_attendances', [
            'event_track_id' => $track->id,
            'user_id' => $studentUser->id,
            'status' => 'attended',
        ]);
    }
}

