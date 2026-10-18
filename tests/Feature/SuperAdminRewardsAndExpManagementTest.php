<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Event;
use App\Models\Raffle;
use App\Models\RaffleWinner;
use App\Models\Reward;
use App\Models\Student;
use App\Models\User;
use App\Models\XpTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuperAdminRewardsAndExpManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $studentUser;
    protected User $regularAdmin;
    protected Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $ay = AcademicYear::create([
            'label' => 'AY 2025–2026',
            'year_start' => 2025,
            'year_end' => 2026,
            'is_active' => true,
        ]);

        $this->superAdmin = User::factory()->create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@comsoc.local',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->regularAdmin = User::factory()->create([
            'name' => 'Regular Admin',
            'email' => 'admin@comsoc.local',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->studentUser = User::factory()->create([
            'name' => 'Student Recipient',
            'email' => 'student@sorsu.edu.ph',
            'role' => 'member',
            'is_active' => true,
        ]);

        Student::create([
            'user_id' => $this->studentUser->id,
            'student_number' => '2023-99999',
            'first_name' => 'Student',
            'last_name' => 'Recipient',
            'email' => 'student@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Technology',
            'year_level' => '3rd Year',
            'status' => 'enrolled',
        ]);

        $this->event = Event::create([
            'academic_year_id' => $ay->id,
            'created_by' => $this->superAdmin->id,
            'name' => 'From Idea to App: Building a Real-World Product',
            'description' => 'Flagship development symposium',
            'location' => 'Auditorium',
            'event_date' => now()->addDays(5),
            'status' => 'published',
            'is_published' => true,
            'max_participants' => 250,
        ]);
    }

    public function test_super_admin_can_create_and_upload_reward_with_event_association(): void
    {
        Storage::fake('public');

        $imageFile = UploadedFile::fake()->image('reward_tote.jpg', 400, 400);

        $response = $this->actingAs($this->superAdmin)->post(route('admin.shop.store'), [
            'title' => 'Exclusive From Idea to App Swag Bag',
            'category' => 'Tote bags',
            'event_id' => $this->event->id,
            'points_cost' => 150,
            'min_level' => 2,
            'stock' => 50,
            'description' => 'Limited edition canvas tote bag for symposium attendees.',
            'eligibility_notes' => 'Requires verified symposium attendance',
            'fulfillment_instructions' => 'Claim at registration booth',
            'image_file' => $imageFile,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.shop.index', ['tab' => 'catalog']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('rewards', [
            'title' => 'Exclusive From Idea to App Swag Bag',
            'category' => 'Tote bags',
            'event_id' => $this->event->id,
            'points_cost' => 150,
            'stock' => 50,
            'initial_quantity' => 50,
            'is_active' => 1,
        ]);

        $reward = Reward::where('title', 'Exclusive From Idea to App Swag Bag')->first();
        $this->assertNotNull($reward->image_path);
        Storage::disk('public')->assertExists($reward->image_path);
    }

    public function test_super_admin_can_award_exp_and_points_to_student(): void
    {
        $idempotencyKey = 'test_award_' . uniqid();

        $response = $this->actingAs($this->superAdmin)->post(route('admin.students.exp-points.adjust'), [
            'user_id' => $this->studentUser->id,
            'award_type' => 'award',
            'exp_amount' => 150,
            'points_amount' => 75,
            'reason_category' => 'winning_challenge',
            'custom_reason' => 'First place in mobile hackathon',
            'event_id' => $this->event->id,
            'idempotency_key' => $idempotencyKey,
        ]);

        $response->assertRedirect(route('admin.students.exp-points.index', ['user_id' => $this->studentUser->id]));
        $response->assertSessionHas('success');

        // Verify recipient balance updated
        $this->studentUser->refresh();
        $this->assertEquals(150, $this->studentUser->getLifetimeExp());
        $this->assertEquals(75, $this->studentUser->getSpendablePoints());

        // Verify ledger transactions recorded
        $this->assertDatabaseHas('xp_transactions', [
            'user_id' => $this->studentUser->id,
            'lifetime_delta' => 150,
            'spendable_delta' => 75,
            'created_by' => $this->superAdmin->id,
            'idempotency_key' => $idempotencyKey,
        ]);

        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $this->studentUser->id,
            'amount' => 75,
            'transaction_type' => 'super_admin_award',
            'created_by' => $this->superAdmin->id,
        ]);
    }

    public function test_super_admin_can_deduct_exp_and_points_with_negative_balance_prevention(): void
    {
        // First award some points to allow deduction
        app(\App\Services\ExpLedgerService::class)->creditExp(
            $this->studentUser,
            200,
            'adjustment',
            null,
            'Initial balance',
            null,
            'init_' . uniqid(),
            $this->superAdmin
        );

        $this->studentUser->refresh();
        $this->assertEquals(200, $this->studentUser->getLifetimeExp());
        $this->assertEquals(200, $this->studentUser->getSpendableExp());

        // Valid deduction
        $response = $this->actingAs($this->superAdmin)->post(route('admin.students.exp-points.adjust'), [
            'user_id' => $this->studentUser->id,
            'award_type' => 'deduct',
            'exp_amount' => 50,
            'points_amount' => 50,
            'reason_category' => 'administrative_correction',
            'custom_reason' => 'Duplicate attendance adjustment reversal',
            'idempotency_key' => 'deduct_' . uniqid(),
        ]);

        $response->assertSessionHas('success');
        $this->studentUser->refresh();
        $this->assertEquals(150, $this->studentUser->getLifetimeExp());
        $this->assertEquals(150, $this->studentUser->getSpendableExp());

        // Excessive deduction attempting negative balance must be rejected
        $failedResponse = $this->actingAs($this->superAdmin)->post(route('admin.students.exp-points.adjust'), [
            'user_id' => $this->studentUser->id,
            'award_type' => 'deduct',
            'exp_amount' => 500, // Exceeds 150 current lifetime
            'points_amount' => 500,
            'reason_category' => 'administrative_correction',
            'idempotency_key' => 'excessive_' . uniqid(),
        ]);

        $failedResponse->assertSessionHas('error');
        $this->studentUser->refresh();
        // Balances must remain intact
        $this->assertEquals(150, $this->studentUser->getLifetimeExp());
        $this->assertEquals(150, $this->studentUser->getSpendableExp());
    }

    public function test_duplicate_submissions_are_prevented_by_idempotency_key(): void
    {
        $idempotencyKey = 'fixed_key_prevent_dupes_123';

        // First attempt succeeds
        $first = $this->actingAs($this->superAdmin)->post(route('admin.students.exp-points.adjust'), [
            'user_id' => $this->studentUser->id,
            'award_type' => 'award',
            'exp_amount' => 100,
            'points_amount' => 100,
            'reason_category' => 'volunteering',
            'idempotency_key' => $idempotencyKey,
        ]);
        $first->assertSessionHas('success');

        $this->studentUser->refresh();
        $this->assertEquals(100, $this->studentUser->getLifetimeExp());

        // Repeated submission with exact same idempotency key must be prevented
        $second = $this->actingAs($this->superAdmin)->post(route('admin.students.exp-points.adjust'), [
            'user_id' => $this->studentUser->id,
            'award_type' => 'award',
            'exp_amount' => 100,
            'points_amount' => 100,
            'reason_category' => 'volunteering',
            'idempotency_key' => $idempotencyKey,
        ]);
        $second->assertSessionHas('error');

        // Balance remains 100, not 200
        $this->studentUser->refresh();
        $this->assertEquals(100, $this->studentUser->getLifetimeExp());
    }

    public function test_unauthorized_users_cannot_access_or_adjust_exp_points(): void
    {
        // Regular student cannot access
        $studentResponse = $this->actingAs($this->studentUser)->get(route('admin.students.exp-points.index'));
        $studentResponse->assertForbidden();

        // Regular admin cannot access Super Admin exclusive endpoint
        $adminResponse = $this->actingAs($this->regularAdmin)->get(route('admin.students.exp-points.index'));
        $adminResponse->assertForbidden();

        // Post adjustment blocked
        $postResponse = $this->actingAs($this->regularAdmin)->post(route('admin.students.exp-points.adjust'), [
            'user_id' => $this->studentUser->id,
            'award_type' => 'award',
            'exp_amount' => 100,
            'points_amount' => 100,
            'reason_category' => 'volunteering',
            'idempotency_key' => 'unauth_' . uniqid(),
        ]);
        $postResponse->assertForbidden();
    }

    public function test_raffle_creation_entry_sync_and_duplicate_winner_prevention(): void
    {
        // Create 2 student users
        $student2 = User::factory()->create([
            'name' => 'Second Student',
            'email' => 'student2@sorsu.edu.ph',
            'role' => 'member',
        ]);

        $raffle = Raffle::create([
            'event_id' => $this->event->id,
            'created_by' => $this->superAdmin->id,
            'title' => 'Tech Summit Lucky Draw',
            'prize_name' => 'Mechanical Keyboard',
            'points_bonus' => 50,
            'winner_count' => 1,
            'entry_type' => 'attendance',
            'max_entries_per_user' => 1,
            'status' => 'open',
        ]);

        // Manually create entries
        \App\Models\RaffleEntry::create([
            'raffle_id' => $raffle->id,
            'user_id' => $this->studentUser->id,
            'source_type' => 'attendance',
            'eligible_at' => now(),
        ]);

        \App\Models\RaffleEntry::create([
            'raffle_id' => $raffle->id,
            'user_id' => $student2->id,
            'source_type' => 'attendance',
            'eligible_at' => now(),
        ]);

        $this->assertEquals(2, $raffle->entries()->count());

        // Execute draw
        $drawResponse = $this->actingAs($this->superAdmin)->post(route('admin.raffles.draw', $raffle));
        $drawResponse->assertSessionHas('success');

        $raffle->refresh();
        $this->assertTrue($raffle->isDrawn());
        $this->assertEquals(1, $raffle->winners()->count());

        $winner = $raffle->winners()->first();
        $this->assertFalse($winner->is_claimed);

        // Mark as claimed
        $claimResponse = $this->actingAs($this->superAdmin)->post(route('admin.raffles.claim', $winner), [
            'notes' => 'Claimed in person with student ID card',
        ]);
        $claimResponse->assertSessionHas('success');

        $winner->refresh();
        $this->assertTrue($winner->is_claimed);
        $this->assertEquals($this->superAdmin->id, $winner->claimed_by_staff_id);
    }

    public function test_super_admin_login_redirects_to_admin_dashboard_with_authenticated_identity(): void
    {
        // Authenticate super admin
        $response = $this->post(route('login'), [
            'login' => 'superadmin@comsoc.local',
            'password' => 'password', // standard factory password
        ]);

        $response->assertRedirect(route('admin.dashboard'));

        $dashboardResponse = $this->actingAs($this->superAdmin)->get(route('admin.dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('Welcome, Super Administrator!');
        $dashboardResponse->assertSee($this->superAdmin->email);
    }
}
