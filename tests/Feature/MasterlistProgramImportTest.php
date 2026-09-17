<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MasterlistProgramImportTest extends TestCase
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
            'year_start' => 2025,
            'year_end' => 2026,
            'label' => 'A.Y. 2025-2026',
            'is_active' => true,
        ]);
    }

    public function test_program_normalization_helper(): void
    {
        $this->assertEquals('BSIT', Student::normalizeProgram('BSIT'));
        $this->assertEquals('BSIT', Student::normalizeProgram(' bsit '));
        $this->assertEquals('BSIT', Student::normalizeProgram('Bachelor of Science in Information Technology'));
        $this->assertEquals('BSCS', Student::normalizeProgram('BSCS'));
        $this->assertEquals('BSCS', Student::normalizeProgram('Bachelor of Science in Computer Science'));
        $this->assertEquals('BSIS', Student::normalizeProgram('BSIS'));
        $this->assertEquals('BSIS', Student::normalizeProgram('Bachelor of Science in Information Systems'));
        $this->assertEquals('BTVTEd', Student::normalizeProgram('BTVTEd'));
        $this->assertEquals('BTVTEd', Student::normalizeProgram('Bachelor of Technical-Vocational Teacher Education'));
        $this->assertEquals('BLIS', Student::normalizeProgram('BLIS'));
        $this->assertEquals('BLIS', Student::normalizeProgram('Bachelor of Library and Information Science'));
    }

    public function test_masterlist_preview_detects_program_and_headers(): void
    {
        $csvContent = "Student Number,Name,Program,Year Level\n";
        $csvContent .= "2023-001,\"Dela Cruz, Juan\",BSIT,3rd Year\n";
        $csvContent .= "2023-002,\"Santos, Pedro\",BSCS,2nd Year\n";
        $csvContent .= "2023-003,\"Luna, Antonio\",Bachelor of Science in Information Systems,1st Year\n";
        $csvContent .= "2023-004,\"Silang, Gabriela\",BTVTEd,4th Year\n";
        $csvContent .= "2023-005,\"Rizal, Jose\",BLIS,2nd Year\n";

        $file = UploadedFile::fake()->createWithContent('masterlist.csv', $csvContent);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.masterlist.preview'), [
                'file' => $file,
                'academic_year_id' => $this->academicYear->id,
            ]);

        $response->assertOk();
        $preview = $response->viewData('preview');

        $this->assertCount(5, $preview['new']);
        $this->assertEquals('BSIT', $preview['new'][0]['program']);
        $this->assertEquals('3rd Year', $preview['new'][0]['year_level']);
        $this->assertEquals('2023-001', $preview['new'][0]['student_number']);

        $this->assertEquals('BSCS', $preview['new'][1]['program']);
        $this->assertEquals('BSIS', $preview['new'][2]['program']);
        $this->assertEquals('BTVTEd', $preview['new'][3]['program']);
        $this->assertEquals('BLIS', $preview['new'][4]['program']);

        $response->assertSee('BSIT');
        $response->assertSee('BSCS');
        $response->assertSee('BSIS');
        $response->assertSee('BTVTEd');
        $response->assertSee('BLIS');
    }

    public function test_masterlist_preview_supports_various_program_header_variations(): void
    {
        $variations = [
            'Course',
            'Program / Course',
            'Program/Course',
            'Course / Program',
            'Degree Program',
            ' program ',
            'PROGRAM',
        ];

        foreach ($variations as $header) {
            $csvContent = "Student Number,Name,{$header},Year Level\n";
            $csvContent .= "2024-9001,\"Test, Student\",BSIT,1st Year\n";

            $file = UploadedFile::fake()->createWithContent('masterlist.csv', $csvContent);

            $response = $this->actingAs($this->admin)
                ->post(route('admin.masterlist.preview'), [
                    'file' => $file,
                    'academic_year_id' => $this->academicYear->id,
                ]);

            $response->assertOk();
            $preview = $response->viewData('preview');
            $this->assertEquals('BSIT', $preview['new'][0]['program'], "Failed detecting header: {$header}");
        }
    }

    public function test_masterlist_import_saves_program_to_database(): void
    {
        $csvContent = "Student Number,Name,Program / Course,Year Level\n";
        $csvContent .= "2023-001,\"Dela Cruz, Juan\",BSIT,3rd Year\n";
        $csvContent .= "2023-002,\"Santos, Pedro\",Bachelor of Science in Computer Science,2nd Year\n";

        $file = UploadedFile::fake()->createWithContent('masterlist.csv', $csvContent);

        $this->actingAs($this->admin)
            ->post(route('admin.masterlist.preview'), [
                'file' => $file,
                'academic_year_id' => $this->academicYear->id,
            ]);

        $this->actingAs($this->admin)
            ->post(route('admin.masterlist.import'));

        $s1 = Student::where('student_number', '2023-001')->first();
        $this->assertNotNull($s1);
        $this->assertEquals('BSIT', $s1->program);
        $this->assertEquals('3rd Year', $s1->year_level);

        $s2 = Student::where('student_number', '2023-002')->first();
        $this->assertNotNull($s2);
        $this->assertEquals('BSCS', $s2->program);
        $this->assertEquals('2nd Year', $s2->year_level);
    }
}
