<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\AccountStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManageStudentExpPointsExclusionTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $activeStudent;
    protected User $softDeletedStudent;
    protected User $suspendedStudent;

    protected function setUp(): void
    {
        parent::setUp();

        $ay = AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'is_active' => true,
        ]);

        $this->superAdmin = User::factory()->create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@comsoc.local',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        // Active Student
        $this->activeStudent = User::factory()->create([
            'name' => 'Active Student User',
            'email' => 'active.student@sorsu.edu.ph',
            'student_number' => '2026-00001',
            'member_id' => 'CMS-ACT001',
            'role' => 'student',
            'is_active' => true,
            'is_eligible' => true,
        ]);
        Student::create([
            'user_id' => $this->activeStudent->id,
            'student_number' => '2026-00001',
            'first_name' => 'Active',
            'last_name' => 'Student',
            'email' => 'active.student@sorsu.edu.ph',
            'program' => 'BSIT',
            'year_level' => '1st Year',
            'status' => 'enrolled',
        ]);

        // Soft Deleted Student
        $this->softDeletedStudent = User::factory()->create([
            'name' => 'Soft Deleted Student',
            'email' => 'deleted.student@sorsu.edu.ph',
            'student_number' => '2026-00002',
            'member_id' => 'CMS-DEL002',
            'role' => 'student',
            'is_active' => true,
            'is_eligible' => true,
        ]);
        Student::create([
            'user_id' => $this->softDeletedStudent->id,
            'student_number' => '2026-00002',
            'first_name' => 'Deleted',
            'last_name' => 'Student',
            'email' => 'deleted.student@sorsu.edu.ph',
            'program' => 'BSIT',
            'year_level' => '1st Year',
            'status' => 'enrolled',
        ]);
        app(AccountStatusService::class)->delete($this->softDeletedStudent, $this->superAdmin, 'Graduated and requested account closure');

        // Suspended Student
        $this->suspendedStudent = User::factory()->create([
            'name' => 'Suspended Student',
            'email' => 'suspended.student@sorsu.edu.ph',
            'student_number' => '2026-00003',
            'member_id' => 'CMS-SUS003',
            'role' => 'student',
            'is_active' => true,
            'is_eligible' => true,
        ]);
        Student::create([
            'user_id' => $this->suspendedStudent->id,
            'student_number' => '2026-00003',
            'first_name' => 'Suspended',
            'last_name' => 'Student',
            'email' => 'suspended.student@sorsu.edu.ph',
            'program' => 'BSIT',
            'year_level' => '1st Year',
            'status' => 'enrolled',
        ]);
        app(AccountStatusService::class)->suspend($this->suspendedStudent, $this->superAdmin, 'Policy violation');
    }

    public function test_only_active_students_are_visible_and_counted_in_manage_exp_points(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.students.exp-points.index'));
        $response->assertOk();

        // Active student must appear
        $response->assertSee('Active Student User');
        $response->assertSee('CMS-ACT001');

        // Deleted and suspended students must NOT appear in the selection list or total counts
        $response->assertDontSee('Soft Deleted Student');
        $response->assertDontSee('CMS-DEL002');
        $response->assertDontSee('Suspended Student');
        $response->assertDontSee('CMS-SUS003');

        // Count must strictly reflect 1 student
        $response->assertSee('1 student(s)');
    }

    public function test_search_excludes_deleted_and_suspended_students(): void
    {
        // Searching specifically for the deleted student name or member ID returns zero results
        $searchDeleted = $this->actingAs($this->superAdmin)->get(route('admin.students.exp-points.index', [
            'search' => 'Deleted Student',
        ]));
        $searchDeleted->assertOk();
        $searchDeleted->assertSee('No students matched');
        $searchDeleted->assertDontSee('CMS-DEL002');

        // Searching specifically for suspended student returns zero results
        $searchSuspended = $this->actingAs($this->superAdmin)->get(route('admin.students.exp-points.index', [
            'search' => 'CMS-SUS003',
        ]));
        $searchSuspended->assertOk();
        $searchSuspended->assertSee('No students matched');
    }

    public function test_selected_user_id_query_ignores_deleted_student(): void
    {
        // Attempting to directly select a soft-deleted user ID via URL param
        $response = $this->actingAs($this->superAdmin)->get(route('admin.students.exp-points.index', [
            'user_id' => $this->softDeletedStudent->id,
        ]));
        $response->assertOk();

        // The adjustment form must not load the deleted student
        $response->assertDontSee('Distribute or Adjust Balances to ' . $this->softDeletedStudent->name);
        $response->assertDontSee($this->softDeletedStudent->email);
    }

    public function test_backend_validation_rejects_adjustment_to_soft_deleted_student(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.students.exp-points.adjust'), [
            'user_id' => $this->softDeletedStudent->id,
            'award_type' => 'award',
            'exp_amount' => 100,
            'points_amount' => 50,
            'reason_category' => 'merit_achievement',
            'custom_reason' => 'Should fail',
            'idempotency_key' => 'test-del-' . uniqid(),
        ]);

        $response->assertSessionHasErrors('user_id');
        $this->assertSame(0, XpTransaction::where('user_id', $this->softDeletedStudent->id)->count());
    }

    public function test_backend_validation_rejects_adjustment_to_suspended_or_ineligible_student(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.students.exp-points.adjust'), [
            'user_id' => $this->suspendedStudent->id,
            'award_type' => 'award',
            'exp_amount' => 100,
            'points_amount' => 50,
            'reason_category' => 'merit_achievement',
            'custom_reason' => 'Should fail',
            'idempotency_key' => 'test-sus-' . uniqid(),
        ]);

        $response->assertSessionHasErrors('user_id');
        $this->assertSame(0, XpTransaction::where('user_id', $this->suspendedStudent->id)->count());
    }

    public function test_backend_validation_rejects_non_existent_account(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.students.exp-points.adjust'), [
            'user_id' => '00000000-0000-0000-0000-000000000000',
            'award_type' => 'award',
            'exp_amount' => 100,
            'points_amount' => 50,
            'reason_category' => 'merit_achievement',
            'custom_reason' => 'Non existent',
            'idempotency_key' => 'test-non-' . uniqid(),
        ]);

        $response->assertSessionHasErrors('user_id');
    }

    public function test_active_student_receives_exp_and_points_adjustment_normally(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.students.exp-points.adjust'), [
            'user_id' => $this->activeStudent->id,
            'award_type' => 'award',
            'exp_amount' => 150,
            'points_amount' => 75,
            'reason_category' => 'event_attendance',
            'custom_reason' => 'Valid activity participation',
            'idempotency_key' => 'test-act-' . uniqid(),
        ]);

        $response->assertRedirect(route('admin.students.exp-points.index', ['user_id' => $this->activeStudent->id]));
        $response->assertSessionHas('success');

        $this->assertSame(150, $this->activeStudent->getLifetimeExp());
        $this->assertSame(75, $this->activeStudent->getSpendablePoints());
    }
}
