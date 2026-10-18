<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventRegistrationFieldConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $studentUser;
    protected Student $student;
    protected AcademicYear $ay;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ay = AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'is_eligible' => true,
        ]);

        $this->student = Student::create([
            'student_number' => '21-00123',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Technology',
            'year_level' => '2nd Year',
        ]);

        $this->studentUser = User::factory()->create([
            'role' => 'student',
            'email' => 'juan@sorsu.edu.ph',
            'student_id' => $this->student->id,
            'is_active' => true,
            'is_eligible' => true,
        ]);
    }

    protected function createEvent(array $attributes = []): Event
    {
        return Event::create(array_merge([
            'academic_year_id' => $this->ay->id,
            'created_by' => $this->admin->id,
            'name' => 'Annual General Assembly',
            'event_type' => 'Assembly',
            'event_date' => now()->addDays(5)->toDateString(),
            'venue_name' => 'Campus Gym',
            'is_published' => true,
            'status' => 'registration_open',
            'requires_registration' => true,
            'capacity' => 100,
            'tshirt_enabled' => true,
            'tshirt_required' => false,
            'dietary_enabled' => true,
            'dietary_required' => false,
        ], $attributes));
    }

    public function test_admin_can_configure_registration_fields_independently(): void
    {
        $event = $this->createEvent();

        $response = $this->actingAs($this->admin)->post(route('admin.events.configure.save', $event), [
            'name' => $event->name,
            'academic_year_id' => $event->academic_year_id,
            'event_date' => $event->event_date->format('Y-m-d'),
            'status' => 'registration_open',
            'is_published' => '1',
            'requires_registration' => '1',
            'tshirt_enabled' => '1',
            'tshirt_required' => '1',
            'dietary_enabled' => '0',
            'dietary_required' => '0',
        ]);

        $response->assertRedirect(route('admin.events.configure', $event));
        $event->refresh();

        $this->assertTrue($event->tshirt_enabled);
        $this->assertTrue($event->tshirt_required);
        $this->assertFalse($event->dietary_enabled);
        $this->assertFalse($event->dietary_required);
    }

    public function test_unauthorized_user_cannot_change_event_configuration(): void
    {
        $event = $this->createEvent([
            'tshirt_enabled' => true,
            'tshirt_required' => false,
        ]);

        $response = $this->actingAs($this->studentUser)->post(route('admin.events.configure.save', $event), [
            'name' => $event->name,
            'academic_year_id' => $event->academic_year_id,
            'event_date' => $event->event_date->format('Y-m-d'),
            'tshirt_enabled' => '0',
            'tshirt_required' => '0',
        ]);

        $response->assertForbidden();
        $this->assertTrue($event->fresh()->tshirt_enabled);
    }

    public function test_disabled_fields_are_hidden_from_student_registration_and_pass(): void
    {
        $event = $this->createEvent([
            'tshirt_enabled' => false,
            'tshirt_required' => false,
            'dietary_enabled' => false,
            'dietary_required' => false,
        ]);

        $response = $this->actingAs($this->studentUser)->get(route('events.show', $event));
        $response->assertOk();
        $response->assertDontSee('T-Shirt Preference');
        $response->assertDontSee('Dietary Restrictions');
    }

    public function test_enabled_optional_fields_do_not_block_submission_when_empty(): void
    {
        $event = $this->createEvent([
            'tshirt_enabled' => true,
            'tshirt_required' => false,
            'dietary_enabled' => true,
            'dietary_required' => false,
        ]);

        $response = $this->actingAs($this->studentUser)->post(route('events.join', $event), [
            'tshirt_size' => '',
            'food_restrictions' => '',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event->id,
            'user_id' => $this->studentUser->id,
            'tshirt_size' => null,
            'food_restrictions' => null,
        ]);
    }

    public function test_enabled_required_fields_reject_empty_submissions_on_server(): void
    {
        $event = $this->createEvent([
            'tshirt_enabled' => true,
            'tshirt_required' => true,
            'dietary_enabled' => true,
            'dietary_required' => true,
        ]);

        $response = $this->actingAs($this->studentUser)->post(route('events.join', $event), [
            'tshirt_size' => '',
            'food_restrictions' => '',
        ]);

        $response->assertSessionHasErrors(['tshirt_size', 'food_restrictions']);
        $this->assertDatabaseMissing('event_registrations', [
            'event_id' => $event->id,
            'user_id' => $this->studentUser->id,
        ]);
    }

    public function test_each_event_uses_its_own_independent_configuration(): void
    {
        $eventA = $this->createEvent([
            'name' => 'Event A',
            'tshirt_enabled' => true,
            'tshirt_required' => true,
        ]);

        $eventB = $this->createEvent([
            'name' => 'Event B',
            'tshirt_enabled' => false,
            'tshirt_required' => false,
        ]);

        $this->assertTrue($eventA->tshirt_enabled);
        $this->assertTrue($eventA->tshirt_required);

        $this->assertFalse($eventB->tshirt_enabled);
        $this->assertFalse($eventB->tshirt_required);
    }

    public function test_existing_registration_data_remains_intact_when_fields_are_disabled(): void
    {
        $event = $this->createEvent([
            'tshirt_enabled' => true,
            'tshirt_required' => false,
        ]);

        $registration = EventRegistration::create([
            'event_id' => $event->id,
            'user_id' => $this->studentUser->id,
            'full_name' => $this->studentUser->name,
            'email' => $this->studentUser->email,
            'tshirt_size' => 'XL',
            'food_restrictions' => 'Vegetarian',
            'confirmed' => true,
            'status' => 'approved',
            'ticket_code' => 'TCK-TEST1234',
        ]);

        // Admin disables fields
        $this->actingAs($this->admin)->post(route('admin.events.configure.save', $event), [
            'name' => $event->name,
            'academic_year_id' => $event->academic_year_id,
            'event_date' => $event->event_date->format('Y-m-d'),
            'requires_registration' => '1',
            'tshirt_enabled' => '0',
            'tshirt_required' => '0',
            'dietary_enabled' => '0',
            'dietary_required' => '0',
        ]);

        $registration->refresh();
        $this->assertEquals('XL', $registration->tshirt_size);
        $this->assertEquals('Vegetarian', $registration->food_restrictions);
    }
}
