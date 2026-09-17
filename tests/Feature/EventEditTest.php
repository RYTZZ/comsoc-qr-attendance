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
}
