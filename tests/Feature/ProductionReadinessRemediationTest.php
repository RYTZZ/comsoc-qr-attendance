<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Mission;
use App\Models\MissionSubmission;
use App\Models\Organization;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductionReadinessRemediationTest extends TestCase
{
    use RefreshDatabase;

    private function createEvent(array $attributes = []): Event
    {
        $year = AcademicYear::firstOrCreate([
            'label' => 'AY 2026-2027',
        ], [
            'year_start' => 2026,
            'year_end' => 2027,
            'semester' => '1st',
            'is_active' => true,
        ]);

        $admin = User::firstOrCreate([
            'email' => 'admin_test@example.com',
        ], [
            'name' => 'Admin Test',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        return Event::create(array_merge([
            'name' => 'Default Test Event',
            'academic_year_id' => $year->id,
            'created_by' => $admin->id,
            'event_date' => Carbon::now()->addDays(5),
            'status' => 'published',
            'is_published' => true,
            'allow_non_students' => true,
            'requires_registration' => true,
            'registration_opens_at' => Carbon::now()->subDays(1),
            'registration_deadline' => Carbon::now()->addDays(2),
        ], $attributes));
    }

    public function test_public_event_registration_enforces_capacity_atomically(): void
    {
        $org = Organization::create(['name' => 'SorSU Bulan', 'is_active' => true]);

        $event = $this->createEvent([
            'name' => 'Capacity Limited Summit',
            'max_participants' => 1,
        ]);

        $payload1 = [
            'event_id' => $event->id,
            'full_name' => 'Participant One',
            'email' => 'part1@example.com',
            'organization' => 'SorSU Bulan',
            'program' => 'Bachelor of Science in Information Technology (BSIT)',
            'year_level' => '1st Year',
            'tshirt_size' => 'M',
            'food_restrictions' => 'None',
            'confirmed' => '1',
        ];

        $response1 = $this->post(route('public.register.store', $event), $payload1);
        $response1->assertRedirect();
        $this->assertEquals(1, EventRegistration::where('event_id', $event->id)->count());

        $payload2 = [
            'event_id' => $event->id,
            'full_name' => 'Participant Two',
            'email' => 'part2@example.com',
            'organization' => 'SorSU Bulan',
            'program' => 'Bachelor of Science in Computer Science (BSCS)',
            'year_level' => '2nd Year',
            'tshirt_size' => 'L',
            'food_restrictions' => 'None',
            'confirmed' => '1',
        ];

        $response2 = $this->from(route('public.register', ['event_id' => $event->id]))
            ->post(route('public.register.store', $event), $payload2);
        $response2->assertSessionHas('error');
        $this->assertEquals(1, EventRegistration::where('event_id', $event->id)->count());
    }

    public function test_public_registration_duplicate_attempt_rejected(): void
    {
        $org = Organization::create(['name' => 'SorSU Bulan', 'is_active' => true]);

        $event = $this->createEvent([
            'name' => 'Open Conference',
            'max_participants' => 50,
        ]);

        $payload = [
            'event_id' => $event->id,
            'full_name' => 'Same Person',
            'email' => 'same@example.com',
            'organization' => 'SorSU Bulan',
            'program' => 'Bachelor of Science in Information Technology (BSIT)',
            'year_level' => '1st Year',
            'tshirt_size' => 'M',
            'food_restrictions' => 'None',
            'confirmed' => '1',
        ];

        $this->post(route('public.register.store', $event), $payload)->assertRedirect();
        $this->assertEquals(1, EventRegistration::where('event_id', $event->id)->count());

        $response = $this->post(route('public.register.store', $event), $payload);
        $response->assertSessionHas('duplicate_error');
        $this->assertEquals(1, EventRegistration::where('event_id', $event->id)->count());
    }

    public function test_cross_database_searches_support_case_insensitivity_and_partial_match(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        Student::create([
            'student_number' => '2024-BSIT-0042',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@example.com',
            'program' => 'BS Information Technology',
            'year_level' => '2nd Year',
            'is_regular' => true,
        ]);

        $resUpper = $this->actingAs($admin)->get(route('admin.students.index', ['search' => 'DELA']));
        $resUpper->assertOk();
        $resUpper->assertSee('2024-BSIT-0042');

        $resLower = $this->actingAs($admin)->get(route('admin.students.index', ['search' => 'dela']));
        $resLower->assertOk();
        $resLower->assertSee('2024-BSIT-0042');

        $resNumber = $this->actingAs($admin)->get(route('admin.students.index', ['search' => '0042']));
        $resNumber->assertOk();
        $resNumber->assertSee('Juan');
    }

    public function test_mission_submission_file_is_cleaned_up_on_deletion(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $mission = Mission::create([
            'title' => 'Code Clean Up Quest',
            'description' => 'Submit code proof',
            'points' => 50,
            'xp' => 100,
            'submission_type' => 'file',
            'is_active' => true,
        ]);

        $file = UploadedFile::fake()->create('proof.pdf', 100);
        $path = $file->store(MissionSubmission::PROOF_DIRECTORY, MissionSubmission::PROOF_DISK);
        Storage::disk('public')->assertExists($path);

        $submission = MissionSubmission::create([
            'mission_id' => $mission->id,
            'user_id' => $user->id,
            'file_path' => $path,
            'status' => 'pending',
        ]);

        $submission->delete();

        Storage::disk('public')->assertMissing($path);
    }

    public function test_sole_active_super_admin_cannot_be_deactivated_or_downgraded(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $responseToggle = $this->actingAs($superAdmin)
            ->post(route('admin.users.toggle-active', $superAdmin));
        $responseToggle->assertSessionHas('error');
        $this->assertTrue($superAdmin->fresh()->is_active);

        $secondSuper = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $responseToggle2 = $this->actingAs($secondSuper)
            ->post(route('admin.users.toggle-active', $superAdmin));
        $responseToggle2->assertSessionHas('success');
        $this->assertFalse($superAdmin->fresh()->is_active);

        $downgradeAttempt = $this->actingAs($secondSuper)
            ->put(route('admin.users.update', $secondSuper), [
                'name' => $secondSuper->name,
                'email' => $secondSuper->email,
                'role' => 'admin',
                'is_active' => 1,
            ]);
        $downgradeAttempt->assertSessionHasErrors('role');
        $this->assertEquals('super_admin', $secondSuper->fresh()->role);
    }

    public function test_register_success_page_renders_valid_markup(): void
    {
        $event = $this->createEvent([
            'name' => 'Computing Summit 2026',
        ]);

        $response = $this->get(route('public.register.success', ['event_id' => $event->id]));
        $response->assertOk();
        $response->assertSee('Registration Submitted!');
        $response->assertSee('Computing Summit 2026');
        $response->assertSee('Add to Google Calendar');
    }
}
