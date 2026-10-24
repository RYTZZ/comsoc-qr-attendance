<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\AttendanceSheetIssuance;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Student;
use App\Models\StudentSignature;
use App\Models\User;
use App\Services\AttendanceSheetService;
use App\Services\SignatureVaultService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentSignatureAndAttendanceSheetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    /** Synthetic signature PNG as a data URL (a diagonal stroke, optional colour to tell users apart). */
    private function sigDataUrl(int $w = 300, int $h = 100, array $rgb = [0, 0, 0], bool $blank = false): string
    {
        $im = imagecreatetruecolor($w, $h);
        imagealphablending($im, false);
        imagesavealpha($im, true);
        imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 127));
        if (!$blank) {
            imagesetthickness($im, 4);
            imageline($im, 10, 10, $w - 10, $h - 10, imagecolorallocate($im, ...$rgb));
        }
        ob_start();
        imagepng($im);
        $png = ob_get_clean();

        return 'data:image/png;base64,' . base64_encode($png);
    }

    private function member(string $first = 'Test', string $last = 'Student', string $role = 'student'): User
    {
        static $n = 0;
        $n++;
        $student = Student::create([
            'student_number' => "9900-" . str_pad((string) $n, 4, '0', STR_PAD_LEFT),
            'first_name' => $first, 'last_name' => $last, 'middle_name' => 'Q',
            'program' => 'BSIT', 'year_level' => '2nd Year', 'block' => 'A',
        ]);

        return User::factory()->create([
            'role' => $role,
            'student_id' => $student->id,
            'name' => "$first $last",
            'is_active' => true,
        ]);
    }

    private function event(User $creator): Event
    {
        $ay = AcademicYear::firstOrCreate(['label' => '2026-2027'], ['year_start' => 2026, 'year_end' => 2027, 'is_active' => true]);

        return Event::create([
            'academic_year_id' => $ay->id, 'created_by' => $creator->id,
            'name' => 'Synthetic Tech Summit', 'event_type' => 'seminar', 'organizer' => 'COMSOC Test Org',
            'event_date' => '2026-11-05', 'starts_at' => '2026-11-05 09:00:00', 'ends_at' => '2026-11-05 12:00:00',
            'venue_name' => 'Test Hall', 'venue_address' => 'Bulan Campus',
            'is_published' => true, 'status' => 'published', 'attendance_enabled' => true,
        ]);
    }

    private function attend(Event $event, User $user, string $action = 'in', string $type = 'user'): void
    {
        static $s = [];
        $session = $s[$event->id] ??= AttendanceSession::create([
            'event_id' => $event->id, 'type' => 'morning_in', 'opens_at' => '08:00', 'closes_at' => '12:00',
        ]);
        AttendanceRecord::create([
            'event_id' => $event->id, 'attendance_session_id' => $session->id,
            'qr_token' => bin2hex(random_bytes(24)),
            'participant_type' => $type,
            'participant_id' => $type === 'user' ? $user->id : $user->student_id,
            'user_id' => $user->id, 'action' => $action, 'status' => 'present', 'scanned_at' => now(),
        ]);
    }

    private function register(User $u, ?string $data = null): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($u)->postJson(route('signature.store'), [
            'signature' => $data ?? $this->sigDataUrl(), 'consent' => true,
        ]);
    }

    // ---------- Authentication & authorization ----------

    public function test_guests_cannot_register_or_view_signatures(): void
    {
        $this->postJson(route('signature.store'), ['signature' => $this->sigDataUrl(), 'consent' => true])->assertUnauthorized();
        $this->getJson(route('signature.preview'))->assertUnauthorized();
        $this->getJson(route('signature.status'))->assertUnauthorized();
    }

    public function test_student_can_register_with_consent_and_status_updates(): void
    {
        $u = $this->member();
        $this->actingAs($u)->getJson(route('signature.status'))->assertJson(['registered' => false]);

        $this->register($u)->assertCreated()->assertJson(['registered' => true, 'version' => 1]);

        $sig = StudentSignature::where('user_id', $u->id)->firstOrFail();
        $this->assertNotNull($sig->consent_acknowledged_at);
        $this->assertSame('active', $sig->status);
        $this->assertTrue(AuditLog::where('action', 'signature.registered')->exists());
        $this->assertTrue(AuditLog::where('action', 'signature.consent_acknowledged')->exists());
    }

    public function test_consent_is_required_for_initial_signature(): void
    {
        $u = $this->member();
        $this->actingAs($u)->postJson(route('signature.store'), ['signature' => $this->sigDataUrl()])
            ->assertStatus(422)->assertJsonValidationErrors('consent');
        $this->assertSame(0, StudentSignature::count());
    }

    public function test_user_id_in_request_is_ignored_and_other_students_signature_is_untouched(): void
    {
        $a = $this->member('Ann', 'Alpha');
        $b = $this->member('Ben', 'Bravo');
        $this->register($b);
        $before = StudentSignature::where('user_id', $b->id)->first()->encrypted_payload;

        // A tries to replace B's signature by injecting ids.
        $this->actingAs($a)->postJson(route('signature.store'), [
            'signature' => $this->sigDataUrl(), 'consent' => true, 'user_id' => $b->id,
        ])->assertCreated();
        $this->actingAs($a)->putJson(route('signature.replace'), [
            'signature' => $this->sigDataUrl(), 'user_id' => $b->id, 'id' => StudentSignature::where('user_id', $b->id)->value('id'),
        ])->assertOk();

        $this->assertSame(1, StudentSignature::where('user_id', $b->id)->count());
        $this->assertSame($before, StudentSignature::where('user_id', $b->id)->first()->encrypted_payload);
        $this->assertSame('active', StudentSignature::where('user_id', $b->id)->first()->status);
    }

    public function test_preview_returns_only_own_signature_and_never_json_ciphertext(): void
    {
        $a = $this->member('Ann', 'Alpha');
        $b = $this->member('Ben', 'Bravo');
        $this->register($a, $this->sigDataUrl(300, 100, [255, 0, 0]));
        $this->register($b, $this->sigDataUrl(300, 100, [0, 0, 255]));

        $r = $this->actingAs($a)->get(route('signature.preview'));
        $r->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertStringContainsString('no-store', $r->headers->get('Cache-Control'));
        $this->assertSame(
            app(SignatureVaultService::class)->decrypt(StudentSignature::where('user_id', $a->id)->first()),
            $r->getContent()
        );

        // Status/replace responses never include payload data.
        $status = $this->actingAs($a)->getJson(route('signature.status'))->getContent();
        $this->assertStringNotContainsString('encrypted_payload', $status);
        $this->assertStringNotContainsString('data:image', $status);
    }

    public function test_user_without_signature_gets_404_on_preview(): void
    {
        $this->actingAs($this->member())->get(route('signature.preview'))->assertNotFound();
    }

    // ---------- Encryption & validation ----------

    public function test_persisted_signature_is_encrypted_not_plaintext(): void
    {
        $u = $this->member();
        $data = $this->sigDataUrl();
        $this->register($u, $data);

        $raw = DB::table('student_signatures')->where('user_id', $u->id)->value('encrypted_payload');
        $png = base64_decode(explode(',', $data)[1]);
        $this->assertStringNotContainsString('PNG', $raw);
        $this->assertStringNotContainsString(base64_encode(substr($png, 0, 40)), $raw);
        $this->assertStringNotContainsString(substr(explode(',', $data)[1], 20, 60), $raw);

        // Round-trips only through the vault, to a valid PNG.
        $dec = app(SignatureVaultService::class)->decrypt(StudentSignature::first());
        $this->assertStringStartsWith("\x89PNG", $dec);
    }

    public function test_tampered_ciphertext_is_rejected_safely(): void
    {
        $u = $this->member();
        $this->register($u);
        $sig = StudentSignature::first();
        $sig->update(['encrypted_payload' => substr($sig->encrypted_payload, 0, -6) . 'AAAAAA']);

        $this->assertNull(app(SignatureVaultService::class)->decrypt($sig->fresh()));
        $this->actingAs($u)->get(route('signature.preview'))->assertNotFound();
    }

    public function test_malformed_unsupported_and_oversized_payloads_are_rejected(): void
    {
        $u = $this->member();
        $bad = [
            'not a data url',
            'data:image/jpeg;base64,' . base64_encode('x'),
            'data:image/svg+xml;base64,' . base64_encode('<svg onload=alert(1)/>'),
            'data:image/png;base64,@@@@',
            'data:image/png;base64,' . base64_encode('this is not a png at all'),
            'data:image/png;base64,' . base64_encode("\x89PNG\r\n\x1a\n" . str_repeat('x', 50)),
        ];
        foreach ($bad as $p) {
            $this->register($u, $p)->assertStatus(422);
        }
        $this->register($u, $this->sigDataUrl(30, 10))->assertStatus(422);          // too small
        $this->register($u, $this->sigDataUrl(2400, 100))->assertStatus(422);       // too wide
        $this->register($u, 'data:image/png;base64,' . str_repeat('A', 500000))->assertStatus(422); // oversized
        $this->register($u, $this->sigDataUrl(300, 100, [0, 0, 0], true))->assertStatus(422);        // blank
        $this->assertSame(0, StudentSignature::count());
    }

    public function test_replacement_supersedes_but_preserves_previous_version(): void
    {
        $u = $this->member();
        $this->register($u)->assertCreated();
        $this->actingAs($u)->postJson(route('signature.store'), ['signature' => $this->sigDataUrl(), 'consent' => true])
            ->assertStatus(409); // must use replace flow

        $this->actingAs($u)->putJson(route('signature.replace'), ['signature' => $this->sigDataUrl(300, 100, [0, 90, 0])])
            ->assertOk()->assertJson(['version' => 2]);

        $this->assertSame(1, StudentSignature::where('user_id', $u->id)->where('status', 'active')->count());
        $old = StudentSignature::where('user_id', $u->id)->where('version', 1)->first();
        $this->assertSame('superseded', $old->status);
        $this->assertNotNull($old->replaced_at);
        $this->assertTrue(AuditLog::where('action', 'signature.replaced')->exists());
    }

    public function test_replace_requires_existing_signature(): void
    {
        $this->actingAs($this->member())->putJson(route('signature.replace'), ['signature' => $this->sigDataUrl()])->assertStatus(409);
    }

    public function test_encryption_keys_never_reach_the_client(): void
    {
        $u = $this->member();
        $this->register($u);
        $html = $this->actingAs($u)->get(route('signature.show'))->assertOk()->getContent();
        $this->assertStringNotContainsString(config('app.key'), $html);
        $dedicated = 'base64:' . base64_encode(random_bytes(32));
        $this->assertStringNotContainsString($dedicated, $html);
        $this->assertStringNotContainsString(substr(config('app.key'), 7), $html);
        $this->assertStringNotContainsString('encrypted_payload', $html);
        $this->assertStringContainsString('signatureRegistration', $html);
    }

    public function test_dedicated_key_and_rotation(): void
    {
        $old = 'base64:' . base64_encode(random_bytes(32));
        $new = 'base64:' . base64_encode(random_bytes(32));
        config(['signatures.key' => $old]);
        $u = $this->member();
        $this->register($u);

        config(['signatures.key' => $new, 'signatures.previous_keys' => [$old]]);
        $vault = new SignatureVaultService();
        $this->assertNotNull($vault->decrypt(StudentSignature::first()));

        config(['signatures.previous_keys' => []]); // retired key removed => unreadable, handled safely
        $this->assertNull((new SignatureVaultService())->decrypt(StudentSignature::first()));
    }

    // ---------- Export authorization ----------

    public function test_unauthorized_users_cannot_export_or_check(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $event = $this->event($admin);
        $student = $this->member();

        $this->get(route('attendance-sheet.download', $event))->assertRedirect(route('login'));
        $this->actingAs($student)->get(route('attendance-sheet.download', $event))->assertForbidden();
        $this->actingAs($student)->getJson(route('attendance-sheet.check', $event))->assertForbidden();

        $unassigned = User::factory()->create(['role' => 'event_organizer']);
        $this->actingAs($unassigned)->get(route('attendance-sheet.download', $event))->assertForbidden();
        $this->assertSame(0, AttendanceSheetIssuance::count());
    }

    public function test_authorized_admin_and_assigned_organizer_can_export(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $event = $this->event($admin);
        $this->attend($event, $this->member());

        $r = $this->actingAs($admin)->get(route('attendance-sheet.download', $event));
        $r->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $r->getContent());
        $this->assertTrue(AuditLog::where('action', 'attendance_sheet.exported')->exists());

        $org = User::factory()->create(['role' => 'event_organizer']);
        $event2 = $this->event($admin);
        $event2->organizers()->attach($org->id);
        $this->actingAs($org)->get(route('attendance-sheet.download', $event2))->assertOk();
    }

    // ---------- Attendance & export content ----------

    public function test_only_eligible_attendance_records_are_included_and_columns_map_correctly(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $event = $this->event($admin);
        $a = $this->member('Alice', 'Zulu');
        $b = $this->member('Bob', 'Alpha');
        $registeredOnly = $this->member('Carl', 'Signer');   // has signature, never attended
        $outOnly = $this->member('Dina', 'Outonly');          // only a time-out record
        $other = $this->member('Eve', 'OtherEvent');
        $this->register($registeredOnly);
        $this->attend($event, $a);
        $this->attend($event, $a, 'out');
        $this->attend($event, $b);
        $this->attend($event, $outOnly, 'out');
        $this->attend($this->event($admin), $other);

        EventRegistration::create(['event_id' => $event->id, 'user_id' => $b->id, 'full_name' => 'Bob', 'email' => 'b@example.test', 'phone' => '09170000001', 'status' => 'approved']);
        $b->update(['officer_position' => 'Treasurer']);

        $rows = app(AttendanceSheetService::class)->rows($event);
        $this->assertCount(2, $rows);                       // duplicate in/out collapsed; non-attendees excluded
        $this->assertSame('Alpha, Bob Q.', $rows[0]['name']); // sorted
        $this->assertSame('Treasurer', $rows[0]['position']);
        $this->assertSame('BSIT 2nd Year - A', $rows[0]['course']);
        $this->assertSame('09170000001', $rows[0]['contact']);
        $this->assertSame('Member', $rows[1]['position']);
        $this->assertSame('', $rows[1]['contact']);          // not fabricated
    }

    public function test_kiosk_style_student_records_are_included(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $event = $this->event($admin);
        $u = $this->member('Kay', 'Kiosk');
        $this->attend($event, $u, 'in', 'student');
        $rows = app(AttendanceSheetService::class)->rows($event);
        $this->assertCount(1, $rows);
        $this->assertSame($u->id, $rows[0]['user_id']);
    }

    public function test_activity_info_and_signatures_land_in_the_right_rows_and_missing_is_blank(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $event = $this->event($admin);
        $red = $this->member('Rita', 'Alpha');
        $blue = $this->member('Ben', 'Bravo');
        $none = $this->member('Nora', 'Charlie');
        $this->register($red, $this->sigDataUrl(300, 100, [255, 0, 0]));
        $this->register($blue, $this->sigDataUrl(300, 100, [0, 0, 255]));
        foreach ([$none, $blue, $red] as $u) { // attendance order differs from sort order
            $this->attend($event, $u);
        }

        $svc = app(SignatureVaultService::class);
        $sheet = app(AttendanceSheetService::class);
        $check = $sheet->check($event);
        $this->assertSame(['Charlie, Nora Q.'], $check['missing']->pluck('name')->all());

        $html = view('pdf.attendance-sheet', $sheet->viewData($event, $check['rows'], $check['signatures']))->render();

        foreach (['COMSOC Test Org', 'Synthetic Tech Summit', 'November 5, 2026, 9:00 AM - 12:00 PM', 'Test Hall, Bulan Campus',
            'FM-BUC-SCA-017', 'October 19, 2026', 'Republic of the Philippines', 'Sorsogon State University',
            'STUDENT COUNCIL AFFAIRS', 'ATTENDANCE SHEET FOR STUDENTS’ ACTIVITY'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        $this->assertMatchesRegularExpression('/No\.<\/th><th>Name<\/th><th>Position<\/th><th>Course\/Yr\. Section<\/th><th>Contact Number<\/th><th>Signature/', $html);

        preg_match_all('#<tbody>(.*)</tbody>#s', $html, $m);
        preg_match_all('#<tr>(.*?)</tr>#s', $m[1][0], $trs);
        $this->assertCount(3, $trs[1]);
        $png = fn (User $u) => base64_encode($svc->decrypt(StudentSignature::where('user_id', $u->id)->first()));
        $this->assertStringContainsString('Alpha, Rita', $trs[1][0]);
        $this->assertStringContainsString($png($red), $trs[1][0]);
        $this->assertStringNotContainsString($png($blue), $trs[1][0]);
        $this->assertStringContainsString('Bravo, Ben', $trs[1][1]);
        $this->assertStringContainsString($png($blue), $trs[1][1]);
        $this->assertStringContainsString('Charlie, Nora', $trs[1][2]);
        $this->assertStringNotContainsString('<img', $trs[1][2]); // blank, not fabricated
    }

    public function test_attendees_without_signature_are_not_excluded_and_check_endpoint_warns(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $event = $this->event($admin);
        $u = $this->member('Nora', 'Nosig');
        $this->attend($event, $u);

        $this->actingAs($admin)->getJson(route('attendance-sheet.check', $event))
            ->assertOk()->assertJson(['attendee_count' => 1, 'missing_count' => 1, 'missing' => ['Nosig, Nora Q.']]);
        $this->actingAs($admin)->get(route('attendance-sheet.download', $event))->assertOk();
        // Registering a signature never creates an attendance record.
        $this->register($u);
        $this->assertSame(1, AttendanceRecord::where('event_id', $event->id)->count());
    }

    public function test_finalized_sheet_is_not_silently_changed_and_reissue_needs_reason(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $event = $this->event($admin);
        $u = $this->member();
        $this->attend($event, $u);
        $this->register($u, $this->sigDataUrl(300, 100, [255, 0, 0]));

        $first = $this->actingAs($admin)->get(route('attendance-sheet.download', $event))->assertOk()->getContent();
        $issuance = AttendanceSheetIssuance::firstOrFail();
        $this->assertSame(hash('sha256', $first), $issuance->pdf_sha256);
        $this->assertSame($u->id, array_key_first($issuance->signature_refs));
        $v1 = StudentSignature::where('user_id', $u->id)->first();
        $this->assertSame($v1->id, $issuance->signature_refs[$u->id]);

        // Archive is encrypted and private.
        $stored = Storage::disk('local')->get($issuance->storage_path);
        $this->assertStringNotContainsString('%PDF', $stored);

        // Student replaces signature afterwards.
        $this->actingAs($u)->putJson(route('signature.replace'), ['signature' => $this->sigDataUrl(300, 100, [0, 0, 255])])->assertOk();
        $this->assertNotNull(StudentSignature::find($v1->id)); // old version retained

        // Re-download returns byte-identical issued copy.
        $again = $this->actingAs($admin)->get(route('attendance-sheet.download', $event))->assertOk()->getContent();
        $this->assertSame($first, $again);
        $this->assertSame(1, AttendanceSheetIssuance::count());

        // Reissue without reason is rejected; with a reason creates a linked, audited issuance.
        $this->actingAs($admin)->get(route('attendance-sheet.download', ['event' => $event, 'reissue' => 1]))->assertSessionHasErrors('reason');
        $this->actingAs($admin)->get(route('attendance-sheet.download', ['event' => $event, 'reissue' => 1, 'reason' => 'Student updated signature']))->assertOk();
        $this->assertSame(2, AttendanceSheetIssuance::count());
        $second = AttendanceSheetIssuance::latest('issued_at')->latest('id')->get()->firstWhere('reissue_of_id', $issuance->id);
        $this->assertNotNull($second);
        $this->assertNotSame($v1->id, $second->signature_refs[$u->id]);
        $this->assertTrue(AuditLog::where('action', 'attendance_sheet.reissued')->exists());
    }

    public function test_tampered_archive_is_refused(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $event = $this->event($admin);
        $this->attend($event, $this->member());
        $this->actingAs($admin)->get(route('attendance-sheet.download', $event))->assertOk();
        $iss = AttendanceSheetIssuance::firstOrFail();
        Storage::disk('local')->put($iss->storage_path, 'garbage');

        $this->actingAs($admin)->get(route('attendance-sheet.download', $event))->assertStatus(500);
    }

    public function test_audit_logs_contain_no_plaintext_signature_or_keys(): void
    {
        $u = $this->member();
        $this->register($u);
        $dump = AuditLog::all()->toJson();
        $this->assertStringNotContainsString('data:image', $dump);
        $this->assertStringNotContainsString(config('app.key'), $dump);
        $this->assertStringNotContainsString(StudentSignature::first()->getRawOriginal('encrypted_payload'), $dump);
    }

    public function test_multi_page_pdf_generates_with_repeating_header_markup(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $event = $this->event($admin);
        for ($i = 0; $i < 45; $i++) {
            $u = $this->member("Person$i", 'Multi' . str_pad((string) $i, 2, '0', STR_PAD_LEFT));
            $this->attend($event, $u);
            if ($i % 2 === 0) {
                $this->register($u);
            }
        }
        $sheet = app(AttendanceSheetService::class);
        $c = $sheet->check($event);
        $pdf = $sheet->render($event, $c['rows'], $c['signatures']);
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThanOrEqual(2, preg_match_all('#/Type\s*/Page[^s]#', $pdf));
        $html = view('pdf.attendance-sheet', $sheet->viewData($event, $c['rows'], $c['signatures']))->render();
        $this->assertStringContainsString('<thead>', $html); // DomPDF repeats thead on each page
        $this->assertStringContainsString('page-break-inside: avoid', $html);
        file_put_contents(storage_path('app/sample-attendance-sheet.pdf'), $pdf); // synthetic data only
    }

    public function test_organizer_access_matrix(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $event = $this->event($admin);
        $this->attend($event, $this->member());

        // 1. Super Admin has access
        $this->actingAs($admin)->get(route('attendance-sheet.download', $event))->assertOk();

        // 2. Assigned Event Organizer has access
        $assignedOrg = User::factory()->create(['role' => 'event_organizer']);
        $event->organizers()->attach($assignedOrg->id);
        $this->actingAs($assignedOrg)->get(route('attendance-sheet.download', $event))->assertOk();
        $this->actingAs($assignedOrg)->getJson(route('attendance-sheet.check', $event))->assertOk();

        // 3. Unassigned Event Organizer is forbidden
        $unassignedOrg = User::factory()->create(['role' => 'event_organizer']);
        $this->actingAs($unassignedOrg)->get(route('attendance-sheet.download', $event))->assertForbidden();
        $this->actingAs($unassignedOrg)->getJson(route('attendance-sheet.check', $event))->assertForbidden();

        // 4. Ordinary member / student is forbidden
        $student = $this->member();
        $this->actingAs($student)->get(route('attendance-sheet.download', $event))->assertForbidden();
        $this->actingAs($student)->getJson(route('attendance-sheet.check', $event))->assertForbidden();

        // 5. Unauthenticated guest is redirected to login
        auth()->logout();
        $this->get(route('attendance-sheet.download', $event))->assertRedirect(route('login'));
    }

    public function test_signature_status_method_returns_correct_states(): void
    {
        $vault = app(SignatureVaultService::class);
        $user = $this->member('Status', 'Test');

        // State 1: Required
        $status = $vault->signatureStatus($user);
        $this->assertSame('required', $status['state']);
        $this->assertSame('Signature Required', $status['label']);
        $this->assertFalse($status['is_usable']);
        $this->assertNull($status['signature']);

        // State 2: Registered
        $sig = $vault->store($user, $this->sigDataUrl(300, 100, [10, 20, 30]), false);
        $status = $vault->signatureStatus($user);
        $this->assertSame('registered', $status['state']);
        $this->assertSame('Signature Registered', $status['label']);
        $this->assertTrue($status['is_usable']);
        $this->assertSame(1, $status['version']);
        $this->assertNotNull($status['signature']);

        // State 3: Unavailable (corrupted encrypted payload)
        DB::table('student_signatures')->where('id', $sig->id)->update([
            'encrypted_payload' => 'invalid-payload-that-cannot-be-decrypted',
        ]);
        $status = $vault->signatureStatus($user);
        $this->assertSame('unavailable', $status['state']);
        $this->assertSame('Signature Unavailable', $status['label']);
        $this->assertFalse($status['is_usable']);
    }

    public function test_persistent_signature_status_rendered_on_dashboard_and_profile(): void
    {
        $user = $this->member('Display', 'Student');
        $vault = app(SignatureVaultService::class);

        // Before registration -> shows Signature Required
        $resDash = $this->actingAs($user)->get(route('dashboard'));
        $resDash->assertOk();
        $resDash->assertSee('Signature Required');
        $resDash->assertSee('id="dashboard-signature-status-badge"', false);
        $resDash->assertSee(route('signature.show'));

        $resProfile = $this->actingAs($user)->get(route('profile.edit'));
        $resProfile->assertOk();
        $resProfile->assertSee('Signature Required');
        $resProfile->assertSee('id="profile-signature-status-card"', false);

        // After registration -> shows Signature Registered
        $vault->store($user, $this->sigDataUrl(300, 100, [10, 20, 30]), false);

        $resDash2 = $this->actingAs($user)->get(route('dashboard'));
        $resDash2->assertOk();
        $resDash2->assertSee('Signature Registered');

        $resProfile2 = $this->actingAs($user)->get(route('profile.edit'));
        $resProfile2->assertOk();
        $resProfile2->assertSee('Signature Registered');
    }

    public function test_attendance_sheet_check_endpoint_returns_accurate_readiness_summary(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $event = $this->event($admin);

        $vault = app(SignatureVaultService::class);
        $studentA = $this->member('Alice', 'Signer');
        $studentB = $this->member('Bob', 'Signer');
        $studentC = $this->member('Charlie', 'Missing');

        $vault->store($studentA, $this->sigDataUrl(300, 100, [10, 20, 30]), false);
        $vault->store($studentB, $this->sigDataUrl(300, 100, [20, 30, 40]), false);
        // studentC has NO signature registered

        $this->attend($event, $studentA);
        $this->attend($event, $studentB);
        $this->attend($event, $studentC);

        $response = $this->actingAs($admin)->getJson(route('attendance-sheet.check', $event));
        $response->assertOk()
            ->assertJson([
                'attendee_count' => 3,
                'registered_count' => 2,
                'missing_count' => 1,
            ]);

        $missingNames = $response->json('missing');
        $this->assertCount(1, $missingNames);
        $studentCAttendeeName = $studentC->student
            ? trim($studentC->student->last_name . ', ' . $studentC->student->first_name . ($studentC->student->middle_name ? ' ' . mb_substr($studentC->student->middle_name, 0, 1) . '.' : ''))
            : $studentC->name;
        $this->assertContains($studentCAttendeeName, $missingNames);

        $studentAAttendeeName = $studentA->student
            ? trim($studentA->student->last_name . ', ' . $studentA->student->first_name . ($studentA->student->middle_name ? ' ' . mb_substr($studentA->student->middle_name, 0, 1) . '.' : ''))
            : $studentA->name;
        $this->assertNotContains($studentAAttendeeName, $missingNames);
    }

    public function test_export_leaves_blank_cell_for_missing_signatures_without_omitting_attendee(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $event = $this->event($admin);

        $vault = app(SignatureVaultService::class);
        $studentWithSig = $this->member('Registered', 'Student');
        $studentWithoutSig = $this->member('Unregistered', 'Student');

        $vault->store($studentWithSig, $this->sigDataUrl(300, 100, [10, 20, 30]), false);

        $this->attend($event, $studentWithSig);
        $this->attend($event, $studentWithoutSig);

        $sheets = app(AttendanceSheetService::class);
        $c = $sheets->check($event);

        $this->assertCount(2, $c['rows']);
        $this->assertCount(1, $c['missing']);

        // Render HTML view
        $html = view('pdf.attendance-sheet', $sheets->viewData($event, $c['rows'], $c['signatures']))->render();

        // Both attendees are included (formatted as "Last, First MiddleInitial.")
        $this->assertStringContainsString('Student, Registered Q.', $html);
        $this->assertStringContainsString('Student, Unregistered Q.', $html);

        // One signature td contains an img tag, one signature td is empty
        $this->assertSame(1, substr_count($html, '<td class="sig"><img src="data:image/png;base64,'));
        $this->assertSame(1, substr_count($html, '<td class="sig"></td>'));
    }

    public function test_student_can_reset_active_signature(): void
    {
        $user = $this->member('Reset', 'Tester');
        $this->register($user);

        $this->assertDatabaseHas('student_signatures', [
            'user_id' => $user->id,
            'version' => 1,
            'status' => 'active',
        ]);

        $res = $this->actingAs($user)->deleteJson(route('signature.reset'));
        $res->assertOk()
            ->assertJson([
                'registered' => false,
                'state' => 'required',
                'label' => 'Signature Required',
                'version' => null,
            ]);

        // Old signature is preserved and marked superseded (never deleted)
        $this->assertDatabaseHas('student_signatures', [
            'user_id' => $user->id,
            'version' => 1,
            'status' => 'superseded',
        ]);

        $sig = StudentSignature::where('user_id', $user->id)->first();
        $this->assertNotNull($sig->replaced_at);

        // Audit log created for signature.reset
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'signature.reset',
        ]);
    }

    public function test_student_can_register_new_signature_after_reset(): void
    {
        $user = $this->member('Cycle', 'Tester');
        $this->register($user);

        // Reset
        $this->actingAs($user)->deleteJson(route('signature.reset'))->assertOk();

        // Register new signature
        $newSigData = $this->sigDataUrl(320, 110, [50, 100, 150]);
        $res = $this->actingAs($user)->postJson(route('signature.store'), [
            'signature' => $newSigData,
            'consent' => true,
        ]);

        $res->assertCreated()
            ->assertJson([
                'registered' => true,
                'state' => 'registered',
                'version' => 2,
            ]);

        // Both versions preserved
        $this->assertSame(2, StudentSignature::where('user_id', $user->id)->count());

        $this->assertDatabaseHas('student_signatures', [
            'user_id' => $user->id,
            'version' => 1,
            'status' => 'superseded',
        ]);

        $this->assertDatabaseHas('student_signatures', [
            'user_id' => $user->id,
            'version' => 2,
            'status' => 'active',
        ]);
    }

    public function test_reset_returns_404_when_no_active_signature_exists(): void
    {
        $user = $this->member('NoSig', 'User');

        $res = $this->actingAs($user)->deleteJson(route('signature.reset'));
        $res->assertNotFound()
            ->assertJson([
                'registered' => false,
                'state' => 'required',
            ]);
    }

    public function test_guest_cannot_reset_signature(): void
    {
        $res = $this->deleteJson(route('signature.reset'));
        $res->assertUnauthorized();
    }

    public function test_user_cannot_reset_another_users_signature(): void
    {
        $victim = $this->member('Victim', 'User');
        $attacker = $this->member('Attacker', 'User');

        $this->register($victim);

        // Attempting to send someone else's user_id in the request payload has zero effect,
        // because the controller derives ownership exclusively from auth()->user().
        $res = $this->actingAs($attacker)->deleteJson(route('signature.reset'), [
            'user_id' => $victim->id,
            'target_user_id' => $victim->id,
        ]);

        // Attacker has no active signature of their own, so they get 404
        $res->assertNotFound();

        // Victim's signature remains active and untouched
        $this->assertDatabaseHas('student_signatures', [
            'user_id' => $victim->id,
            'version' => 1,
            'status' => 'active',
        ]);
    }

    public function test_consecutive_or_repeated_reset_requests_are_handled_safely(): void
    {
        $user = $this->member('Repeat', 'Resetter');
        $this->register($user);

        // First reset succeeds
        $res1 = $this->actingAs($user)->deleteJson(route('signature.reset'));
        $res1->assertOk();

        // Immediate second reset returns 404 safely without corrupting history
        $res2 = $this->actingAs($user)->deleteJson(route('signature.reset'));
        $res2->assertNotFound();

        $this->assertSame(1, StudentSignature::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('student_signatures', [
            'user_id' => $user->id,
            'version' => 1,
            'status' => 'superseded',
        ]);
    }

    public function test_resetting_signature_causes_student_to_appear_in_missing_signatures_check(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $event = $this->event($admin);
        $student = $this->member('Reset', 'Attendee');

        $this->register($student);
        $this->attend($event, $student);

        // Check readiness: student is registered
        $check1 = $this->actingAs($admin)->getJson(route('attendance-sheet.check', $event));
        $check1->assertOk()
            ->assertJson([
                'attendee_count' => 1,
                'registered_count' => 1,
                'missing_count' => 0,
            ]);

        // Student resets signature
        $this->actingAs($student)->deleteJson(route('signature.reset'))->assertOk();

        // Check readiness: student attendance is preserved, but signature is now missing
        $check2 = $this->actingAs($admin)->getJson(route('attendance-sheet.check', $event));
        $check2->assertOk()
            ->assertJson([
                'attendee_count' => 1,
                'registered_count' => 0,
                'missing_count' => 1,
            ]);

        // Attendance record itself was NOT altered
        $this->assertDatabaseHas('attendance_records', [
            'event_id' => $event->id,
            'user_id' => $student->id,
            'status' => 'present',
        ]);
    }

    public function test_archived_issued_pdfs_remain_intact_and_unaffected_by_signature_reset(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $event = $this->event($admin);
        $student = $this->member('Archive', 'TestStudent');

        $this->register($student);
        $this->attend($event, $student);

        // Issue initial attendance sheet PDF
        $sheets = app(AttendanceSheetService::class);
        [$issuance, $pdf, $missing] = $sheets->issue($event, $admin, 'Initial official issuance');
        $this->assertNotNull($issuance);
        $this->assertNotNull($issuance->storage_path);
        $this->assertTrue(Storage::disk('local')->exists($issuance->storage_path));

        $originalEncryptedBytes = Storage::disk('local')->get($issuance->storage_path);
        $originalHash = $issuance->pdf_sha256;

        // Student resets their signature
        $this->actingAs($student)->deleteJson(route('signature.reset'))->assertOk();

        // Student's active signature is gone
        $vault = app(SignatureVaultService::class);
        $this->assertNull($vault->activeRecord($student));

        // The archived PDF file and issuance record remain completely identical and unaltered
        $issuance->refresh();
        $this->assertSame($originalHash, $issuance->pdf_sha256);
        $this->assertSame($originalEncryptedBytes, Storage::disk('local')->get($issuance->storage_path));
        $this->assertSame($pdf, $sheets->readArchive($issuance));
    }

    public function test_dashboard_renders_signature_status_section_correctly(): void
    {
        $student = $this->member('Dash', 'Tester');

        // Initially required
        $res1 = $this->actingAs($student)->get(route('dashboard'));
        $res1->assertOk();
        $res1->assertSee('id="dashboard-signature-status-section"', false);
        $res1->assertSee('dashboard-register-signature-btn');

        // Register signature
        $this->register($student);

        // Now registered
        $res2 = $this->actingAs($student)->get(route('dashboard'));
        $res2->assertOk();
        $res2->assertSee('id="dashboard-signature-status-section"', false);
        $res2->assertSee('dashboard-view-signature-btn');
        $res2->assertSee('dashboard-reset-signature-btn');
        $res2->assertSee('dashboard-register-new-signature-btn');
    }
}