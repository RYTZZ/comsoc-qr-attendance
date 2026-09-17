<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Membership;
use App\Models\Student;
use App\Models\User;
use App\Services\MemberCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StudentYearLevelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->academicYear = AcademicYear::create([
            'label' => 'AY 2025-2026',
            'year_start' => 2025,
            'year_end' => 2026,
            'is_active' => true,
        ]);
    }

    public function test_year_level_normalization_helper(): void
    {
        $this->assertEquals('1st Year', Student::normalizeYearLevel('1st Year'));
        $this->assertEquals('1st Year', Student::normalizeYearLevel('1'));
        $this->assertEquals('1st Year', Student::normalizeYearLevel('1st'));
        $this->assertEquals('1st Year', Student::normalizeYearLevel('1st yr'));
        $this->assertEquals('1st Year', Student::normalizeYearLevel('First Year'));

        $this->assertEquals('2nd Year', Student::normalizeYearLevel('2nd Year'));
        $this->assertEquals('2nd Year', Student::normalizeYearLevel('2'));
        $this->assertEquals('2nd Year', Student::normalizeYearLevel('2nd'));
        $this->assertEquals('2nd Year', Student::normalizeYearLevel('second year'));

        $this->assertEquals('3rd Year', Student::normalizeYearLevel('3rd Year'));
        $this->assertEquals('3rd Year', Student::normalizeYearLevel('3'));
        $this->assertEquals('3rd Year', Student::normalizeYearLevel('3rd'));
        $this->assertEquals('3rd Year', Student::normalizeYearLevel('third yr'));

        $this->assertEquals('4th Year', Student::normalizeYearLevel('4th Year'));
        $this->assertEquals('4th Year', Student::normalizeYearLevel('4'));
        $this->assertEquals('4th Year', Student::normalizeYearLevel('4th'));
        $this->assertEquals('4th Year', Student::normalizeYearLevel('fourth year'));

        $this->assertNull(Student::normalizeYearLevel('5th Year'));
        $this->assertNull(Student::normalizeYearLevel(''));
        $this->assertNull(Student::normalizeYearLevel(null));
    }

    public function test_masterlist_import_assigns_year_level(): void
    {
        $csvContent = "Student Number,Last Name,First Name,Middle Name,Program,Year Level\n";
        $csvContent .= "2024-0001,Dela Cruz,Juan,,BSIT,3rd Year\n";
        $csvContent .= "2024-0002,Santos,Maria,,BSCS,1\n";

        $file = UploadedFile::fake()->createWithContent('masterlist.csv', $csvContent);

        $previewResponse = $this->actingAs($this->admin)
            ->post(route('admin.masterlist.preview'), [
                'file' => $file,
                'academic_year_id' => $this->academicYear->id,
            ]);

        $previewResponse->assertOk();
        $previewResponse->assertViewHas('preview');

        $importResponse = $this->actingAs($this->admin)
            ->post(route('admin.masterlist.import'));

        $importResponse->assertRedirect(route('admin.students.index'));

        $student1 = Student::where('student_number', '2024-0001')->first();
        $this->assertNotNull($student1);
        $this->assertEquals('3rd Year', $student1->year_level);
        $this->assertEquals('BSIT', $student1->program);

        $student2 = Student::where('student_number', '2024-0002')->first();
        $this->assertNotNull($student2);
        $this->assertEquals('1st Year', $student2->year_level);
        $this->assertEquals('BSCS', $student2->program);
    }

    public function test_masterlist_import_does_not_overwrite_existing_year_level_if_new_is_empty(): void
    {
        $existingStudent = Student::create([
            'student_number' => '2023-9999',
            'first_name' => 'Existing',
            'last_name' => 'Student',
            'program' => 'BSIT',
            'year_level' => '3rd Year',
        ]);

        $csvContent = "Student Number,Name,Program,Year Level\n";
        $csvContent .= "2023-9999,Existing, Student,BSIT,\n";

        $file = UploadedFile::fake()->createWithContent('masterlist.csv', $csvContent);

        $this->actingAs($this->admin)
            ->post(route('admin.masterlist.preview'), [
                'file' => $file,
                'academic_year_id' => $this->academicYear->id,
            ]);

        $this->actingAs($this->admin)
            ->post(route('admin.masterlist.import'));

        $existingStudent->refresh();
        $this->assertEquals('3rd Year', $existingStudent->year_level);
    }

    public function test_masterlist_import_identifies_missing_year_level_as_invalid_and_allows_correction(): void
    {
        $csvContent = "Student Number,Name,Program,Year Level\n";
        $csvContent .= "2025-0010,Reyes, Pedro,BSIT,invalid_year\n";

        $file = UploadedFile::fake()->createWithContent('masterlist.csv', $csvContent);

        $previewResponse = $this->actingAs($this->admin)
            ->post(route('admin.masterlist.preview'), [
                'file' => $file,
                'academic_year_id' => $this->academicYear->id,
            ]);

        $preview = $previewResponse->viewData('preview');
        $this->assertCount(1, $preview['invalid']);
        $this->assertEquals('2025-0010', $preview['invalid'][0]['student_number']);

        $importResponse = $this->actingAs($this->admin)
            ->post(route('admin.masterlist.import'), [
                'corrections' => [
                    '2025-0010' => [
                        'year_level' => '2nd Year',
                    ],
                ],
            ]);

        $importResponse->assertRedirect(route('admin.students.index'));

        $student = Student::where('student_number', '2025-0010')->first();
        $this->assertNotNull($student);
        $this->assertEquals('2nd Year', $student->year_level);
    }

    public function test_student_year_level_can_be_updated_by_admin(): void
    {
        $student = Student::create([
            'student_number' => '2023-1111',
            'first_name' => 'Ana',
            'last_name' => 'Cruz',
            'program' => 'BSIT',
            'year_level' => '1st Year',
        ]);

        $response = $this->actingAs($this->admin)
            ->patch(route('admin.students.update', $student), [
                'first_name' => 'Ana',
                'last_name' => 'Cruz',
                'program' => 'BSIT',
                'year_level' => '2nd Year',
            ]);

        $response->assertSessionHasNoErrors();
        $student->refresh();
        $this->assertEquals('2nd Year', $student->year_level);
    }

    public function test_student_and_membership_filtering_by_year_level(): void
    {
        $student1 = Student::create([
            'student_number' => '2024-1001',
            'first_name' => 'One',
            'last_name' => 'First',
            'year_level' => '1st Year',
        ]);
        $student2 = Student::create([
            'student_number' => '2024-1002',
            'first_name' => 'Two',
            'last_name' => 'Second',
            'year_level' => '2nd Year',
        ]);

        Membership::create([
            'student_id' => $student1->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => 'active',
        ]);
        Membership::create([
            'student_id' => $student2->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => 'active',
        ]);

        $studentFilterResponse = $this->actingAs($this->admin)
            ->get(route('admin.students.index', ['year_level' => '1st Year']));

        $studentFilterResponse->assertOk();
        $studentFilterResponse->assertSee('2024-1001');
        $studentFilterResponse->assertDontSee('2024-1002');

        $membershipFilterResponse = $this->actingAs($this->admin)
            ->get(route('admin.memberships.index', ['year_level' => '2nd Year']));

        $membershipFilterResponse->assertOk();
        $membershipFilterResponse->assertSee('2024-1002');
        $membershipFilterResponse->assertDontSee('2024-1001');
    }

    public function test_membership_card_generation_uses_student_year_level(): void
    {
        $student = Student::create([
            'student_number' => '2023-10023-BN-0',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'program' => 'BSIT',
            'year_level' => '4th Year',
        ]);

        $membership = Membership::create([
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => 'active',
            'membership_number' => 'MEM-2025-0001',
        ]);

        $cardService = app(MemberCardService::class);
        $png = $cardService->generatePng($membership);

        $this->assertNotEmpty($png);
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $png);
    }

    public function test_admin_memberships_index_handles_missing_related_data_safely(): void
    {
        $studentWithoutData = Student::create([
            'student_number' => '2026-9999',
            'first_name' => 'Edge',
            'last_name' => 'Case',
            'program' => null,
            'year_level' => null,
        ]);

        $membership = Membership::create([
            'student_id' => $studentWithoutData->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => 'active',
            'membership_number' => 'MEM-2026-9999',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.memberships.index'));

        $response->assertOk();
        $response->assertSee('2026-9999');
        $response->assertSee('Edge Case');
        $response->assertSee('No Account');
        $response->assertSee('Not assigned');
        $response->assertSee('None');
        $response->assertSee('QR Missing');
    }
}
