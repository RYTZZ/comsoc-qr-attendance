<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AttendanceSession;
use App\Models\Event;
use App\Models\Kiosk;
use App\Models\Membership;
use App\Models\QrCode;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaAndOfflineScannerTest extends TestCase
{
    use RefreshDatabase;

    private function getAcademicYear(): AcademicYear
    {
        return AcademicYear::firstOrCreate(
            ['label' => '2026-2027'],
            ['year_start' => 2026, 'year_end' => 2027, 'is_active' => true]
        );
    }

    public function test_pwa_webmanifest_and_serviceworker_exist_and_are_accessible(): void
    {
        $manifestPath = public_path('manifest.webmanifest');
        $this->assertFileExists($manifestPath);
        $manifest = json_decode(file_get_contents($manifestPath), true);
        $this->assertSame('COMSOC HUB', $manifest['short_name']);
        $this->assertSame('standalone', $manifest['display']);

        $swPath = public_path('sw.js');
        $this->assertFileExists($swPath);
        $swContent = file_get_contents($swPath);
        $this->assertStringContainsString('STATIC_CACHE_NAME', $swContent);
        $this->assertStringContainsString('comsoc-static', $swContent);
    }

    public function test_kiosk_offline_manifest_can_be_retrieved(): void
    {
        $ay = $this->getAcademicYear();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $kiosk = Kiosk::create([
            'name' => 'Main Gate Kiosk',
            'identifier' => 'KIOSK-01',
            'is_active' => true,
        ]);

        $student = Student::create([
            'student_number' => '2026-10001',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'gender' => 'male',
            'program' => 'BSCS',
            'year_level' => 3,
            'block' => 'A',
            'is_active' => true,
        ]);

        $mem = Membership::create([
            'student_id' => $student->id,
            'membership_type' => 'regular',
            'academic_year_id' => $ay->id,
            'academic_year' => $ay->label,
            'status' => 'active',
        ]);

        QrCode::create([
            'membership_id' => $mem->id,
            'token' => 'offline_test_token_123',
            'status' => 'active',
        ]);

        $res = $this->actingAs($admin)
            ->getJson(route('kiosk.offline-manifest', $kiosk))
            ->assertOk()
            ->assertJsonStructure([
                'total_attendees',
                'manifest',
                'generated_at',
            ]);

        $manifest = $res->json('manifest');
        $this->assertNotEmpty($manifest);
        $this->assertSame('Juan Dela Cruz', $manifest[0]['name']);
        $this->assertSame('offline_test_token_123', $manifest[0]['token']);
    }

    public function test_kiosk_batch_sync_processes_offline_scans(): void
    {
        $ay = $this->getAcademicYear();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $kiosk = Kiosk::create([
            'name' => 'Express Terminal',
            'identifier' => 'KIOSK-02',
            'is_active' => true,
        ]);

        $event = Event::create([
            'name' => 'COMSOC Tech Summit 2026',
            'academic_year_id' => $ay->id,
            'created_by' => $admin->id,
            'description' => 'Summit description',
            'event_date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'venue' => 'Main Auditorium',
            'status' => 'published',
            'is_published' => true,
        ]);

        $session = AttendanceSession::create([
            'event_id' => $event->id,
            'type' => 'morning_in',
            'opens_at' => '07:00:00',
            'closes_at' => '12:00:00',
            'is_active' => true,
        ]);

        $student = Student::create([
            'student_number' => '2026-20002',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'gender' => 'female',
            'program' => 'BSIT',
            'year_level' => 2,
            'block' => 'B',
            'is_active' => true,
        ]);

        $mem = Membership::create([
            'student_id' => $student->id,
            'membership_type' => 'regular',
            'academic_year_id' => $ay->id,
            'academic_year' => $ay->label,
            'status' => 'active',
        ]);

        $qr = QrCode::create([
            'membership_id' => $mem->id,
            'token' => 'batch_sync_token_456',
            'status' => 'active',
        ]);

        $payload = [
            'scans' => [
                [
                    'token' => $qr->token,
                    'event_id' => $event->id,
                    'session_id' => $session->id,
                    'timestamp' => now()->toISOString(),
                ]
            ]
        ];

        $res = $this->actingAs($admin)
            ->postJson(route('kiosk.batch-sync', $kiosk), $payload)
            ->assertOk()
            ->assertJson([
                'success' => true,
                'synced_count' => 1,
                'failed_count' => 0,
            ]);

        $this->assertDatabaseHas('attendance_records', [
            'event_id' => $event->id,
            'attendance_session_id' => $session->id,
            'qr_token' => $qr->token,
        ]);
    }
}
