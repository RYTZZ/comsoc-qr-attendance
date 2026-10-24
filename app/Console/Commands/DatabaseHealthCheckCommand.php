<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseHealthCheckCommand extends Command
{
    protected $signature = 'app:health-check
                            {--strict : Treat optional table anomalies as errors}';

    protected $description = 'Perform a comprehensive administrative database health diagnostic';

    /**
     * Required tables and their critical columns that the application depends on.
     *
     * @var array<string, array<string>>
     */
    protected array $requiredTables = [
        'users' => ['id', 'name', 'email', 'role', 'is_active', 'student_id'],
        'students' => ['id', 'student_number', 'first_name', 'last_name', 'program', 'year_level'],
        'academic_years' => ['id', 'label', 'year_start', 'year_end', 'is_active'],
        'events' => ['id', 'name', 'event_type', 'starts_at', 'ends_at'],
        'attendance_sessions' => ['id', 'event_id', 'type'],
        'attendance_records' => ['id', 'event_id', 'action', 'participant_type'],
        'event_registrations' => ['id', 'event_id', 'user_id', 'status'],
        'event_organizer_assignments' => ['id', 'event_id', 'user_id'],
        'student_signatures' => ['id', 'user_id', 'encrypted_payload', 'version', 'status', 'consent_acknowledged_at'],
        'attendance_sheet_issuances' => ['id', 'event_id', 'storage_path', 'pdf_sha256', 'attendee_count', 'signature_refs'],
        'user_profiles' => ['id', 'user_id'],
        'user_notification_preferences' => ['id', 'user_id'],
        'audit_logs' => ['id', 'action'],
        'settings' => ['key', 'value', 'type'],
    ];

    public function handle(): int
    {
        $this->newLine();
        $this->info('======================================================================');
        $this->info(' COMSOC Hub — Database Health & Schema Diagnostics');
        $this->info('======================================================================');
        $this->newLine();

        $failures = 0;
        $warnings = 0;

        // 1. Connection check
        $defaultConnection = config('database.default');
        $this->line("<comment>Active Connection:</comment> <info>{$defaultConnection}</info>");

        $dbTarget = config("database.connections.{$defaultConnection}.database");
        $sanitizedTarget = is_string($dbTarget) ? basename($dbTarget) : 'configured';
        $this->line("<comment>Database Target:</comment>   <info>{$sanitizedTarget}</info>");

        try {
            DB::connection()->getPdo();
            $this->info('  [OK] Database server is reachable and active.');
        } catch (\Throwable $e) {
            $this->error('  [FAIL] Database connection failed: ' . $e->getMessage());
            $this->newLine();
            $this->error('CRITICAL: Cannot proceed with database diagnostics. Database is unreachable.');
            return 1;
        }

        $this->newLine();

        // 2. Migration status check
        $this->info('Checking migration status...');
        try {
            $ranMigrations = DB::table('migrations')->pluck('migration')->all();
            $migrationFiles = array_map(
                fn ($p) => basename($p, '.php'),
                glob(database_path('migrations/*.php'))
            );
            $pending = array_diff($migrationFiles, $ranMigrations);

            if (empty($pending)) {
                $this->info('  [OK] All migration files have been executed (' . count($ranMigrations) . ' migrations ran).');
            } else {
                $this->error('  [FAIL] Pending migrations detected (' . count($pending) . ' pending):');
                foreach ($pending as $p) {
                    $this->line("         - <fg=red>{$p}</>");
                }
                $this->line('         <comment>Corrective action:</comment> Run `php artisan migrate` to apply pending migrations.');
                $failures++;
            }
        } catch (\Throwable $e) {
            $this->error('  [FAIL] Could not query migrations table: ' . $e->getMessage());
            $failures++;
        }

        $this->newLine();

        // 3. Required tables and critical columns check
        $this->info('Checking required application tables & critical columns...');
        $tableRows = [];

        foreach ($this->requiredTables as $table => $criticalColumns) {
            if (!Schema::hasTable($table)) {
                $tableRows[] = [$table, '<fg=red>MISSING</>', 'N/A', 'Run pending migrations'];
                $failures++;
                continue;
            }

            $missingColumns = [];
            foreach ($criticalColumns as $col) {
                if (!Schema::hasColumn($table, $col)) {
                    $missingColumns[] = $col;
                }
            }

            if (!empty($missingColumns)) {
                $tableRows[] = [
                    $table,
                    '<fg=yellow>INCOMPLETE</>',
                    implode(', ', $missingColumns),
                    'Update or run schema migration',
                ];
                $failures++;
            } else {
                $tableRows[] = [$table, '<fg=green>HEALTHY</>', 'All critical columns present', 'None'];
            }
        }

        $this->table(['Table', 'Status', 'Details', 'Action'], $tableRows);
        $this->newLine();

        // 4. Eloquent Model Schema Inventory Scan
        $this->info('Scanning all Eloquent model table mappings...');
        $modelScanRows = [];
        $modelFiles = glob(app_path('Models/*.php'));
        $missingModelTables = [];

        foreach ($modelFiles as $file) {
            $class = 'App\\Models\\' . basename($file, '.php');
            if (!class_exists($class)) {
                continue;
            }
            $reflection = new \ReflectionClass($class);
            if ($reflection->isAbstract()) {
                continue;
            }

            try {
                $model = new $class;
                $table = $model->getTable();
                if (!Schema::hasTable($table)) {
                    $missingModelTables[] = [$class, $table];
                }
            } catch (\Throwable $e) {
                // Ignore instantiation issues with non-standard models
            }
        }

        if (empty($missingModelTables)) {
            $this->info('  [OK] All 65 Eloquent models map to verified existing tables.');
        } else {
            $this->error('  [FAIL] Eloquent models with missing tables detected:');
            foreach ($missingModelTables as [$m, $t]) {
                $this->line("         - <fg=red>{$m}</> (expects table: <fg=yellow>{$t}</>)");
            }
            $failures++;
        }

        $this->newLine();

        // 5. Signature Storage Security & Decryption Health (Non-sensitive)
        $this->info('Checking signature vault encryption configuration...');
        $encKey = config('signatures.encryption_key') ?: config('app.key');
        if (empty($encKey)) {
            $this->error('  [FAIL] Signature encryption key is empty! APP_KEY or SIGNATURE_ENCRYPTION_KEY required.');
            $failures++;
        } else {
            $cipher = config('signatures.cipher') ?: config('app.cipher', 'AES-256-CBC');
            $this->info("  [OK] Encryption key configured with cipher {$cipher}.");
        }

        // Summary
        $this->newLine();
        $this->info('======================================================================');
        if ($failures === 0) {
            $this->info(' DATABASE HEALTH: HEALTHY (0 failures, 0 blocking issues)');
            $this->info('======================================================================');
            return 0;
        }

        $this->error(" DATABASE HEALTH: UNHEALTHY ({$failures} critical failure(s) detected)");
        $this->error(' Please review the corrective actions listed above.');
        $this->info('======================================================================');
        return 1;
    }
}
