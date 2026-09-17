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
        $event = Event::create([
            'academic_year_id' => $ay->id,
            'created_by' => $admin->id,
            'name' => 'CICT Congress 2027',
            'event_date' => '2026-09-19',
            'starts_at' => '2026-09-19 13:24:00',
            'ends_at' => '2026-09-19 13:24:00',
            'status' => 'draft',
            'is_published' => false,
            'requires_registration' => true,
            'allow_non_students' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.events.configure.save', $event), [
            'name' => 'CICT Congress 2027',
            'academic_year_id' => $ay->id,
            'event_date' => '2026-09-19',
            'starts_at' => '2026-09-19T13:24',
            'ends_at' => '2026-09-19T13:24',
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
}
