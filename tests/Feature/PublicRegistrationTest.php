<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Organization;
use App\Models\Program;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_screen_can_be_rendered_without_auth(): void
    {
        $year = AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'semester' => '1st',
            'is_active' => true,
        ]);

        $user = \App\Models\User::factory()->create(['role' => 'superadmin']);

        $event = Event::create([
            'name' => 'ComSoc Tech Summit 2026',
            'academic_year_id' => $year->id,
            'created_by' => $user->id,
            'event_date' => Carbon::now()->addDays(5),
            'allow_non_students' => true,
            'is_published' => true,
            'status' => 'published',
            'requires_registration' => true,
            'registration_opens_at' => Carbon::now()->subDays(1),
            'registration_deadline' => Carbon::now()->addDays(2),
        ]);

        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('ComSoc Tech Summit 2026');
        $response->assertSee('Participant Registration');
    }

    public function test_non_student_can_register_via_canonical_register_url(): void
    {
        $year = AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'semester' => '1st',
            'is_active' => true,
        ]);

        $user = \App\Models\User::factory()->create(['role' => 'superadmin']);

        $event = Event::create([
            'name' => 'ComSoc Tech Summit 2026',
            'academic_year_id' => $year->id,
            'created_by' => $user->id,
            'event_date' => Carbon::now()->addDays(5),
            'allow_non_students' => true,
            'is_published' => true,
            'status' => 'published',
            'requires_registration' => true,
            'registration_opens_at' => Carbon::now()->subDays(1),
            'registration_deadline' => Carbon::now()->addDays(2),
        ]);

        $org = Organization::create([
            'name' => 'Bulacan State University',
            'is_active' => true,
        ]);

        $response = $this->post('/register', [
            'event_id' => $event->id,
            'full_name' => 'Maria Santos',
            'email' => 'maria.santos@gmail.com',
            'phone' => '09171234567',
            'organization' => $org->name,
            'program' => 'Bachelor of Science in Information Technology (BSIT)',
            'year_level' => '3rd Year',
            'tshirt_size' => 'M',
            'food_restrictions' => 'None',
            'confirmed' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event->id,
            'email' => 'maria.santos@gmail.com',
            'full_name' => 'Maria Santos',
        ]);
    }

    public function test_duplicate_registration_is_prevented(): void
    {
        $year = AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'semester' => '1st',
            'is_active' => true,
        ]);

        $user = \App\Models\User::factory()->create(['role' => 'superadmin']);

        $event = Event::create([
            'name' => 'ComSoc Tech Summit 2026',
            'academic_year_id' => $year->id,
            'created_by' => $user->id,
            'event_date' => Carbon::now()->addDays(5),
            'allow_non_students' => true,
            'is_published' => true,
            'status' => 'published',
            'requires_registration' => true,
            'registration_opens_at' => Carbon::now()->subDays(1),
            'registration_deadline' => Carbon::now()->addDays(2),
        ]);

        $org = Organization::create([
            'name' => 'Bulacan State University',
            'is_active' => true,
        ]);

        EventRegistration::create([
            'event_id' => $event->id,
            'full_name' => 'Maria Santos',
            'email' => 'maria.santos@gmail.com',
            'organization' => $org->name,
            'program' => 'Bachelor of Science in Information Technology (BSIT)',
            'year_level' => '3rd Year',
            'tshirt_size' => 'M',
            'food_restrictions' => 'None',
            'confirmed' => true,
            'status' => 'pending',
        ]);

        $response = $this->from('/register')->post('/register', [
            'event_id' => $event->id,
            'full_name' => 'Maria Santos',
            'email' => 'maria.santos@gmail.com',
            'phone' => '09171234567',
            'organization' => $org->name,
            'program' => 'Bachelor of Science in Information Technology (BSIT)',
            'year_level' => '3rd Year',
            'tshirt_size' => 'M',
            'food_restrictions' => 'None',
            'confirmed' => '1',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHas('error', 'You have already registered for this event.');
        $this->assertEquals(1, EventRegistration::where('email', 'maria.santos@gmail.com')->count());
    }
}
