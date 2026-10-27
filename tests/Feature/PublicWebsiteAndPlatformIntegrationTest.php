<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicWebsiteAndPlatformIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $student;
    protected AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->academicYear = AcademicYear::create([
            'label' => 'A.Y. 2026-2027',
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

        $this->student = User::factory()->create([
            'name' => 'Jane Member',
            'email' => 'jane@student.local',
            'role' => 'student',
            'is_active' => true,
        ]);
    }

    public function test_unauthenticated_user_accessing_root_is_redirected_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_student_accessing_root_is_redirected_to_dashboard(): void
    {
        $response = $this->actingAs($this->student)->get('/');
        $response->assertRedirect(route('dashboard'));
    }

    public function test_authenticated_super_admin_accessing_root_is_redirected_to_dashboard(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/');
        $response->assertRedirect(route('dashboard'));
    }

    public function test_removed_public_website_pages_redirect_safely(): void
    {
        // Public marketing routes should redirect to root / (which then enforces auth)
        $routes = ['/about', '/officers', '/activities', '/achievements', '/gallery', '/partners'];

        foreach ($routes as $url) {
            $response = $this->get($url);
            $response->assertRedirect(route('home'));
        }
    }

    public function test_terms_and_privacy_routes_remain_accessible(): void
    {
        $this->get('/terms-of-use')->assertRedirect(route('privacy'));
        $this->get('/terms')->assertRedirect(route('privacy'));
        $this->get('/privacy')->assertOk()->assertSee('COMSOC HUB Privacy Notice');
    }

    public function test_super_admin_can_access_website_management_without_breaking(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.website.settings'));
        $response->assertOk();
        $response->assertSee('Public Website Management');
    }

    public function test_unauthorized_users_and_guests_cannot_access_website_management(): void
    {
        // Unauthenticated guest redirected to login
        $this->get(route('admin.website.settings'))->assertRedirect(route('login'));
        $this->post(route('admin.website.settings.update'), [])->assertRedirect(route('login'));

        // Regular student gets 403 Forbidden
        $this->actingAs($this->student)->get(route('admin.website.settings'))->assertForbidden();
        $this->actingAs($this->student)->get(route('admin.website.officers'))->assertForbidden();
        $this->actingAs($this->student)->post(route('admin.website.settings.update'), [])->assertForbidden();
    }
}
