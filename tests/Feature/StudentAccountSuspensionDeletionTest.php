<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\ActivityRegistration;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\PointTransaction;
use App\Models\Season;
use App\Models\Student;
use App\Models\User;
use App\Models\UserSession;
use App\Models\XpTransaction;
use App\Services\AccountStatusService;
use App\Services\ExpLedgerService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentAccountSuspensionDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function createSuperAdmin(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'name' => 'Super Administrator',
            'role' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ], $overrides));
    }

    private function createStudentWithAccount(array $userOverrides = []): array
    {
        $student = Student::create([
            'student_number' => '2026-'.rand(10000, 99999),
            'first_name' => 'Carlos',
            'last_name' => 'Reyes',
            'program' => 'BSCS',
            'year_level' => '2nd Year',
            'block' => '1',
            'email' => 'carlos.'.uniqid().'@comsochub.tech',
        ]);

        $user = User::factory()->create(array_merge([
            'name' => 'Carlos Reyes',
            'username' => 'carlos_'.substr(md5(uniqid()), 0, 6),
            'email' => $student->email,
            'password' => Hash::make('Password123!'),
            'role' => 'student',
            'student_id' => $student->id,
            'student_number' => $student->student_number,
            'is_active' => true,
            'must_change_password' => false,
            'email_verified_at' => now(),
        ], $userOverrides));

        return [$student, $user];
    }

    /**
     * Scenarios 1, 2, 3: Super Admin can suspend a student, suspended student cannot log in, and existing sessions are invalidated.
     */
    public function test_super_admin_can_suspend_student_blocking_login_and_invalidating_sessions(): void
    {
        $superAdmin = $this->createSuperAdmin();
        [, $studentUser] = $this->createStudentWithAccount();

        // Create an active tracked session & remember token for the student
        $sessionRecord = UserSession::create([
            'user_id' => $studentUser->id,
            'session_id_hash' => hash('sha256', 'active-session-id'),
            'device_type' => 'desktop',
            'platform' => 'Windows',
            'browser' => 'Chrome',
            'device_signature' => 'sig-desktop-chrome',
            'ip_address' => '127.0.0.1',
            'first_authenticated_at' => now()->subHour(),
            'last_active_at' => now(),
        ]);
        $studentUser->forceFill([
            'active_session_hash' => $sessionRecord->session_id_hash,
            'remember_token' => 'initial-remember-token',
        ])->save();

        // 1. Super Admin suspends the student account
        $response = $this->actingAs($superAdmin)->post(route('admin.users.suspend', $studentUser), [
            'reason' => 'Violation of COMSOC laboratory code of conduct.',
        ]);

        $response->assertRedirect();
        $studentUser->refresh();

        $this->assertFalse($studentUser->is_active);
        $this->assertTrue($studentUser->isSuspended());
        $this->assertNotNull($studentUser->suspended_at);
        $this->assertSame($superAdmin->id, $studentUser->suspended_by);
        $this->assertSame('Violation of COMSOC laboratory code of conduct.', $studentUser->suspension_reason);

        // 3. Existing sessions and tokens are invalidated
        $this->assertNull($studentUser->active_session_hash);
        $this->assertNotSame('initial-remember-token', $studentUser->remember_token);
        $this->assertNotNull($sessionRecord->fresh()->revoked_at);

        // Verify an already-authenticated student session is immediately terminated on next request
        $this->actingAs($studentUser)->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();

        // 2. Suspended student cannot log in
        $loginResponse = $this->post('/login', [
            'email' => $studentUser->email,
            'password' => 'Password123!',
        ]);
        $loginResponse->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * Scenarios 4 & 5: Super Admin can reactivate a suspended student, and reactivated student can log in again.
     */
    public function test_super_admin_can_reactivate_suspended_student_restoring_login_eligibility(): void
    {
        $superAdmin = $this->createSuperAdmin();
        [, $studentUser] = $this->createStudentWithAccount();

        app(AccountStatusService::class)->suspend($studentUser, $superAdmin, 'Temporary administrative hold.');
        $this->assertTrue($studentUser->fresh()->isSuspended());

        // 4. Super Admin reactivates the account
        $response = $this->actingAs($superAdmin)->post(route('admin.users.reactivate', $studentUser), [
            'reason' => 'Hold cleared after review.',
        ]);

        $response->assertRedirect();
        $studentUser->refresh();

        $this->assertTrue($studentUser->is_active);
        $this->assertFalse($studentUser->isSuspended());
        $this->assertNull($studentUser->suspended_at);
        $this->assertNotNull($studentUser->reactivated_at);
        $this->assertSame($superAdmin->id, $studentUser->reactivated_by);

        // Previous sessions remain invalidated (student must authenticate again)
        $this->assertNull($studentUser->active_session_hash);

        auth()->logout();

        // 5. Reactivated student can log in again
        $loginResponse = $this->post('/login', [
            'email' => $studentUser->email,
            'password' => 'Password123!',
        ]);
        $this->assertAuthenticatedAs($studentUser);
        $loginResponse->assertRedirect(route('dashboard', absolute: false));
    }

    /**
     * Scenarios 6, 7, 8, 9, 10, 11:
     * - Super Admin can soft-delete a student
     * - Deleted student cannot log in
     * - Soft-deleted student disappears from active listings
     * - Historical attendance, activity participation, EXP, and Points remain intact
     * - Authorized restoration works
     */
    public function test_soft_delete_and_restore_preserve_historical_records_and_enforce_listings(): void
    {
        $superAdmin = $this->createSuperAdmin();
        [$student, $studentUser] = $this->createStudentWithAccount();

        // Seed historical Attendance, Activity Participation, EXP, and Points
        $academicYear = AcademicYear::create([
            'label' => 'AY 2026-2027',
            'year_start' => 2026,
            'year_end' => 2027,
            'is_active' => true,
        ]);
        $event = Event::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'General Assembly',
            'event_date' => today(),
            'venue' => 'Main Auditorium',
            'status' => 'ongoing',
            'created_by' => $superAdmin->id,
        ]);
        $attSession = AttendanceSession::create([
            'event_id' => $event->id,
            'type' => 'morning_in',
            'opens_at' => now()->subHour(),
            'closes_at' => now()->addHour(),
            'is_active' => true,
        ]);
        $attendanceRecord = AttendanceRecord::create([
            'event_id' => $event->id,
            'attendance_session_id' => $attSession->id,
            'participant_type' => 'student',
            'participant_id' => $student->id,
            'user_id' => $studentUser->id,
            'action' => 'time_in',
            'qr_token' => 'qr-token-hist-123',
            'scanned_by' => $superAdmin->id,
            'status' => 'present',
            'scanned_at' => now(),
        ]);

        $activity = Activity::create([
            'academic_year_id' => $academicYear->id,
            'title' => 'AI Bootcamp',
            'slug' => 'ai-bootcamp',
            'category' => 'workshop',
            'status' => 'published',
            'created_by' => $superAdmin->id,
        ]);
        $activityReg = ActivityRegistration::create([
            'activity_id' => $activity->id,
            'user_id' => $studentUser->id,
            'student_id' => $student->id,
            'registration_type' => 'individual',
            'status' => 'approved',
            'completion_status' => 'completed',
        ]);

        $season = Season::create([
            'name' => 'Season 1',
            'academic_year_label' => '2026-2027',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'is_active' => true,
            'status' => 'active',
        ]);
        app(ExpLedgerService::class)->creditExp(
            user: $studentUser,
            amount: 150,
            sourceType: 'attendance',
            sourceId: $event->id,
            description: 'General Assembly Attendance',
            idempotencyKey: 'ga_exp_'.$studentUser->id
        );
        $pointTx = PointTransaction::create([
            'user_id' => $studentUser->id,
            'season_id' => $season->id,
            'amount' => 75,
            'transaction_type' => 'earn',
            'source_type' => Event::class,
            'source_id' => $event->id,
            'description' => 'General Assembly Points',
        ]);

        // 6. Super Admin soft-deletes the student account
        $deleteResponse = $this->actingAs($superAdmin)->delete(route('admin.users.destroy', $studentUser), [
            'reason' => 'Graduated / requested account removal.',
            'confirm_delete' => '1',
        ]);
        $deleteResponse->assertRedirect();

        $trashedUser = User::withTrashed()->find($studentUser->id);
        $this->assertNotNull($trashedUser);
        $this->assertTrue($trashedUser->trashed());
        $this->assertFalse($trashedUser->is_active);
        $this->assertSame($superAdmin->id, $trashedUser->deleted_by);
        $this->assertSame('Graduated / requested account removal.', $trashedUser->deletion_reason);

        // 7. Deleted student cannot log in
        auth()->logout();
        $this->post('/login', [
            'email' => $studentUser->email,
            'password' => 'Password123!',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        // 8. Soft-deleted student disappears from ordinary active listings
        $this->assertNull(User::find($studentUser->id));
        $activeStudentsPage = $this->actingAs($superAdmin)->get(route('admin.students.index'));
        $activeStudentsPage->assertOk();
        $activeStudentsPage->assertDontSee($student->student_number);

        // 9 & 10. Historical attendance, activity participation, EXP, and Points records remain 100% intact
        $this->assertDatabaseHas('attendance_records', ['id' => $attendanceRecord->id, 'participant_id' => $student->id]);
        $this->assertDatabaseHas('activity_registrations', ['id' => $activityReg->id, 'user_id' => $studentUser->id]);
        $this->assertSame(150, (int) XpTransaction::where('user_id', $studentUser->id)->sum('lifetime_delta'));
        $this->assertDatabaseHas('point_transactions', ['id' => $pointTx->id, 'user_id' => $studentUser->id, 'amount' => 75]);

        // 11. Authorized Super Admin restoration works
        $restoreResponse = $this->actingAs($superAdmin)->post(route('admin.users.restore', $studentUser->id), [
            'reason' => 'Re-enrolled for graduate studies.',
        ]);
        $restoreResponse->assertRedirect();

        $restoredUser = User::find($studentUser->id);
        $this->assertNotNull($restoredUser);
        $this->assertFalse($restoredUser->trashed());
        $this->assertTrue($restoredUser->is_active);
        $this->assertSame(150, $restoredUser->getLifetimeExp());
        $this->assertSame(75, $restoredUser->getSpendablePoints());
    }

    /**
     * Scenarios 12 & 13: Ordinary administrators and ordinary students cannot suspend or delete students.
     */
    public function test_ordinary_admins_and_students_cannot_suspend_or_delete_accounts(): void
    {
        [, $targetStudent] = $this->createStudentWithAccount();
        [, $otherStudent] = $this->createStudentWithAccount();

        $ordinaryAdmin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // 12. Ordinary administrator is forbidden
        $this->actingAs($ordinaryAdmin)
            ->post(route('admin.users.suspend', $targetStudent), ['reason' => 'Unauthorized attempt'])
            ->assertForbidden();

        $this->actingAs($ordinaryAdmin)
            ->delete(route('admin.users.destroy', $targetStudent), ['reason' => 'Unauthorized attempt', 'confirm_delete' => '1'])
            ->assertForbidden();

        // 13. Ordinary student is forbidden
        $this->actingAs($otherStudent)
            ->post(route('admin.users.suspend', $targetStudent), ['reason' => 'Student attempt'])
            ->assertForbidden();

        $this->actingAs($otherStudent)
            ->delete(route('admin.users.destroy', $targetStudent), ['reason' => 'Student attempt', 'confirm_delete' => '1'])
            ->assertForbidden();

        $this->assertTrue($targetStudent->fresh()->is_active);
        $this->assertFalse($targetStudent->fresh()->trashed());
    }

    /**
     * Scenarios 14 & 15: Super Admin cannot delete their own account, and last active Super Admin cannot be suspended or deleted.
     */
    public function test_super_admin_cannot_delete_self_or_suspend_or_delete_last_active_super_admin(): void
    {
        $onlySuperAdmin = $this->createSuperAdmin();

        // 14. Self-deletion is blocked
        $selfDelete = $this->actingAs($onlySuperAdmin)->delete(route('admin.users.destroy', $onlySuperAdmin), [
            'reason' => 'Attempting self deletion',
            'confirm_delete' => '1',
        ]);
        $selfDelete->assertRedirect();
        $this->assertFalse($onlySuperAdmin->fresh()->trashed());

        // 15. Last active Super Admin cannot be deactivated, suspended, or deleted via service or controller
        $toggleResp = $this->actingAs($onlySuperAdmin)->post(route('admin.users.toggle-active', $onlySuperAdmin));
        $toggleResp->assertRedirect();
        $this->assertTrue($onlySuperAdmin->fresh()->is_active);

        $secondSuperAdmin = $this->createSuperAdmin(['is_active' => false]);
        $service = app(AccountStatusService::class);

        try {
            $service->suspend($onlySuperAdmin, $secondSuperAdmin, 'Trying to suspend last active Super Admin');
            $this->fail('Expected AuthorizationException was not thrown.');
        } catch (AuthorizationException $e) {
            $this->assertNotEmpty($e->getMessage());
        }

        $this->assertTrue($onlySuperAdmin->fresh()->is_active);
    }

    /**
     * Scenario 16: Invalid deletion and suspension requests (missing reason, unconfirmed deletion, invalid transitions) are rejected.
     */
    public function test_invalid_suspension_and_deletion_requests_are_rejected(): void
    {
        $superAdmin = $this->createSuperAdmin();
        [, $studentUser] = $this->createStudentWithAccount();

        // Missing suspension reason
        $this->actingAs($superAdmin)
            ->post(route('admin.users.suspend', $studentUser), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        // Missing deletion reason
        $this->actingAs($superAdmin)
            ->delete(route('admin.users.destroy', $studentUser), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        // Unchecked confirm_delete when provided
        $this->actingAs($superAdmin)
            ->delete(route('admin.users.destroy', $studentUser), ['reason' => 'Valid reason', 'confirm_delete' => '0'])
            ->assertSessionHasErrors('confirm_delete');

        $this->assertTrue($studentUser->fresh()->is_active);
        $this->assertFalse($studentUser->fresh()->trashed());
    }

    /**
     * Scenarios 17 & 18: Every successful action creates an audit record, and concurrent/repeated requests do not corrupt account status.
     */
    public function test_audit_logging_and_concurrent_state_consistency(): void
    {
        $superAdmin = $this->createSuperAdmin();
        [, $studentUser] = $this->createStudentWithAccount();
        $service = app(AccountStatusService::class);

        $service->suspend($studentUser, $superAdmin, 'Audit test suspension');
        $service->reactivate($studentUser, $superAdmin, 'Audit test reactivation');
        $service->delete($studentUser, $superAdmin, 'Audit test soft deletion');
        $service->restore($studentUser, $superAdmin, 'Audit test restoration');

        // 17. Verify all 4 audit records exist with reason, actor, and status transitions
        foreach (['account.suspended', 'account.reactivated', 'account.soft_deleted', 'account.restored'] as $action) {
            $log = AuditLog::where('action', $action)
                ->where('auditable_id', $studentUser->id)
                ->first();

            $this->assertNotNull($log, "Expected audit log for {$action}");
            $this->assertSame($superAdmin->id, $log->user_id);
            $this->assertNotEmpty($log->reason);
            $this->assertArrayHasKey('account_status', $log->old_values);
            $this->assertArrayHasKey('account_status', $log->new_values);
        }

        // 18. Repeated / concurrent invalid transition attempts do not corrupt state
        $service->suspend($studentUser->fresh(), $superAdmin, 'First suspension');
        $this->expectException(\InvalidArgumentException::class);
        $service->suspend($studentUser->fresh(), $superAdmin, 'Duplicate suspension');
    }
}
