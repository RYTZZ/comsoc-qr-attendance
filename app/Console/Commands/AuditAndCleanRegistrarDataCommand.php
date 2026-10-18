<?php

namespace App\Console\Commands;

use App\Models\AttendanceRecord;
use App\Models\Membership;
use App\Models\PhysicalCard;
use App\Models\QrCode;
use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditAndCleanRegistrarDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'comsoc:privacy-cleanup
                            {--dry-run : Report count and preview changes without modifying data}
                            {--anonymize-unlinked : Anonymize personal fields on registrar imported records without active user accounts}
                            {--force : Bypasses confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit registrar-imported records, analyze dependencies, and safely anonymize or purge orphaned records under DPO policy.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run') ?? false;
        $anonymizeUnlinked = $this->option('anonymize-unlinked') ?? false;

        $this->info("===============================================================================");
        $this->info(" COMSOC Hub - Data Privacy Audit & Registrar-Import Remediation Tool");
        $this->info("===============================================================================\n");

        $totalStudents = Student::count();
        $importedStudents = Student::where('is_registrar_imported', true)->count();
        $totalUsers = User::count();
        $registeredStudentUsers = User::where('role', 'student')->count();
        $officerUsers = User::whereIn('role', ['admin', 'super_admin', 'president', 'executive_secretary', 'event_staff', 'auditor', 'treasurer'])->count();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Student Table Records', $totalStudents],
                ['Flagged as Registrar-Imported', $importedStudents],
                ['Total Users (Login Accounts)', $totalUsers],
                ['Self-Registered / Student Accounts', $registeredStudentUsers],
                ['Officer / System Accounts (Protected)', $officerUsers],
            ]
        );

        // Find students that have attendance or event records
        $studentsWithAttendance = Student::whereHas('attendanceRecords')->pluck('id');
        $studentIdsWithUsers = User::whereNotNull('student_id')->pluck('student_id');

        // Unlinked students: no user account and no attendance records
        $orphanedImported = Student::where('is_registrar_imported', true)
            ->whereNotIn('id', $studentIdsWithUsers)
            ->whereNotIn('id', $studentsWithAttendance)
            ->get();

        $linkedToAttendance = Student::where('is_registrar_imported', true)
            ->whereIn('id', $studentsWithAttendance)
            ->whereNotIn('id', $studentIdsWithUsers)
            ->get();

        $this->info("\nDetailed Dependency Breakdown:");
        $this->line("- Orphaned registrar records (No login account, zero attendance): {$orphanedImported->count()}");
        $this->line("- Retained registrar records with historical attendance: {$linkedToAttendance->count()}");
        $this->line("- Linked registrar records with active user account: " . ($importedStudents - $orphanedImported->count() - $linkedToAttendance->count()));

        if ($dryRun || (!$anonymizeUnlinked)) {
            $this->warn("\n[DRY RUN / AUDIT ONLY] No modifications performed.");
            $this->line("To safely anonymize unlinked registrar imported student records under the approved plan, run:");
            $this->line("  php artisan comsoc:privacy-cleanup --anonymize-unlinked");
            return 0;
        }

        if (!$this->option('force') && !$this->confirm('Proceed with safe anonymization of unlinked registrar personal data?')) {
            $this->info("Operation cancelled by user.");
            return 0;
        }

        DB::transaction(function () use ($orphanedImported, $linkedToAttendance) {
            $anonymizedCount = 0;

            // Anonymize orphaned records: remove personal identifiers while maintaining schema integrity
            foreach ($orphanedImported as $student) {
                $hashId = substr(hash('sha256', $student->id . 'COMSOC_SALT'), 0, 8);
                $student->update([
                    'first_name' => 'Anonymized',
                    'last_name' => "Student_{$hashId}",
                    'middle_name' => null,
                    'email' => "anonymized_{$hashId}@privacy.comsoc.internal",
                ]);
                $anonymizedCount++;
            }

            $this->info("\n[SUCCESS] Anonymized personal data for {$anonymizedCount} orphaned registrar records.");
            $this->info("Historical attendance records ({$linkedToAttendance->count()}) were protected without cascading deletion.");
        });

        return 0;
    }
}
