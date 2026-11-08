<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityAttendance;
use App\Models\ActivityRegistration;
use App\Models\ActivityReward;
use App\Models\ActivitySession;
use App\Models\ActivitySessionRegistration;
use App\Models\ActivityTeam;
use App\Models\ActivityTeamMember;
use App\Models\RegistrationForm;
use App\Models\Season;
use App\Models\Student;
use App\Models\User;
use App\Services\ActivityAttendanceRewardService;
use App\Services\DynamicQrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DynamicActivityManagementSystemTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $regularAdmin;
    private User $organizer;
    private User $student1;
    private User $student2;
    private User $student3;

    protected function setUp(): void
    {
        parent::setUp();

        Season::create([
            'name' => '1st Semester AY 2026-2027',
            'academic_year_label' => '2026-2027',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(4)->toDateString(),
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->regularAdmin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->organizer = User::factory()->create([
            'role' => 'event_organizer',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $s1Profile = Student::create([
            'student_number' => '2026-00001',
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan',
            'program' => 'BSCS',
            'year_level' => '3rd Year',
            'block' => '1',
            'email' => 'juan@comsochub.tech',
        ]);

        $this->student1 = User::factory()->create([
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@comsochub.tech',
            'student_number' => '2026-00001',
            'student_id' => $s1Profile->id,
            'role' => 'student',
            'is_active' => true,
            'is_eligible' => true,
            'email_verified_at' => now(),
        ]);

        $s2Profile = Student::create([
            'student_number' => '2026-00002',
            'last_name' => 'Santos',
            'first_name' => 'Maria',
            'program' => 'BSIT',
            'year_level' => '2nd Year',
            'block' => '1',
            'email' => 'maria@comsochub.tech',
        ]);

        $this->student2 = User::factory()->create([
            'name' => 'Maria Santos',
            'email' => 'maria@comsochub.tech',
            'student_number' => '2026-00002',
            'student_id' => $s2Profile->id,
            'role' => 'student',
            'is_active' => true,
            'is_eligible' => true,
            'email_verified_at' => now(),
        ]);

        $this->student3 = User::factory()->create([
            'name' => 'Pedro Reyes',
            'email' => 'pedro@comsochub.tech',
            'role' => 'student',
            'is_active' => true,
            'is_eligible' => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_super_admin_only_form_builder_access_and_unauthorized_admin_blocked(): void
    {
        // Super Admin can access form builder
        $this->actingAs($this->superAdmin)
            ->get(route('admin.registration-forms.index'))
            ->assertOk();

        // Regular Admin is blocked (403)
        $this->actingAs($this->regularAdmin)
            ->get(route('admin.registration-forms.index'))
            ->assertForbidden();

        // Event Organizer is blocked (403)
        $this->actingAs($this->organizer)
            ->get(route('admin.registration-forms.index'))
            ->assertForbidden();

        // Student is blocked (403)
        $this->actingAs($this->student1)
            ->get(route('admin.registration-forms.index'))
            ->assertForbidden();
    }

    public function test_dynamic_form_creation_editing_question_options_publishing_and_duplication(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.registration-forms.store'), [
                'title' => 'COMSOC Hackathon 2026 Application',
                'description' => 'Official Hackathon Registration Form',
                'intended_categories' => ['Hackathon', 'IDEATHON'],
                'is_template' => true,
                'publish_now' => true,
                'fields' => [
                    [
                        'field_key' => 'preferred_track',
                        'label' => 'Preferred Track',
                        'question_type' => 'dropdown',
                        'is_required' => true,
                        'is_enabled' => true,
                        'options' => [
                            ['label' => 'Web Development', 'value' => 'Web Development'],
                            ['label' => 'Artificial Intelligence', 'value' => 'Artificial Intelligence'],
                            ['label' => 'Game Development', 'value' => 'Game Development'],
                        ],
                    ],
                    [
                        'field_key' => 'technologies',
                        'label' => 'Preferred Technologies',
                        'question_type' => 'checkboxes',
                        'is_required' => true,
                        'is_enabled' => true,
                        'options' => [
                            ['label' => 'Laravel', 'value' => 'Laravel'],
                            ['label' => 'Flutter', 'value' => 'Flutter'],
                            ['label' => 'PyTorch', 'value' => 'PyTorch'],
                        ],
                    ],
                    [
                        'field_key' => 'project_pitch',
                        'label' => 'Project Pitch Deck',
                        'question_type' => 'file_upload',
                        'is_required' => false,
                        'is_enabled' => true,
                        'accepted_file_types' => 'pdf,png',
                        'max_file_size_kb' => 2048,
                    ],
                ],
            ]);

        $form = RegistrationForm::firstOrFail();
        $response->assertRedirect(route('admin.registration-forms.edit', $form));
        $this->assertEquals('published', $form->status);
        $this->assertEquals(1, $form->version);
        $this->assertCount(3, $form->fields);
        $this->assertCount(3, $form->published_snapshot['fields']);

        // Edit and republish -> increments version
        $this->actingAs($this->superAdmin)
            ->put(route('admin.registration-forms.update', $form), [
                'title' => 'COMSOC Hackathon 2026 Application Updated',
                'publish_now' => true,
                'fields' => [
                    [
                        'id' => $form->fields[0]->id,
                        'field_key' => 'preferred_track',
                        'label' => 'Preferred Track',
                        'question_type' => 'dropdown',
                        'is_required' => true,
                        'is_enabled' => true,
                        'options' => [
                            ['label' => 'Web Development', 'value' => 'Web Development'],
                            ['label' => 'Internet of Things', 'value' => 'Internet of Things'],
                        ],
                    ],
                ],
            ]);

        $form->refresh();
        $this->assertEquals(2, $form->version);
        $this->assertEquals('COMSOC Hackathon 2026 Application Updated', $form->title);

        // Duplicate template
        $this->actingAs($this->superAdmin)
            ->post(route('admin.registration-forms.duplicate', $form), [
                'title' => 'Cloned IDEATHON Form',
                'as_template' => true,
            ]);

        $this->assertDatabaseHas('registration_forms', [
            'title' => 'Cloned IDEATHON Form',
            'parent_form_id' => $form->id,
        ]);
    }

    public function test_required_field_dropdown_checkbox_and_file_upload_validation_plus_version_compatibility(): void
    {
        Storage::fake('local');

        $this->actingAs($this->superAdmin)->post(route('admin.registration-forms.store'), [
            'title' => 'Workshop Form v1',
            'publish_now' => true,
            'fields' => [
                [
                    'field_key' => 'skill_level',
                    'label' => 'Current Skill Level',
                    'question_type' => 'dropdown',
                    'is_required' => true,
                    'is_enabled' => true,
                    'options' => [
                        ['label' => 'Beginner', 'value' => 'Beginner'],
                        ['label' => 'Intermediate', 'value' => 'Intermediate'],
                    ],
                ],
                [
                    'field_key' => 'topics',
                    'label' => 'Topics of Interest',
                    'question_type' => 'checkboxes',
                    'is_required' => true,
                    'is_enabled' => true,
                    'options' => [
                        ['label' => 'UI Design', 'value' => 'UI Design'],
                        ['label' => 'UX Research', 'value' => 'UX Research'],
                    ],
                ],
                [
                    'field_key' => 'portfolio',
                    'label' => 'Portfolio PDF',
                    'question_type' => 'file_upload',
                    'is_required' => true,
                    'is_enabled' => true,
                    'accepted_file_types' => 'pdf',
                    'max_file_size_kb' => 1024,
                ],
            ],
        ]);

        $form = RegistrationForm::firstOrFail();

        $activity = Activity::create([
            'title' => 'UI/UX Workshop',
            'slug' => 'ui-ux-workshop',
            'category' => 'Workshop',
            'status' => 'registration_open',
            'is_multi_session' => false,
            'registration_mode' => 'individual',
            'require_session_selection' => false,
            'registration_form_id' => $form->id,
            'form_version' => $form->version,
            'form_snapshot' => $form->getActiveSchema(),
            'created_by' => $this->superAdmin->id,
        ]);

        $session = $activity->sessions()->create([
            'name' => 'Main Workshop Session',
            'capacity' => 20,
            'is_registration_open' => true,
            'exp_reward' => 100,
            'points_reward' => 10,
            'status' => 'open',
        ]);

        // 1. Invalid dropdown option rejected
        $this->actingAs($this->student1)
            ->post(route('activities.register', $activity), [
                'session_ids' => [$session->id],
                'answers' => [
                    'skill_level' => 'InvalidHackerOption',
                    'topics' => ['UI Design'],
                ],
                'answers_files' => [],
            ])
            ->assertSessionHasErrors(['answers.skill_level', 'answers.portfolio']);

        // 2. Invalid checkbox option rejected
        $this->actingAs($this->student1)
            ->post(route('activities.register', $activity), [
                'session_ids' => [$session->id],
                'answers' => [
                    'skill_level' => 'Beginner',
                    'topics' => ['TamperedCheckboxValue'],
                ],
            ])
            ->assertSessionHasErrors(['answers.topics.0']);

        // 3. Valid submission with PDF file succeeds and preserves form_version snapshot
        $pdfFile = UploadedFile::fake()->create('portfolio.pdf', 200, 'application/pdf');
        $this->actingAs($this->student1)
            ->post(route('activities.register', $activity), [
                'session_ids' => [$session->id],
                'answers' => [
                    'skill_level' => 'Beginner',
                    'topics' => ['UI Design', 'UX Research'],
                    'portfolio' => $pdfFile,
                ],
            ])
            ->assertRedirect(route('activities.my-registrations'));

        $reg = ActivityRegistration::where('activity_id', $activity->id)->where('user_id', $this->student1->id)->firstOrFail();
        $this->assertEquals(1, $reg->form_version);
        $this->assertCount(3, $reg->answers);

        // Unauthorized student cannot download student1's uploaded file (IDOR protection)
        $fileAnswer = $reg->answers()->where('field_key', 'portfolio')->firstOrFail();
        $this->actingAs($this->student2)
            ->get(route('activity-answers.download', $fileAnswer))
            ->assertForbidden();

        // Owner and Super Admin can download
        $this->actingAs($this->student1)
            ->get(route('activity-answers.download', $fileAnswer))
            ->assertOk();
    }

    public function test_multi_session_selection_independent_capacity_duplicate_prevention_and_schedule_conflict_validation(): void
    {
        $activity = Activity::create([
            'title' => 'COMSOC Skills Development Day',
            'slug' => 'comsoc-skills-day',
            'category' => 'Multi-Workshop Event',
            'status' => 'registration_open',
            'is_multi_session' => true,
            'registration_mode' => 'individual',
            'allow_multiple_sessions' => true,
            'max_sessions_per_student' => 2,
            'require_session_selection' => true,
            'conflict_policy' => 'block',
            'waitlist_enabled' => false,
            'created_by' => $this->superAdmin->id,
        ]);

        // Session 1: Web Dev (Capacity 1, 9:00 AM - 11:00 AM)
        $webSession = $activity->sessions()->create([
            'name' => 'Session 1: Web Development',
            'capacity' => 1,
            'starts_at' => now()->addDays(3)->setTime(9, 0),
            'ends_at' => now()->addDays(3)->setTime(11, 0),
            'is_registration_open' => true,
            'exp_reward' => 100,
            'points_reward' => 10,
            'status' => 'open',
        ]);

        // Session 2: Game Dev (Capacity 25, 1:00 PM - 3:00 PM — no overlap)
        $gameSession = $activity->sessions()->create([
            'name' => 'Session 2: Game Development',
            'capacity' => 25,
            'starts_at' => now()->addDays(3)->setTime(13, 0),
            'ends_at' => now()->addDays(3)->setTime(15, 0),
            'is_registration_open' => true,
            'exp_reward' => 100,
            'points_reward' => 10,
            'status' => 'open',
        ]);

        // Session 3: Overlapping Graphic Design (9:30 AM - 11:30 AM — overlaps with Web Dev)
        $graphicSession = $activity->sessions()->create([
            'name' => 'Session 3: Graphic Design',
            'capacity' => 30,
            'starts_at' => now()->addDays(3)->setTime(9, 30),
            'ends_at' => now()->addDays(3)->setTime(11, 30),
            'is_registration_open' => true,
            'exp_reward' => 100,
            'points_reward' => 10,
            'status' => 'open',
        ]);

        // 1. Overlapping session selection is blocked when conflict_policy = block
        $this->actingAs($this->student1)
            ->post(route('activities.register', $activity), [
                'session_ids' => [$webSession->id, $graphicSession->id],
            ])
            ->assertSessionHasErrors(['session_ids']);

        // 2. Selecting non-overlapping Web Dev + Game Dev succeeds for Student 1
        $this->actingAs($this->student1)
            ->post(route('activities.register', $activity), [
                'session_ids' => [$webSession->id, $gameSession->id],
            ])
            ->assertRedirect(route('activities.my-registrations'));

        // Verify independent capacities: Web Dev has 0 remaining, Game Dev still has 24 remaining, Graphic Design still has 30 remaining
        $this->assertEquals(0, $webSession->fresh()->remainingSlots());
        $this->assertEquals(24, $gameSession->fresh()->remainingSlots());
        $this->assertEquals(30, $graphicSession->fresh()->remainingSlots());

        // 3. Duplicate registration by Student 1 is prevented
        $this->actingAs($this->student1)
            ->post(route('activities.register', $activity), [
                'session_ids' => [$gameSession->id],
            ])
            ->assertSessionHasErrors(['registration']);

        // 4. Student 2 cannot register for full Web Dev session, but CAN register for Game Dev + Graphic Design
        $this->actingAs($this->student2)
            ->post(route('activities.register', $activity), [
                'session_ids' => [$webSession->id],
            ])
            ->assertSessionHasErrors(['session_ids']);

        $this->actingAs($this->student2)
            ->post(route('activities.register', $activity), [
                'session_ids' => [$gameSession->id, $graphicSession->id],
            ])
            ->assertRedirect(route('activities.my-registrations'));
    }

    public function test_team_registration_invitations_and_duplicate_membership_prevention(): void
    {
        $hackathon = Activity::create([
            'title' => 'COMSOC Hackathon 2026',
            'slug' => 'comsoc-hackathon-2026',
            'category' => 'Hackathon',
            'status' => 'registration_open',
            'is_multi_session' => false,
            'registration_mode' => 'team',
            'min_team_size' => 2,
            'max_team_size' => 3,
            'require_all_team_invites_accepted' => true,
            'require_session_selection' => true,
            'created_by' => $this->superAdmin->id,
        ]);

        $session = $hackathon->sessions()->create([
            'name' => 'Hackathon Main Finals',
            'capacity' => 50,
            'is_registration_open' => true,
            'exp_reward' => 200,
            'points_reward' => 25,
            'status' => 'open',
        ]);

        // Student 1 (Captain) creates team and invites Student 2
        $this->actingAs($this->student1)
            ->post(route('activities.teams.store', $hackathon), [
                'name' => 'Team Quantum',
                'project_title' => 'AI Attendance Guardian',
                'invited_user_ids' => [$this->student2->id],
            ])
            ->assertRedirect(route('activities.show', $hackathon));

        $team = ActivityTeam::where('activity_id', $hackathon->id)->firstOrFail();

        // Submitting before Student 2 accepts invitation is blocked
        $this->actingAs($this->student1)
            ->post(route('activities.register', $hackathon), [
                'team_id' => $team->id,
                'session_ids' => [$session->id],
            ])
            ->assertSessionHasErrors(['team']);

        // Student 2 accepts invitation
        $invite = ActivityTeamMember::where('activity_team_id', $team->id)
            ->where('user_id', $this->student2->id)
            ->firstOrFail();

        $this->actingAs($this->student2)
            ->post(route('activities.teams.invitations.respond', $invite), [
                'decision' => 'accepted',
            ]);

        // Prevent Student 2 from creating a duplicate team in the same activity
        $this->actingAs($this->student2)
            ->post(route('activities.teams.store', $hackathon), [
                'name' => 'Duplicate Team',
            ])
            ->assertSessionHasErrors(['team']);

        // Now Captain submits team registration -> registers both Student 1 and Student 2
        $this->actingAs($this->student1)
            ->post(route('activities.register', $hackathon), [
                'team_id' => $team->id,
                'session_ids' => [$session->id],
            ])
            ->assertRedirect(route('activities.my-registrations'));

        $this->assertEquals(2, ActivityRegistration::where('activity_id', $hackathon->id)->count());
        $this->assertEquals(2, ActivitySessionRegistration::where('activity_session_id', $session->id)->count());
    }

    public function test_registration_deadline_and_independent_session_approval(): void
    {
        $closedActivity = Activity::create([
            'title' => 'Past Deadline Seminar',
            'slug' => 'past-deadline-seminar',
            'category' => 'Seminar',
            'status' => 'registration_open',
            'registration_opens_at' => now()->subDays(5),
            'registration_closes_at' => now()->subMinute(),
            'require_session_selection' => false,
            'created_by' => $this->superAdmin->id,
        ]);

        // Registration after deadline is rejected
        $this->actingAs($this->student1)
            ->post(route('activities.register', $closedActivity))
            ->assertSessionHasErrors(['registration']);

        // Activity with separate session approval
        $approvalActivity = Activity::create([
            'title' => 'Bootcamp with Separate Session Approval',
            'slug' => 'bootcamp-approval',
            'category' => 'Bootcamp',
            'status' => 'registration_open',
            'is_multi_session' => true,
            'requires_approval' => true,
            'require_separate_session_approval' => true,
            'created_by' => $this->superAdmin->id,
        ]);

        $s1 = $approvalActivity->sessions()->create([
            'name' => 'Bootcamp Track A',
            'requires_approval' => true,
            'is_registration_open' => true,
            'exp_reward' => 100,
            'points_reward' => 10,
        ]);

        $s2 = $approvalActivity->sessions()->create([
            'name' => 'Bootcamp Track B',
            'requires_approval' => true,
            'is_registration_open' => true,
            'exp_reward' => 100,
            'points_reward' => 10,
        ]);

        $this->actingAs($this->student1)
            ->post(route('activities.register', $approvalActivity), [
                'session_ids' => [$s1->id, $s2->id],
            ]);

        $parentReg = ActivityRegistration::where('activity_id', $approvalActivity->id)->firstOrFail();
        $this->assertEquals('pending', $parentReg->status);

        // Approving parent registration does NOT automatically approve sessions when require_separate_session_approval = true
        $this->actingAs($this->superAdmin)
            ->post(route('admin.activities.registrations.approve', $parentReg));

        $parentReg->refresh();
        $this->assertEquals('approved', $parentReg->status);
        $this->assertEquals(
            2,
            $parentReg->sessionRegistrations()->where('status', 'pending')->count()
        );

        // Approve only Session 1
        $s1Reg = $parentReg->sessionRegistrations()->where('activity_session_id', $s1->id)->firstOrFail();
        $this->actingAs($this->superAdmin)
            ->post(route('admin.activities.session-registrations.approve', $s1Reg));

        $this->assertEquals('approved', $s1Reg->fresh()->status);
        $this->assertEquals(
            'pending',
            $parentReg->sessionRegistrations()->where('activity_session_id', $s2->id)->first()->status
        );
    }

    public function test_independent_session_qr_attendance_completion_and_duplicate_reward_prevention(): void
    {
        $activity = Activity::create([
            'title' => 'COMSOC Multi-Session Skills Day',
            'slug' => 'comsoc-multi-skills',
            'category' => 'Multi-Workshop Event',
            'status' => 'ongoing',
            'is_multi_session' => true,
            'allow_multiple_sessions' => true,
            'created_by' => $this->superAdmin->id,
        ]);
        $activity->organizers()->attach($this->organizer->id, ['id' => (string) \Illuminate\Support\Str::uuid()]);

        $session1 = $activity->sessions()->create([
            'name' => 'Web Development',
            'capacity' => 30,
            'is_registration_open' => true,
            'attendance_enabled' => true,
            'qr_attendance_enabled' => true,
            'require_check_out' => true,
            'exp_reward' => 100,
            'points_reward' => 10,
            'status' => 'ongoing',
        ]);

        $session2 = $activity->sessions()->create([
            'name' => 'Game Development',
            'capacity' => 25,
            'is_registration_open' => true,
            'attendance_enabled' => true,
            'qr_attendance_enabled' => true,
            'require_check_out' => true,
            'exp_reward' => 100,
            'points_reward' => 10,
            'status' => 'ongoing',
        ]);

        // Student 1 registers for both sessions
        $this->actingAs($this->student1)
            ->post(route('activities.register', $activity), [
                'session_ids' => [$session1->id, $session2->id],
            ]);

        $s1Reg = ActivitySessionRegistration::where('activity_session_id', $session1->id)
            ->where('user_id', $this->student1->id)
            ->firstOrFail();
        $s2Reg = ActivitySessionRegistration::where('activity_session_id', $session2->id)
            ->where('user_id', $this->student1->id)
            ->firstOrFail();

        // Merely registering does NOT mark completed or award rewards
        $this->assertEquals('registered', $s1Reg->completion_status);
        $this->assertFalse($s1Reg->rewards_awarded);
        $this->assertEquals(0, $this->student1->fresh()->getLifetimeExp());

        /** @var DynamicQrService $qrService */
        $qrService = app(DynamicQrService::class);
        /** @var ActivityAttendanceRewardService $attService */
        $attService = app(ActivityAttendanceRewardService::class);

        // Scan 1: Check-In to Session 1 using rolling QR token
        $token1 = $qrService->generateTokenForUser($this->student1);
        $checkInRes = $attService->processSessionQrScan($activity, $session1, $token1['qr_payload'], $this->organizer, 'in');
        $this->assertTrue($checkInRes['success']);
        $this->assertEquals('check_in', $checkInRes['action']);

        // Attendance in Session 1 does NOT record attendance in Session 2
        $this->assertNull(ActivityAttendance::where('activity_session_id', $session2->id)->where('user_id', $this->student1->id)->first());
        $this->assertEquals('registered', $s2Reg->fresh()->completion_status);

        // Duplicate Check-In to Session 1 is prevented
        $tokenDup = $qrService->generateTokenForUser($this->student1);
        $dupRes = $attService->processSessionQrScan($activity, $session1, $tokenDup['qr_payload'], $this->organizer, 'in');
        $this->assertFalse($dupRes['success']);
        $this->assertEquals('duplicate_check_in', $dupRes['code']);

        // Scan 2: Check-Out from Session 1 -> completes Session 1 and awards 100 EXP + 10 Points
        $token2 = $qrService->generateTokenForUser($this->student1);
        $checkOutRes = $attService->processSessionQrScan($activity, $session1, $token2['qr_payload'], $this->organizer, 'out');
        $this->assertTrue($checkOutRes['success']);
        $this->assertTrue($checkOutRes['completed']);
        $this->assertEquals(100, $checkOutRes['exp_awarded']);
        $this->assertEquals(10, $checkOutRes['points_awarded']);

        // Complete Session 2 as well -> awards another 100 EXP + 10 Points (Total = 200 EXP, 20 Points)
        $token3 = $qrService->generateTokenForUser($this->student1);
        $attService->processSessionQrScan($activity, $session2, $token3['qr_payload'], $this->organizer, 'in');
        $token4 = $qrService->generateTokenForUser($this->student1);
        $attService->processSessionQrScan($activity, $session2, $token4['qr_payload'], $this->organizer, 'out');

        $this->assertEquals(200, $this->student1->fresh()->getLifetimeExp());
        $this->assertEquals(20, $this->student1->fresh()->getSpendablePoints());

        // Attempting to issue rewards again for Session 1 is prevented by idempotency
        $reissue = $attService->issueSessionRewards($s1Reg->fresh(), $this->superAdmin);
        $this->assertFalse($reissue['awarded']);
        $this->assertEquals(200, $this->student1->fresh()->getLifetimeExp());
        $this->assertEquals(2, ActivityReward::where('user_id', $this->student1->id)->count());
    }

    public function test_waitlist_enrollment_and_automatic_waitlist_promotion_plus_csv_export(): void
    {
        $activity = Activity::create([
            'title' => 'COMSOC Industry Familiarization Tour',
            'slug' => 'comsoc-industry-tour',
            'category' => 'Industry Familiarization Tour',
            'status' => 'registration_open',
            'is_multi_session' => true,
            'waitlist_enabled' => true,
            'allow_cancellation' => true,
            'created_by' => $this->superAdmin->id,
        ]);

        // Session with capacity = 1 and waitlist enabled
        $busSession = $activity->sessions()->create([
            'name' => 'Bus 1 — Tech Park Visit',
            'capacity' => 1,
            'waitlist_enabled' => true,
            'is_registration_open' => true,
            'exp_reward' => 150,
            'points_reward' => 15,
            'status' => 'open',
        ]);

        // Student 1 registers -> gets the 1 available approved slot
        $this->actingAs($this->student1)
            ->post(route('activities.register', $activity), [
                'session_ids' => [$busSession->id],
            ]);

        // Student 2 registers when full -> automatically placed on waitlist at position #1
        $this->actingAs($this->student2)
            ->post(route('activities.register', $activity), [
                'session_ids' => [$busSession->id],
            ]);

        $s2SessionReg = ActivitySessionRegistration::where('activity_session_id', $busSession->id)
            ->where('user_id', $this->student2->id)
            ->firstOrFail();

        $this->assertEquals('waitlisted', $s2SessionReg->status);
        $this->assertEquals(1, $s2SessionReg->waitlist_position);

        // Student 1 cancels registration -> Student 2 is automatically promoted from waitlist to approved
        $s1ParentReg = ActivityRegistration::where('activity_id', $activity->id)
            ->where('user_id', $this->student1->id)
            ->firstOrFail();

        $this->actingAs($this->student1)
            ->post(route('activities.registrations.cancel', $s1ParentReg))
            ->assertRedirect();

        $this->assertEquals('approved', $s2SessionReg->fresh()->status);
        $this->assertNull($s2SessionReg->fresh()->waitlist_position);

        // Verify CSV export redacts Student ID for non-Super Admin and shows Student ID for Super Admin
        $csvAdmin = $this->actingAs($this->regularAdmin)
            ->get(route('admin.activities.export-csv', $activity));
        $csvAdmin->assertOk();
        $this->assertStringContainsString('REDACTED', $csvAdmin->streamedContent());

        $csvSuperAdmin = $this->actingAs($this->superAdmin)
            ->get(route('admin.activities.export-csv', $activity));
        $csvSuperAdmin->assertOk();
        $this->assertStringContainsString('2026-00002', $csvSuperAdmin->streamedContent());
    }

    public function test_program_year_level_eligibility_and_organizer_verification_completion_requirements(): void
    {
        $activity = Activity::create([
            'title' => '3rd Year BSCS Capstone Bootcamp',
            'slug' => 'bscs-3rd-year-bootcamp',
            'category' => 'Bootcamp',
            'status' => 'registration_open',
            'eligible_programs' => ['BSCS'],
            'eligible_year_levels' => ['3rd Year'],
            'award_parent_rewards' => true,
            'parent_exp_reward' => 50,
            'parent_points_reward' => 5,
            'completion_mode' => 'all_selected_sessions',
            'created_by' => $this->superAdmin->id,
        ]);

        $session = $activity->sessions()->create([
            'name' => 'Thesis Architecture Lab',
            'capacity' => 20,
            'is_registration_open' => true,
            'attendance_enabled' => false,
            'require_organizer_verification' => true,
            'require_output_submission' => true,
            'exp_reward' => 100,
            'points_reward' => 10,
            'status' => 'open',
        ]);

        // Student 2 (BSIT, 2nd Year) is blocked by backend eligibility check
        $this->actingAs($this->student2)
            ->post(route('activities.register', $activity), [
                'session_ids' => [$session->id],
            ])
            ->assertSessionHasErrors(['program']);

        // Student 1 (BSCS, 3rd Year) passes eligibility and registers
        $this->actingAs($this->student1)
            ->post(route('activities.register', $activity), [
                'session_ids' => [$session->id],
            ])
            ->assertRedirect(route('activities.my-registrations'));

        $sessionReg = ActivitySessionRegistration::where('activity_session_id', $session->id)
            ->where('user_id', $this->student1->id)
            ->firstOrFail();

        // Organizer verifies output & completion -> awards 100 Session EXP + 50 Parent Bonus EXP = 150 EXP
        $this->actingAs($this->superAdmin)
            ->post(route('admin.activities.session-registrations.verify', $sessionReg), [
                'organizer_verified' => 1,
                'output_submitted' => 1,
                'completion_status' => 'completed',
            ])
            ->assertRedirect();

        $this->assertEquals('completed', $sessionReg->fresh()->completion_status);
        $this->assertEquals(150, $this->student1->fresh()->getLifetimeExp());
        $this->assertEquals(15, $this->student1->fresh()->getSpendablePoints());
    }

    public function test_audit_regressions_role_access_evaluation_template_destroy_and_qr_pass_priority(): void
    {
        // 1. comsoc_admin can access /admin/dashboard without 403
        $this->actingAs($this->regularAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk();

        // 2. event_organizer and member roles can access /dashboard without 403 redirect loop
        $this->actingAs($this->organizer)
            ->get(route('dashboard'))
            ->assertOk();

        $memberRoleUser = User::factory()->create([
            'role' => 'member',
            'is_active' => true,
            'is_eligible' => true,
            'must_change_password' => false,
        ]);
        $this->actingAs($memberRoleUser)
            ->get(route('dashboard'))
            ->assertOk();

        // 3. EvaluationTemplateController::destroy works for Super Admin
        $evalTemplate = \App\Models\EvaluationTemplate::create([
            'title' => 'Temp Workshop Feedback',
            'description' => 'Audit test template',
            'is_active' => true,
            'version' => 1,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->actingAs($this->superAdmin)
            ->delete(route('admin.evaluation-templates.destroy', $evalTemplate))
            ->assertRedirect(route('admin.evaluation-templates.index'));

        $this->assertSoftDeleted('evaluation_templates', ['id' => $evalTemplate->id]);

        // 4. Activity update preserves require_parent_registration and allow_session_change
        $activity = Activity::create([
            'title' => 'Preserved Config Activity',
            'slug' => 'preserved-config-activity',
            'category' => 'Seminar',
            'status' => 'registration_open',
            'require_parent_registration' => true,
            'allow_session_change' => true,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->actingAs($this->superAdmin)
            ->put(route('admin.activities.update', $activity), [
                'title' => 'Preserved Config Activity Updated',
                'category' => 'Seminar',
                'status' => 'registration_open',
                'registration_mode' => 'individual',
                'conflict_policy' => 'block',
                'completion_mode' => 'any_session',
            ])
            ->assertRedirect(route('admin.activities.show', $activity));

        $this->assertTrue($activity->fresh()->require_parent_registration);
        $this->assertTrue($activity->fresh()->allow_session_change);

        // 5. Assigned event_organizer can access /admin/activities and view their assigned activity, but cannot create parent activities
        $activity->organizers()->attach($this->organizer->id, [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'assigned_by' => $this->superAdmin->id,
        ]);

        $this->actingAs($this->organizer)
            ->get(route('admin.activities.index'))
            ->assertOk();

        $this->actingAs($this->organizer)
            ->get(route('admin.activities.show', $activity))
            ->assertOk();

        $this->actingAs($this->organizer)
            ->get(route('admin.activities.create'))
            ->assertForbidden();

        // 6. Session switching works when allow_session_change = true and is blocked when unchecked ('0')
        $sessA = $activity->sessions()->create([
            'name' => 'Track A Session',
            'capacity' => 20,
            'is_registration_open' => true,
            'status' => 'open',
        ]);
        $sessB = $activity->sessions()->create([
            'name' => 'Track B Session',
            'capacity' => 20,
            'is_registration_open' => true,
            'status' => 'open',
        ]);

        $this->actingAs($this->student1)
            ->post(route('activities.register', $activity), [
                'session_ids' => [$sessA->id],
            ])
            ->assertRedirect(route('activities.my-registrations'));

        $sr = ActivitySessionRegistration::where('user_id', $this->student1->id)
            ->where('activity_id', $activity->id)
            ->firstOrFail();

        $this->actingAs($this->student1)
            ->post(route('activities.sessions.switch', $sr), [
                'target_session_id' => $sessB->id,
            ])
            ->assertRedirect();

        $this->assertEquals($sessB->id, $sr->fresh()->activity_session_id);

        // Uncheck allow_session_change via hidden '0' input
        $this->actingAs($this->superAdmin)
            ->put(route('admin.activities.update', $activity), [
                'title' => 'Preserved Config Activity Updated',
                'category' => 'Seminar',
                'status' => 'registration_open',
                'registration_mode' => 'individual',
                'conflict_policy' => 'block',
                'completion_mode' => 'any_session',
                'require_parent_registration' => '0',
                'allow_session_change' => '0',
            ])
            ->assertRedirect(route('admin.activities.show', $activity));

        $this->assertFalse($activity->fresh()->require_parent_registration);
        $this->assertFalse($activity->fresh()->allow_session_change);

        $this->actingAs($this->student1)
            ->post(route('activities.sessions.switch', $sr->fresh()), [
                'target_session_id' => $sessA->id,
            ])
            ->assertSessionHasErrors(['session']);

        // Ownership enforcement: student2 cannot switch student1's session registration
        $this->actingAs($this->student2)
            ->post(route('activities.sessions.switch', $sr->fresh()), [
                'target_session_id' => $sessA->id,
            ])
            ->assertForbidden();

        // 7. EvaluationTemplate referenced by an Event is safely deactivated instead of deleted
        $linkedTemplate = \App\Models\EvaluationTemplate::create([
            'title' => 'Linked Event Evaluation',
            'description' => 'Referenced by an event',
            'is_active' => true,
            'version' => 1,
            'created_by' => $this->superAdmin->id,
        ]);
        \App\Models\Event::create([
            'name' => 'Event Using Template',
            'event_date' => now()->addDay()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'venue' => 'SorSU Hall',
            'status' => 'published',
            'is_published' => true,
            'evaluation_template_id' => $linkedTemplate->id,
            'academic_year_id' => \App\Models\AcademicYear::create([
                'label' => 'A.Y. 2026-2027',
                'year_start' => 2026,
                'year_end' => 2027,
                'is_active' => true,
            ])->id,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->actingAs($this->superAdmin)
            ->delete(route('admin.evaluation-templates.destroy', $linkedTemplate))
            ->assertRedirect(route('admin.evaluation-templates.index'));

        $this->assertFalse($linkedTemplate->fresh()->is_active);
        $this->assertNotSoftDeleted('evaluation_templates', ['id' => $linkedTemplate->id]);

        // 8. User::getProfileProgram() and getProfileYearLevel() 4-case check + footer component render
        $this->assertEquals('BSCS', $this->student1->getProfileProgram());
        $this->assertEquals('3rd Year', $this->student1->getProfileYearLevel());
        $this->assertNull($memberRoleUser->getProfileProgram());
        $this->assertNull($memberRoleUser->getProfileYearLevel());

        \App\Models\UserProfile::updateOrCreate(
            ['user_id' => $memberRoleUser->id],
            ['bio' => 'Profile-only user', 'is_public' => true]
        );
        $this->assertNull($memberRoleUser->fresh()->getProfileProgram());
        $this->assertNull($memberRoleUser->fresh()->getProfileYearLevel());

        $footerHtml = \Illuminate\Support\Facades\Blade::render('<x-public-website-footer />');
        $this->assertStringContainsString('Computing Society (COMSOC)', $footerHtml);
    }
}
