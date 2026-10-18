<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAnnouncementCreationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $studentBsit;
    protected User $studentBsis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $studentA = Student::create([
            'student_number' => '21-00101',
            'first_name' => 'John',
            'last_name' => 'IT',
            'email' => 'john.it@sorsu.edu.ph',
            'program' => 'BSIT',
            'year_level' => '3',
            'block' => 'A',
        ]);

        $this->studentBsit = User::factory()->create([
            'role' => 'student',
            'student_id' => $studentA->id,
            'is_active' => true,
        ]);

        $studentB = Student::create([
            'student_number' => '21-00102',
            'first_name' => 'Jane',
            'last_name' => 'IS',
            'email' => 'jane.is@sorsu.edu.ph',
            'program' => 'BSIS',
            'year_level' => '2',
            'block' => 'B',
        ]);

        $this->studentBsis = User::factory()->create([
            'role' => 'student',
            'student_id' => $studentB->id,
            'is_active' => true,
        ]);
    }

    public function test_can_create_announcement_for_all_members(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.announcements.store'), [
            'title' => 'General Assembly 2026',
            'body' => 'Welcome to all computing society members for the annual assembly.',
            'priority' => 'normal',
            'category' => 'general',
            'target_audience_type' => 'all',
            'target_audience_values' => [''],
        ]);

        $response->assertRedirect(route('admin.announcements.index'));
        $this->assertDatabaseHas('announcements', [
            'title' => 'General Assembly 2026',
            'target_audience_type' => 'all',
        ]);

        $announcement = Announcement::where('title', 'General Assembly 2026')->first();
        $this->assertTrue($announcement->isAudienceEligible($this->studentBsit));
        $this->assertTrue($announcement->isAudienceEligible($this->studentBsis));
    }

    public function test_can_create_announcement_with_empty_target_audience_values_array_without_validation_error(): void
    {
        // This reproduces the exact bug where form submitted [null] or [''] as target_audience_values
        $response = $this->actingAs($this->admin)->post(route('admin.announcements.store'), [
            'title' => 'Tech Workshop',
            'body' => 'Hands on open session for everyone.',
            'priority' => 'high',
            'category' => 'technical',
            'target_audience_type' => 'all',
            'target_audience_values' => [null],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.announcements.index'));
        $this->assertDatabaseHas('announcements', [
            'title' => 'Tech Workshop',
        ]);
    }

    public function test_can_create_announcement_for_specific_program(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.announcements.store'), [
            'title' => 'BSIT Capstone Orientation',
            'body' => 'Notice for BSIT students regarding capstone milestones.',
            'priority' => 'urgent',
            'category' => 'general',
            'target_audience_type' => 'program',
            'target_audience_values' => ['BSIT'],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.announcements.index'));

        $announcement = Announcement::where('title', 'BSIT Capstone Orientation')->first();
        $this->assertNotNull($announcement);
        $this->assertEquals('program', $announcement->target_audience_type);
        $this->assertEquals(['BSIT'], $announcement->target_audience_values);

        $this->assertTrue($announcement->isAudienceEligible($this->studentBsit));
        $this->assertFalse($announcement->isAudienceEligible($this->studentBsis));
    }

    public function test_can_create_announcement_for_multiple_programs_via_comma_separated_string_or_array(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.announcements.store'), [
            'title' => 'IT and IS Joint Seminar',
            'body' => 'Joint curriculum symposium.',
            'priority' => 'normal',
            'category' => 'events',
            'target_audience_type' => 'program',
            'target_audience_values' => 'BSIT, BSIS',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.announcements.index'));

        $announcement = Announcement::where('title', 'IT and IS Joint Seminar')->first();
        $this->assertNotNull($announcement);
        $this->assertEquals(['BSIT', 'BSIS'], $announcement->target_audience_values);

        $this->assertTrue($announcement->isAudienceEligible($this->studentBsit));
        $this->assertTrue($announcement->isAudienceEligible($this->studentBsis));
    }

    public function test_rejects_unsupported_audience_type(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.announcements.store'), [
            'title' => 'Invalid Type Test',
            'body' => 'Testing audience type rejection.',
            'priority' => 'normal',
            'category' => 'general',
            'target_audience_type' => 'unsupported_audience_category',
            'target_audience_values' => ['test'],
        ]);

        $response->assertSessionHasErrors(['target_audience_type']);
    }

    public function test_unauthorized_user_cannot_create_announcements(): void
    {
        $response = $this->actingAs($this->studentBsit)->post(route('admin.announcements.store'), [
            'title' => 'Hacked Announcement',
            'body' => 'Unauthorized attempt.',
            'priority' => 'urgent',
            'category' => 'general',
            'target_audience_type' => 'all',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('announcements', [
            'title' => 'Hacked Announcement',
        ]);
    }

    public function test_guest_cannot_create_announcements(): void
    {
        $response = $this->post(route('admin.announcements.store'), [
            'title' => 'Guest Announcement',
            'body' => 'Guest attempt.',
            'priority' => 'urgent',
            'category' => 'general',
            'target_audience_type' => 'all',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('announcements', [
            'title' => 'Guest Announcement',
        ]);
    }
}
