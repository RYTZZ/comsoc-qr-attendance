<?php

namespace Tests\Feature;

use App\Models\OrganizationDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConstitutionAndByLawsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function createDummyPdf(): UploadedFile
    {
        $content = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n3 0 obj\n<< /Type /Page /Parent 2 0 R >>\nendobj\nxref\n0 4\n0000000000 65535 f \n0000000010 00000 n \n0000000060 00000 n \n0000000117 00000 n \ntrailer\n<< /Size 4 /Root 1 0 R >>\nstartxref\n168\n%%EOF";
        return UploadedFile::fake()->createWithContent('official_cbl.pdf', $content);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('constitution.index'));
        $response->assertRedirect(route('login'));

        $previewResponse = $this->get(route('constitution.preview'));
        $previewResponse->assertRedirect(route('login'));

        $downloadResponse = $this->get(route('constitution.download'));
        $downloadResponse->assertRedirect(route('login'));
    }

    public function test_member_can_view_empty_state_when_no_document_is_published(): void
    {
        $member = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($member)->get(route('constitution.index'));
        $response->assertStatus(200);
        $response->assertSee('Constitutional and By-Laws');
        $response->assertSee('No Document Currently Published');
    }

    public function test_member_can_view_page_preview_and_download_published_document(): void
    {
        $file = $this->createDummyPdf();
        $storedPath = $file->storeAs('documents/cbl', 'test-cbl-v1.pdf', 'local');

        $doc = OrganizationDocument::create([
            'code' => OrganizationDocument::CODE_CBL,
            'title' => 'Computing Society Constitution and By-Laws',
            'description' => 'The official governing charter.',
            'file_path' => $storedPath,
            'original_filename' => 'official_cbl.pdf',
            'file_size' => $file->getSize(),
            'mime_type' => 'application/pdf',
            'file_hash' => hash('sha256', $file->getContent()),
            'version' => 1,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $member = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        // 1. Visit index page
        $indexResponse = $this->actingAs($member)->get(route('constitution.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Computing Society Constitution and By-Laws');
        $indexResponse->assertSee('COMSOC Constitutional and By-Laws');
        $indexResponse->assertSee(route('constitution.download'));

        // 2. Stream preview
        $previewResponse = $this->actingAs($member)->get(route('constitution.preview'));
        $previewResponse->assertStatus(200);
        $this->assertEquals('application/pdf', $previewResponse->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', $previewResponse->headers->get('Content-Disposition'));

        // 3. Download original file
        $downloadResponse = $this->actingAs($member)->get(route('constitution.download'));
        $downloadResponse->assertStatus(200);
        $this->assertEquals('application/pdf', $downloadResponse->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $downloadResponse->headers->get('Content-Disposition'));
    }

    public function test_unpublished_document_cannot_be_previewed_or_downloaded_by_regular_members(): void
    {
        $file = $this->createDummyPdf();
        $storedPath = $file->storeAs('documents/cbl', 'test-cbl-v1.pdf', 'local');

        OrganizationDocument::create([
            'code' => OrganizationDocument::CODE_CBL,
            'title' => 'Draft Constitution',
            'file_path' => $storedPath,
            'original_filename' => 'draft.pdf',
            'file_size' => $file->getSize(),
            'mime_type' => 'application/pdf',
            'version' => 1,
            'is_published' => false, // Unpublished
        ]);

        $member = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        // Index shows unpublished empty state
        $indexResponse = $this->actingAs($member)->get(route('constitution.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('No Document Currently Published');

        // Preview returns 403 Forbidden
        $previewResponse = $this->actingAs($member)->get(route('constitution.preview'));
        $previewResponse->assertStatus(403);

        // Download returns 403 Forbidden
        $downloadResponse = $this->actingAs($member)->get(route('constitution.download'));
        $downloadResponse->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_access_admin_management_or_upload(): void
    {
        $member = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $file = $this->createDummyPdf();

        // GET admin constitution index
        $indexResponse = $this->actingAs($member)->get(route('admin.constitution.index'));
        $indexResponse->assertStatus(403);

        // POST upload
        $uploadResponse = $this->actingAs($member)->post(route('admin.constitution.upload'), [
            'title' => 'Hacked Constitution',
            'document' => $file,
        ]);
        $uploadResponse->assertStatus(403);
    }

    public function test_authorized_administrator_can_upload_replace_and_manage_document(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        // 1. Visit admin management page
        $pageResponse = $this->actingAs($admin)->get(route('admin.constitution.index'));
        $pageResponse->assertStatus(200);
        $pageResponse->assertSee('Constitutional and By-Laws Management');

        // 2. Upload initial document (v1)
        $file1 = $this->createDummyPdf();
        $uploadResponse = $this->actingAs($admin)->post(route('admin.constitution.upload'), [
            'title' => 'Computing Society Constitution and By-Laws 2026',
            'description' => 'Initial approved charter.',
            'document' => $file1,
            'is_published' => '1',
        ]);

        $uploadResponse->assertRedirect(route('admin.constitution.index'));
        $doc = OrganizationDocument::where('code', OrganizationDocument::CODE_CBL)->first();
        $this->assertNotNull($doc);
        $this->assertEquals(1, $doc->version);
        $this->assertTrue($doc->is_published);
        $this->assertEquals('Computing Society Constitution and By-Laws 2026', $doc->title);
        $this->assertTrue(Storage::disk('local')->exists($doc->file_path));

        // 3. Replace with newer version (v2)
        $file2 = $this->createDummyPdf();
        $replaceResponse = $this->actingAs($admin)->post(route('admin.constitution.upload'), [
            'title' => 'Computing Society Constitution and By-Laws (Amended)',
            'description' => 'Ratified by General Assembly.',
            'document' => $file2,
            'is_published' => '1',
        ]);

        $replaceResponse->assertRedirect(route('admin.constitution.index'));
        $doc->refresh();
        $this->assertEquals(2, $doc->version);
        $this->assertEquals('Computing Society Constitution and By-Laws (Amended)', $doc->title);

        // 4. Toggle unpublish / publish
        $toggleResponse = $this->actingAs($admin)->post(route('admin.constitution.toggle-publish'));
        $toggleResponse->assertRedirect(route('admin.constitution.index'));
        $doc->refresh();
        $this->assertFalse($doc->is_published);

        // 5. Update metadata
        $metaResponse = $this->actingAs($admin)->patch(route('admin.constitution.update-metadata'), [
            'title' => 'Official Constitution & By-Laws of SSU-BC ComSoc',
            'description' => 'Updated official description.',
        ]);
        $metaResponse->assertRedirect(route('admin.constitution.index'));
        $doc->refresh();
        $this->assertEquals('Official Constitution & By-Laws of SSU-BC ComSoc', $doc->title);
    }
}
