<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Student;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\DynamicQrService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberIdentityExposureAndEndedEventTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->academicYear = AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'is_active' => true,
        ]);
    }

    /**
     * PRIORITY 1: Configured username displays with '@' format.
     */
    public function test_member_with_configured_username_displays_username_in_directory(): void
    {
        $student = Student::create([
            'student_number' => '24194844',
            'first_name' => 'Emmanuel',
            'last_name' => 'Santos',
            'email' => 'emmanuel@example.com',
            'program' => 'BSIT',
            'year_level' => '2nd Year',
        ]);

        $user = User::factory()->create([
            'name' => 'Emmanuel Santos',
            'username' => 'fraarroyo',
            'student_id' => $student->id,
            'student_number' => $student->student_number,
            'role' => 'student',
            'is_active' => true,
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'visibility' => 'public',
            'is_public' => true,
        ]);

        $this->assertEquals('@fraarroyo', $user->display_username);
        $this->assertEquals('fraarroyo', $user->raw_username);

        $viewer = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $response = $this->actingAs($viewer)->get(route('directory.index'));

        $response->assertOk();
        $response->assertSee('@fraarroyo');
        $response->assertDontSee('@24194844');
    }

    /**
     * PRIORITY 1: Missing username falls back to member's actual first name (no '@').
     */
    public function test_member_with_missing_username_falls_back_to_actual_first_name(): void
    {
        $student = Student::create([
            'student_number' => '26123132',
            'first_name' => 'Bernadett',
            'last_name' => 'Reyes',
            'email' => 'bernadett@example.com',
            'program' => 'BSCS',
            'year_level' => '1st Year',
        ]);

        $user = User::factory()->create([
            'name' => 'Bernadett Reyes',
            'username' => null,
            'student_id' => $student->id,
            'student_number' => $student->student_number,
            'role' => 'student',
            'is_active' => true,
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'visibility' => 'public',
            'is_public' => true,
        ]);

        $this->assertEquals('Bernadett', $user->display_username);
        $this->assertFalse(str_starts_with($user->display_username, '@'));
        $this->assertStringNotContainsString('26123132', $user->display_username);

        $viewer = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $response = $this->actingAs($viewer)->get(route('directory.index'));

        $response->assertOk();
        $response->assertSee('Bernadett');
        $response->assertDontSee('@26123132');
    }

    /**
     * PRIORITY 1: Blank or whitespace username falls back to actual first name.
     */
    public function test_member_with_blank_or_whitespace_username_falls_back_to_first_name(): void
    {
        $student = Student::create([
            'student_number' => '24182701',
            'first_name' => 'Edcin',
            'last_name' => 'Cruz',
            'email' => 'edcin@example.com',
            'program' => 'BSIT',
            'year_level' => '3rd Year',
        ]);

        $user = User::factory()->create([
            'name' => 'Edcin Cruz',
            'username' => '   ',
            'student_id' => $student->id,
            'student_number' => $student->student_number,
            'role' => 'student',
            'is_active' => true,
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'visibility' => 'public',
            'is_public' => true,
        ]);

        $this->assertEquals('Edcin', $user->display_username);
        $this->assertFalse(str_starts_with($user->display_username, '@'));
        $this->assertStringNotContainsString('24182701', $user->display_username);

        $viewer = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $response = $this->actingAs($viewer)->get(route('directory.index'));

        $response->assertOk();
        $response->assertSee('Edcin');
        $response->assertDontSee('@24182701');
    }

    /**
     * PRIORITY 1: Legacy student number stored in username field is never exposed as username.
     */
    public function test_legacy_student_number_in_username_column_falls_back_to_first_name(): void
    {
        $student = Student::create([
            'student_number' => '24194844',
            'first_name' => 'Emmanuel',
            'last_name' => 'Perez',
            'email' => 'emmanuel.perez@example.com',
            'program' => 'BSIT',
            'year_level' => '2nd Year',
        ]);

        // Simulated legacy account where username was set to student ID
        $user = User::factory()->create([
            'name' => 'Emmanuel Perez',
            'username' => '24194844',
            'student_id' => $student->id,
            'student_number' => $student->student_number,
            'role' => 'student',
            'is_active' => true,
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'visibility' => 'public',
            'is_public' => true,
        ]);

        $this->assertEquals('Emmanuel', $user->display_username);
        $this->assertStringNotContainsString('24194844', $user->display_username);

        $viewer = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $response = $this->actingAs($viewer)->get(route('directory.index'));

        $response->assertOk();
        $response->assertSee('Emmanuel');
        $response->assertDontSee('@24194844');
    }

    /**
     * PRIORITY 1: When neither username nor first name is available, display 'Username not set'.
     */
    public function test_member_without_username_and_without_first_name_displays_username_not_set(): void
    {
        $user = User::factory()->create([
            'name' => '',
            'username' => null,
            'student_id' => null,
            'student_number' => null,
            'role' => 'student',
            'is_active' => true,
        ]);

        $this->assertEquals('Username not set', $user->display_username);
        $this->assertNull($user->raw_username);
    }

    /**
     * PRIORITY 2: Ended event access pass (/tickets/{ticket}) blocks QR and displays ended notice.
     */
    public function test_ticket_show_pass_blocks_ended_event_and_displays_ended_state(): void
    {
        $student = Student::create([
            'student_number' => '2026-CS-0001',
            'first_name' => 'Alex',
            'last_name' => 'Tester',
            'email' => 'alex@example.com',
            'program' => 'BSCS',
            'year_level' => '2nd Year',
        ]);

        $user = User::factory()->create([
            'name' => 'Alex Tester',
            'email' => 'alex@example.com',
            'student_id' => $student->id,
            'role' => 'student',
            'is_active' => true,
            'is_eligible' => true,
        ]);

        $endedEvent = Event::create([
            'academic_year_id' => $this->academicYear->id,
            'created_by' => $user->id,
            'name' => 'Past Hackathon 2026',
            'event_date' => Carbon::parse('2026-10-22'),
            'ends_at' => Carbon::parse('2026-10-22 17:00:00'),
            'status' => 'completed',
            'is_published' => true,
        ]);

        $ticket = EventRegistration::create([
            'event_id' => $endedEvent->id,
            'user_id' => $user->id,
            'full_name' => $user->name,
            'email' => $user->email,
            'status' => 'approved',
            'ticket_code' => 'TCK-PAST1022',
        ]);

        $this->assertTrue($endedEvent->isEnded());

        // 1. Visit ticket page: QR container must NOT render, must display 'This event has ended.'
        $response = $this->actingAs($user)->get(route('tickets.show', $ticket));
        $response->assertOk();
        $response->assertSee('Event Ended');
        $response->assertSee('This event has ended. The attendance pass is no longer available.');
        $response->assertSee('Browse Events & Activities', false);
        $response->assertDontSee('id="event-qrcode-canvas"', false);

        // 2. Direct refresh API call: Must be rejected with 403 and EVENT_ENDED
        $refreshRes = $this->actingAs($user)->postJson(route('tickets.refresh_qr', $ticket));
        $refreshRes->assertStatus(403);
        $refreshRes->assertJson([
            'success' => false,
            'code' => 'EVENT_ENDED',
            'event_ended' => true,
            'message' => 'This event has ended. The attendance pass is no longer available.',
        ]);
    }

    /**
     * PRIORITY 2: Member dynamic QR view (/member/qr) displays ended state and blocks refresh.
     */
    public function test_member_dynamic_qr_blocks_ended_event_and_refresh(): void
    {
        $student = Student::create([
            'student_number' => '2026-CS-0002',
            'first_name' => 'Sam',
            'last_name' => 'Tester',
            'email' => 'sam@example.com',
            'program' => 'BSCS',
            'year_level' => '3rd Year',
        ]);

        $user = User::factory()->create([
            'name' => 'Sam Tester',
            'email' => 'sam@example.com',
            'student_id' => $student->id,
            'role' => 'student',
            'is_active' => true,
            'is_eligible' => true,
        ]);

        $endedEvent = Event::create([
            'academic_year_id' => $this->academicYear->id,
            'created_by' => $user->id,
            'name' => 'Past General Assembly',
            'event_date' => Carbon::now()->subDays(5)->toDateString(),
            'ends_at' => Carbon::now()->subDays(5)->setTime(18, 0),
            'status' => 'completed',
            'is_published' => true,
        ]);

        EventRegistration::create([
            'event_id' => $endedEvent->id,
            'user_id' => $user->id,
            'full_name' => $user->name,
            'email' => $user->email,
            'status' => 'approved',
            'ticket_code' => 'TCK-PAST-GEN',
        ]);

        // GET member dynamic QR pass
        $response = $this->actingAs($user)->get(route('member.dynamic_qr', ['event_id' => $endedEvent->id]));
        $response->assertOk();
        $response->assertSee('Event Ended');
        $response->assertDontSee('id="qrcode-canvas"', false);

        // POST refresh
        $refreshRes = $this->actingAs($user)->postJson(route('member.dynamic_qr.refresh'), [
            'event_id' => $endedEvent->id,
        ]);
        $refreshRes->assertStatus(403);
        $refreshRes->assertJsonFragment([
            'success' => false,
            'code' => 'EVENT_ENDED',
            'event_ended' => true,
            'message' => 'This event has ended. The attendance pass is no longer available.',
        ]);
    }
}
