<?php

namespace Tests\Feature;

use App\Models\EventOrganizerAssignment;
use App\Models\StudentSignature;
use App\Models\AttendanceSheetIssuance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseHealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_required_tables_exist_in_fresh_database(): void
    {
        $requiredTables = [
            'users',
            'students',
            'academic_years',
            'events',
            'attendance_sessions',
            'attendance_records',
            'event_registrations',
            'event_organizer_assignments',
            'student_signatures',
            'attendance_sheet_issuances',
            'user_profiles',
            'user_notification_preferences',
            'audit_logs',
            'settings',
        ];

        foreach ($requiredTables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Expected table [{$table}] to exist after migration.");
        }
    }

    public function test_event_organizer_assignment_model_resolves_correct_table(): void
    {
        $pivot = new EventOrganizerAssignment();
        $this->assertSame('event_organizer_assignments', $pivot->getTable());
        $this->assertTrue(Schema::hasTable($pivot->getTable()));
    }

    public function test_student_signatures_and_issuances_tables_exist_and_queryable(): void
    {
        $this->assertTrue(Schema::hasTable('student_signatures'));
        $this->assertTrue(Schema::hasTable('attendance_sheet_issuances'));

        $this->assertSame(0, StudentSignature::count());
        $this->assertSame(0, AttendanceSheetIssuance::count());
    }

    public function test_health_check_command_passes_on_healthy_database(): void
    {
        $this->artisan('app:health-check')
            ->expectsOutputToContain('DATABASE HEALTH: HEALTHY')
            ->assertExitCode(0);
    }
}
