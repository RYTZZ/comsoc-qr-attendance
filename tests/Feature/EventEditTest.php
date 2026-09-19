<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_editing_event_redirects_to_configure_page(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $ay = AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'semester' => '1st',
            'is_active' => true,
        ]);
        $event = Event::create([
            'academic_year_id' => $ay->id,
            'created_by' => $admin->id,
            'name' => 'Sample Event',
            'event_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.events.edit', $event));

        $response->assertRedirect(route('admin.events.configure', $event));
    }

    public function test_save_configuration_with_future_registration_opens_at(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $ay = AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'semester' => '1st',
            'is_active' => true,
        ]);
        $eventDate = now()->addDays(5)->format('Y-m-d');
        $startsAt = now()->addDays(5)->setTime(13, 0)->format('Y-m-d H:i:s');
        $endsAt = now()->addDays(5)->setTime(17, 0)->format('Y-m-d H:i:s');

        $event = Event::create([
            'academic_year_id' => $ay->id,
            'created_by' => $admin->id,
            'name' => 'CICT Congress 2027',
            'event_date' => $eventDate,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => 'draft',
            'is_published' => false,
            'requires_registration' => true,
            'allow_non_students' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.events.configure.save', $event), [
            'name' => 'CICT Congress 2027',
            'academic_year_id' => $ay->id,
            'event_date' => $eventDate,
            'starts_at' => now()->addDays(5)->setTime(13, 0)->format('Y-m-d\TH:i'),
            'ends_at' => now()->addDays(5)->setTime(17, 0)->format('Y-m-d\TH:i'),
            'registration_opens_at' => now()->addDays(1)->format('Y-m-d\TH:i'),
            'registration_deadline' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'status' => 'registration_open',
            'is_published' => '1',
            'requires_registration' => '1',
            'allow_non_students' => '1',
        ]);

        $response->assertRedirect(route('admin.events.configure', $event));
        $event->refresh();

        $this->assertEquals('registration_open', $event->status);
        $this->assertTrue($event->is_published);
        $this->assertEquals('registration_open', $event->effective_status);
        $this->assertEquals('Registration Open', $event->registration_status);
        $this->assertTrue($event->isRegistrationOpen());
    }

    public function test_save_configuration_rejects_inverted_operating_hours(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $ay = AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'semester' => '1st',
            'is_active' => true,
        ]);
        $event = Event::create([
            'academic_year_id' => $ay->id,
            'created_by' => $admin->id,
            'name' => 'Invalid Time Event',
            'event_date' => now()->addDays(2)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($admin)->from(route('admin.events.configure', $event))
            ->post(route('admin.events.configure.save', $event), [
                'name' => 'Invalid Time Event',
                'academic_year_id' => $ay->id,
                'event_date' => now()->addDays(2)->format('Y-m-d'),
                'starts_at' => now()->addDays(2)->setTime(17, 0)->format('Y-m-d\TH:i'),
                'ends_at' => now()->addDays(2)->setTime(8, 0)->format('Y-m-d\TH:i'),
            ]);

        $response->assertRedirect(route('admin.events.configure', $event));
        $response->assertSessionHasErrors(['ends_at']);
    }

    public function test_save_configuration_rejects_invalid_attendance_session_sequences(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $ay = AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'semester' => '1st',
            'is_active' => true,
        ]);
        $event = Event::create([
            'academic_year_id' => $ay->id,
            'created_by' => $admin->id,
            'name' => 'Attendance Sequence Test',
            'event_date' => now()->addDays(2)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($admin)->from(route('admin.events.configure', $event))
            ->post(route('admin.events.configure.save', $event), [
                'name' => 'Attendance Sequence Test',
                'academic_year_id' => $ay->id,
                'event_date' => now()->addDays(2)->format('Y-m-d'),
                'attendance_enabled' => '1',
                'sessions' => [
                    'morning_in' => [
                        'enabled' => '1',
                        'opens_at' => '11:00',
                        'closes_at' => '08:00',
                        'late_threshold' => '08:30',
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.events.configure', $event));
        $response->assertSessionHasErrors(['sessions.morning_in.closes_at']);
    }

    public function test_save_configuration_saves_attendance_sessions_accurately(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $ay = AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'semester' => '1st',
            'is_active' => true,
        ]);
        $event = Event::create([
            'academic_year_id' => $ay->id,
            'created_by' => $admin->id,
            'name' => 'Full Attendance Setup',
            'event_date' => now()->addDays(2)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($admin)->from(route('admin.events.configure', $event))
            ->post(route('admin.events.configure.save', $event), [
                'name' => 'Full Attendance Setup',
                'academic_year_id' => $ay->id,
                'event_date' => now()->addDays(2)->format('Y-m-d'),
                'starts_at' => now()->addDays(2)->setTime(8, 0)->format('Y-m-d H:i:s'),
                'ends_at' => now()->addDays(2)->setTime(17, 0)->format('Y-m-d H:i:s'),
                'venue_name' => 'SorSU Bulan Campus Social Hall',
                'venue_address' => 'Zone 8, Bulan, Sorsogon',
                'attendance_enabled' => '1',
                'sessions' => [
                    'morning_in' => [
                        'enabled' => '1',
                        'opens_at' => '08:00',
                        'closes_at' => '11:30',
                        'late_threshold' => '08:15',
                    ],
                    'morning_out' => [
                        'enabled' => '1',
                        'opens_at' => '11:30',
                        'closes_at' => '12:30',
                    ],
                    'afternoon_in' => [
                        'enabled' => '1',
                        'opens_at' => '12:30',
                        'closes_at' => '14:00',
                    ],
                    'afternoon_out' => [
                        'enabled' => '1',
                        'opens_at' => '16:30',
                        'closes_at' => '17:30',
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.events.configure', $event));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('attendance_sessions', [
            'event_id' => $event->id,
            'type' => 'morning_in',
            'opens_at' => '08:00',
            'closes_at' => '11:30',
            'late_threshold' => '08:15',
        ]);
        $this->assertDatabaseHas('attendance_sessions', [
            'event_id' => $event->id,
            'type' => 'afternoon_out',
            'opens_at' => '16:30',
            'closes_at' => '17:30',
        ]);
    }

    public function test_save_configuration_saves_afternoon_late_threshold_and_removes_disabled_sessions(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $ay = AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'semester' => '1st',
            'is_active' => true,
        ]);
        $event = Event::create([
            'academic_year_id' => $ay->id,
            'created_by' => $admin->id,
            'name' => 'Afternoon Late Test',
            'event_date' => now()->addDays(2)->format('Y-m-d'),
        ]);

        $event->attendanceSessions()->create([
            'type' => 'morning_in',
            'opens_at' => '08:00',
            'closes_at' => '11:00',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->from(route('admin.events.configure', $event))
            ->post(route('admin.events.configure.save', $event), [
                'name' => 'Afternoon Late Test',
                'academic_year_id' => $ay->id,
                'event_date' => now()->addDays(2)->format('Y-m-d'),
                'attendance_enabled' => '1',
                'sessions' => [
                    'morning_in' => [
                        'enabled' => '0',
                    ],
                    'afternoon_in' => [
                        'enabled' => '1',
                        'opens_at' => '13:00',
                        'closes_at' => '15:00',
                        'late_threshold' => '13:30',
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.events.configure', $event));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('attendance_sessions', [
            'event_id' => $event->id,
            'type' => 'morning_in',
        ]);

        $this->assertDatabaseHas('attendance_sessions', [
            'event_id' => $event->id,
            'type' => 'afternoon_in',
            'opens_at' => '13:00',
            'closes_at' => '15:00',
            'late_threshold' => '13:30',
        ]);
    }
}
