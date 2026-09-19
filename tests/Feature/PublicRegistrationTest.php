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

    public function test_public_registration_page_reflects_registration_open_status(): void
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
            'name' => 'CICT Congress 2027',
            'academic_year_id' => $year->id,
            'created_by' => $user->id,
            'event_date' => Carbon::now()->addDays(2),
            'starts_at' => Carbon::now()->addDays(2)->setHour(13)->setMinute(24)->setSecond(0),
            'ends_at' => Carbon::now()->addDays(2)->setHour(13)->setMinute(24)->setSecond(0),
            'allow_non_students' => true,
            'is_published' => true,
            'status' => 'registration_open',
            'requires_registration' => true,
            'registration_deadline' => Carbon::now()->addDays(2)->setHour(21)->setMinute(25)->setSecond(0),
        ]);

        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Registration Open');
        $response->assertDontSee('Registration Not Open');
        $response->assertDontSee('Registration Closed');
        $response->assertSee('Time Left to Register');
        $response->assertSee('1:24 PM');
        $response->assertDontSee('1:24 PM — 1:24 PM');
    }

    public function test_public_registration_page_reflects_manually_closed_status_and_hides_countdown(): void
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
            'name' => 'CICT Congress 2027',
            'academic_year_id' => $year->id,
            'created_by' => $user->id,
            'event_date' => Carbon::now()->addDays(2),
            'allow_non_students' => true,
            'is_published' => true,
            'status' => 'registration_closed',
            'requires_registration' => true,
            'registration_deadline' => Carbon::now()->addDays(2),
        ]);

        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Registration Closed');
        $response->assertDontSee('Registration Open');
        $response->assertDontSee('Time Left to Register');
    }

    public function test_registration_open_without_deadline_remains_open(): void
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
            'name' => 'CICT Tech Meetup',
            'academic_year_id' => $year->id,
            'created_by' => $user->id,
            'event_date' => Carbon::now(),
            'starts_at' => Carbon::now()->subHours(1),
            'allow_non_students' => true,
            'is_published' => true,
            'status' => 'registration_open',
            'requires_registration' => true,
            'registration_deadline' => null,
        ]);

        $this->assertTrue($event->isRegistrationOpen());
        $this->assertEquals('Registration Open', $event->registration_status);
        $this->assertEquals('registration_open', $event->effective_status);

        $response = $this->get('/register');
        $response->assertStatus(200);
        $response->assertSee('Registration Open');
    }

    public function test_multi_step_registration_wizard_structure_and_indicators(): void
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
        $response->assertSee('1 Personal');
        $response->assertSee('2 Academic');
        $response->assertSee('3 Food & Review', false);
        $response->assertSee('x-show="currentStep === 1"', false);
        $response->assertSee('x-show="currentStep === 2"', false);
        $response->assertSee('x-show="currentStep === 3"', false);
        $response->assertSee('nextStep()', false);
        $response->assertSee('prevStep()', false);
    }

    public function test_admin_event_registrations_page_loads_with_uuid(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        $year = AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'semester' => '1st',
            'is_active' => true,
        ]);

        $uuid = '01a0b767-7d9b-72dc-927e-1a2ed4a0a46d';
        $event = new Event([
            'name' => 'ComSoc Tech Summit 2026',
            'academic_year_id' => $year->id,
            'created_by' => $admin->id,
            'event_date' => Carbon::now()->addDays(5),
            'allow_non_students' => true,
            'is_published' => true,
            'status' => 'published',
            'requires_registration' => true,
        ]);
        $event->id = $uuid;
        $event->save();

        $reg = EventRegistration::create([
            'event_id' => $event->id,
            'full_name' => 'Maria Santos',
            'email' => 'maria.santos@gmail.com',
            'organization' => 'Bulacan State University',
            'program' => 'Bachelor of Science in Information Technology (BSIT)',
            'year_level' => '3rd Year',
            'tshirt_size' => 'M',
            'food_restrictions' => 'None',
            'confirmed' => true,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get("/admin/events/{$uuid}/registrations");

        $response->assertStatus(200);
        $response->assertSee('ComSoc Tech Summit 2026');
        $response->assertSee('Maria Santos');
        $response->assertSee('maria.santos@gmail.com');
        $response->assertSee(route('admin.event-registrations.batch-approve'));
        $response->assertSee(route('admin.event-registrations.batch-reject'));

        $this->flushSession();
        \Illuminate\Support\Facades\Auth::logout();

        $unauthed = $this->get("/admin/events/{$uuid}/registrations");
        $unauthed->assertRedirect('/login');

        $student = \App\Models\User::factory()->create(['role' => 'student']);
        $forbidden = $this->actingAs($student)->get("/admin/events/{$uuid}/registrations");
        $forbidden->assertStatus(403);
    }
}
