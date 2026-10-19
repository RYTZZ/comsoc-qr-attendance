<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Event;
use App\Models\EventEvaluation;
use App\Models\EventRegistration;
use App\Models\EvaluationQuestion;
use App\Models\EvaluationTemplate;
use App\Models\Student;
use App\Models\User;
use App\Services\DynamicQrService;
use App\Services\EventAttendanceService;
use App\Services\EventEvaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MandatoryEventEvaluationTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $organizer;
    private User $member;
    private Student $student;
    private Event $event;
    private AttendanceSession $inSession;
    private AttendanceSession $outSession;
    private EvaluationTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        $ay = AcademicYear::create([
            'label' => 'A.Y. 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'is_active' => true,
        ]);

        $this->superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'email' => 'superadmin@comsoc.test',
        ]);

        $this->organizer = User::factory()->create([
            'role' => 'event_organizer',
            'email' => 'organizer@comsoc.test',
        ]);

        $this->student = Student::create([
            'student_number' => '2026-9999',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'program' => 'BSIT',
            'year_level' => '3rd Year',
            'email' => 'juan@comsoc.test',
        ]);

        $this->member = User::factory()->create([
            'role' => 'student',
            'student_id' => $this->student->id,
            'email' => 'juan@comsoc.test',
            'is_active' => true,
            'is_eligible' => true,
        ]);

        $this->event = Event::create([
            'academic_year_id' => $ay->id,
            'created_by' => $this->superAdmin->id,
            'name' => 'Annual Tech Summit 2026',
            'event_type' => 'Seminar / Tech Talk',
            'event_date' => now()->toDateString(),
            'is_published' => true,
            'status' => 'published',
            'attendance_enabled' => true,
            'requires_registration' => true,
        ]);

        $this->event->organizers()->attach($this->organizer->id);

        $this->inSession = AttendanceSession::create([
            'event_id' => $this->event->id,
            'type' => 'afternoon_in',
            'opens_at' => '13:00',
            'closes_at' => '14:00',
            'is_active' => true,
        ]);

        $this->outSession = AttendanceSession::create([
            'event_id' => $this->event->id,
            'type' => 'afternoon_out',
            'opens_at' => '16:00',
            'closes_at' => '18:00',
            'is_active' => true,
        ]);

        // Create reusable evaluation template
        $this->template = EvaluationTemplate::create([
            'title' => 'Standard Tech Summit Feedback',
            'description' => 'Please evaluate your speaker and event experience.',
            'version' => 1,
            'is_active' => true,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->template->questions()->createMany([
            [
                'question_text' => 'Overall rating for this seminar?',
                'question_type' => 'rating',
                'rating_min' => 1,
                'rating_max' => 5,
                'is_required' => true,
                'sort_order' => 1,
            ],
            [
                'question_text' => 'Would you recommend this seminar to peers?',
                'question_type' => 'single_choice',
                'options' => ['Yes', 'No', 'Maybe'],
                'is_required' => true,
                'sort_order' => 2,
            ],
            [
                'question_text' => 'Additional suggestions',
                'question_type' => 'long_text',
                'is_required' => false,
                'sort_order' => 3,
            ],
        ]);

        // Register member to event
        EventRegistration::create([
            'event_id' => $this->event->id,
            'user_id' => $this->member->id,
            'full_name' => $this->student->full_name,
            'email' => $this->member->email,
            'student_number' => $this->student->student_number,
            'status' => 'approved',
        ]);
    }

    /** 1. Eligible attendee can view evaluation form */
    public function test_eligible_attendee_can_access_event_evaluation(): void
    {
        // Assign template to event
        app(EventEvaluationService::class)->snapshotEventTemplate($this->event, $this->template);

        $response = $this->actingAs($this->member)->get(route('events.evaluation.show', $this->event));
        $response->assertStatus(200);
        $response->assertSee('Standard Tech Summit Feedback');
        $response->assertSee('Overall rating for this seminar?');
    }

    /** 2. Non-attendee cannot access event evaluation */
    public function test_non_attendee_cannot_access_event_evaluation(): void
    {
        $nonAttendee = User::factory()->create(['role' => 'student', 'email' => 'stranger@comsoc.test']);

        $response = $this->actingAs($nonAttendee)->get(route('events.evaluation.show', $this->event));
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error');
    }

    /** 3. Required questions must be answered */
    public function test_required_questions_must_be_answered(): void
    {
        app(EventEvaluationService::class)->snapshotEventTemplate($this->event, $this->template);

        $q1 = $this->template->questions()->where('sort_order', 1)->first();

        // Submit missing question 2
        $response = $this->actingAs($this->member)->post(route('events.evaluation.store', $this->event), [
            'answers' => [
                $q1->id => 5,
            ],
        ]);

        $response->assertSessionHasErrors();
        $this->assertEquals(0, EventEvaluation::count());
    }

    /** 4. Optional questions may remain unanswered */
    public function test_optional_questions_may_remain_unanswered(): void
    {
        app(EventEvaluationService::class)->snapshotEventTemplate($this->event, $this->template);

        $q1 = $this->template->questions()->where('sort_order', 1)->first();
        $q2 = $this->template->questions()->where('sort_order', 2)->first();

        $response = $this->actingAs($this->member)->post(route('events.evaluation.store', $this->event), [
            'answers' => [
                $q1->id => 5,
                $q2->id => 'Yes',
                // question 3 (long text optional) omitted
            ],
        ]);

        $response->assertRedirect(route('events.evaluation.show', $this->event));
        $response->assertSessionHas('success');
        $this->assertEquals(1, EventEvaluation::count());
    }

    /** 5. Invalid rating formats are rejected */
    public function test_invalid_rating_format_is_rejected(): void
    {
        app(EventEvaluationService::class)->snapshotEventTemplate($this->event, $this->template);

        $q1 = $this->template->questions()->where('sort_order', 1)->first();
        $q2 = $this->template->questions()->where('sort_order', 2)->first();

        $response = $this->actingAs($this->member)->post(route('events.evaluation.store', $this->event), [
            'answers' => [
                $q1->id => 99, // Out of bounds (1-5)
                $q2->id => 'Yes',
            ],
        ]);

        $response->assertSessionHasErrors();
        $this->assertEquals(0, EventEvaluation::count());
    }

    /** 6. Valid evaluation is persisted and remains completed */
    public function test_valid_evaluation_is_persisted_and_viewable(): void
    {
        app(EventEvaluationService::class)->snapshotEventTemplate($this->event, $this->template);

        $q1 = $this->template->questions()->where('sort_order', 1)->first();
        $q2 = $this->template->questions()->where('sort_order', 2)->first();

        $this->actingAs($this->member)->post(route('events.evaluation.store', $this->event), [
            'answers' => [
                $q1->id => 4,
                $q2->id => 'Yes',
            ],
        ]);

        $eval = EventEvaluation::where('event_id', $this->event->id)->where('user_id', $this->member->id)->first();
        $this->assertNotNull($eval);
        $this->assertCount(2, $eval->answers);

        // Subsequent visit shows completed state
        $res = $this->actingAs($this->member)->get(route('events.evaluation.show', $this->event));
        $res->assertSee('Your Evaluation is Saved');
    }

    /** 7. Repeated submission does not create duplicate evaluations */
    public function test_repeated_submission_does_not_duplicate_evaluations(): void
    {
        app(EventEvaluationService::class)->snapshotEventTemplate($this->event, $this->template);

        $q1 = $this->template->questions()->where('sort_order', 1)->first();
        $q2 = $this->template->questions()->where('sort_order', 2)->first();

        // Submit 1st time
        $this->actingAs($this->member)->post(route('events.evaluation.store', $this->event), [
            'answers' => [$q1->id => 5, $q2->id => 'Yes'],
        ]);

        // Submit 2nd time
        $res = $this->actingAs($this->member)->post(route('events.evaluation.store', $this->event), [
            'answers' => [$q1->id => 5, $q2->id => 'Yes'],
        ]);

        $res->assertSessionHas('info');
        $this->assertEquals(1, EventEvaluation::count());
    }

    /** 8. Unconfigured event blocks timeout scan (fail-closed) */
    public function test_unconfigured_event_blocks_timeout_scan(): void
    {
        // Event has no evaluation template configured
        $this->event->update(['evaluation_template_id' => null, 'evaluation_snapshot' => null]);

        // Record Afternoon IN first
        AttendanceRecord::create([
            'event_id' => $this->event->id,
            'attendance_session_id' => $this->inSession->id,
            'qr_token' => 'IN_TOKEN',
            'participant_type' => 'user',
            'participant_id' => $this->member->id,
            'user_id' => $this->member->id,
            'action' => 'in',
            'status' => 'present',
            'scanned_by' => $this->organizer->id,
            'scanned_at' => now()->subHours(2),
        ]);

        // Generate dynamic token
        $tokenData = app(DynamicQrService::class)->generateTokenForUser($this->member, $this->event);

        $attendanceService = app(EventAttendanceService::class);
        $result = $attendanceService->processScan(
            event: $this->event,
            session: $this->outSession,
            qrTokenPayload: $tokenData['qr_payload'],
            organizer: $this->organizer
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('evaluation_unconfigured', $result['code']);
    }

    /** 9. Incomplete evaluation blocks timeout scan even with valid token */
    public function test_incomplete_evaluation_blocks_afternoon_timeout_scan(): void
    {
        app(EventEvaluationService::class)->snapshotEventTemplate($this->event, $this->template);

        // Record Afternoon IN first
        AttendanceRecord::create([
            'event_id' => $this->event->id,
            'attendance_session_id' => $this->inSession->id,
            'qr_token' => 'IN_TOKEN',
            'participant_type' => 'user',
            'participant_id' => $this->member->id,
            'user_id' => $this->member->id,
            'action' => 'in',
            'status' => 'present',
            'scanned_by' => $this->organizer->id,
            'scanned_at' => now()->subHours(2),
        ]);

        $tokenData = app(DynamicQrService::class)->generateTokenForUser($this->member, $this->event);

        $attendanceService = app(EventAttendanceService::class);
        $result = $attendanceService->processScan(
            event: $this->event,
            session: $this->outSession,
            qrTokenPayload: $tokenData['qr_payload'],
            organizer: $this->organizer
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('evaluation_required', $result['code']);
        $this->assertStringContainsString('Mandatory Event Evaluation Required', $result['message']);

        // Assert no out record was created
        $outCount = AttendanceRecord::where('event_id', $this->event->id)->where('action', 'out')->count();
        $this->assertEquals(0, $outCount);
    }

    /** 10. Completed evaluation allows timeout scan and records attendance */
    public function test_completed_evaluation_permits_afternoon_timeout_scan(): void
    {
        app(EventEvaluationService::class)->snapshotEventTemplate($this->event, $this->template);

        // Record Afternoon IN first
        AttendanceRecord::create([
            'event_id' => $this->event->id,
            'attendance_session_id' => $this->inSession->id,
            'qr_token' => 'IN_TOKEN',
            'participant_type' => 'user',
            'participant_id' => $this->member->id,
            'user_id' => $this->member->id,
            'action' => 'in',
            'status' => 'present',
            'scanned_by' => $this->organizer->id,
            'scanned_at' => now()->subHours(2),
        ]);

        // Complete evaluation
        $q1 = $this->template->questions()->where('sort_order', 1)->first();
        $q2 = $this->template->questions()->where('sort_order', 2)->first();
        app(EventEvaluationService::class)->submitEvaluation($this->event, $this->member, [
            $q1->id => 5,
            $q2->id => 'Yes',
        ]);

        $tokenData = app(DynamicQrService::class)->generateTokenForUser($this->member, $this->event);

        $attendanceService = app(EventAttendanceService::class);
        $result = $attendanceService->processScan(
            event: $this->event,
            session: $this->outSession,
            qrTokenPayload: $tokenData['qr_payload'],
            organizer: $this->organizer
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('accepted', $result['code']);

        $outRecord = AttendanceRecord::where('attendance_session_id', $this->outSession->id)
            ->where('user_id', $this->member->id)
            ->first();

        $this->assertNotNull($outRecord);
        $this->assertEquals('out', $outRecord->action);
    }

    /** 11. Evaluation submission does not automatically record timeout */
    public function test_evaluation_submission_does_not_automatically_record_timeout(): void
    {
        app(EventEvaluationService::class)->snapshotEventTemplate($this->event, $this->template);

        $q1 = $this->template->questions()->where('sort_order', 1)->first();
        $q2 = $this->template->questions()->where('sort_order', 2)->first();

        $this->actingAs($this->member)->post(route('events.evaluation.store', $this->event), [
            'answers' => [$q1->id => 5, $q2->id => 'Yes'],
        ]);

        $this->assertEquals(1, EventEvaluation::count());
        $this->assertEquals(0, AttendanceRecord::where('action', 'out')->count());
    }

    /** 12. Admin can create, edit, and toggle evaluation templates */
    public function test_admin_can_manage_evaluation_templates(): void
    {
        $res = $this->actingAs($this->superAdmin)->post(route('admin.evaluation-templates.store'), [
            'title' => 'Leadership Camp Evaluation',
            'description' => 'Feedback for student leaders',
            'questions' => [
                [
                    'question_text' => 'Quality of leadership workshop?',
                    'question_type' => 'rating',
                    'rating_min' => 1,
                    'rating_max' => 5,
                    'is_required' => 1,
                ],
            ],
        ]);

        $res->assertRedirect(route('admin.evaluation-templates.index'));
        $created = EvaluationTemplate::where('title', 'Leadership Camp Evaluation')->first();
        $this->assertNotNull($created);
        $this->assertEquals(1, $created->version);

        // Edit increases version to 2
        $this->actingAs($this->superAdmin)->put(route('admin.evaluation-templates.update', $created), [
            'title' => 'Leadership Camp Evaluation v2',
            'description' => 'Updated feedback',
            'is_active' => 1,
            'questions' => [
                [
                    'question_text' => 'Quality of leadership workshop?',
                    'question_type' => 'rating',
                    'rating_min' => 1,
                    'rating_max' => 5,
                    'is_required' => 1,
                ],
            ],
        ]);

        $created->refresh();
        $this->assertEquals(2, $created->version);
    }

    /** 13. Regular member cannot manage evaluation templates */
    public function test_regular_member_cannot_manage_evaluation_templates(): void
    {
        $res = $this->actingAs($this->member)->get(route('admin.evaluation-templates.index'));
        $res->assertStatus(403);

        $resPost = $this->actingAs($this->member)->post(route('admin.evaluation-templates.store'), [
            'title' => 'Illegal Template',
            'questions' => [],
        ]);
        $resPost->assertStatus(403);
    }

    /** 14. Admin can view evaluation results and analytics */
    public function test_admin_can_view_evaluation_results(): void
    {
        app(EventEvaluationService::class)->snapshotEventTemplate($this->event, $this->template);

        $q1 = $this->template->questions()->where('sort_order', 1)->first();
        $q2 = $this->template->questions()->where('sort_order', 2)->first();
        app(EventEvaluationService::class)->submitEvaluation($this->event, $this->member, [
            $q1->id => 5,
            $q2->id => 'Yes',
        ]);

        $res = $this->actingAs($this->superAdmin)->get(route('admin.evaluations.results.index', ['event_id' => $this->event->id]));
        $res->assertStatus(200);
        $res->assertSee($this->member->name);
        $res->assertSee('Overall rating for this seminar?');
    }

    /** 15. Student cannot access administrative evaluation results */
    public function test_student_cannot_access_administrative_evaluation_results(): void
    {
        $res = $this->actingAs($this->member)->get(route('admin.evaluations.results.index'));
        $res->assertStatus(403);
    }
}
