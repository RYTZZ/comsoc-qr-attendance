<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Event;
use App\Models\OrgAchievement;
use App\Models\OrgFaq;
use App\Models\OrgGalleryItem;
use App\Models\OrgOfficer;
use App\Models\OrgPartner;
use App\Models\User;
use App\Services\WebsiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_homepage_is_accessible_without_authentication_and_shows_defaults(): void
    {
        $response = $this->get(route('public.home'));

        $response->assertOk();
        $response->assertSee('Computing Society');
        $response->assertSee('Building Skills. Creating Opportunities. Connecting Communities.');
        $response->assertSee('Access COMSOC Hub');
        $response->assertSee('Discover COMSOC');
        $response->assertSee('Our Five Organizational Pillars');
    }

    public function test_all_public_navigation_routes_resolve_correctly_without_authentication(): void
    {
        $publicRoutes = [
            route('public.home'),
            route('public.about'),
            route('public.officers'),
            route('public.activities'),
            route('public.achievements'),
            route('public.gallery'),
            route('public.partners'),
            route('privacy'),
        ];

        foreach ($publicRoutes as $url) {
            $response = $this->get($url);
            $response->assertOk();
            // Verify standardized footer across every public page (false allows unescaped ampersand matching)
            $response->assertSee('Computing Society (COMSOC) — SorSU Bulan Campus. All rights reserved.');
            $response->assertSee('Contact & Channels', false);
            $response->assertSee('computingsocietysorsubc@gmail.com');
            $response->assertSee('Official Facebook Page');
        }

        $aboutRes = $this->get(route('public.about'));
        $aboutRes->assertSee('Organization Background');
        $aboutRes->assertSee('A Message From The President');
        $aboutRes->assertSee('A Computing Society That Does More');
        $this->get(route('public.officers'))->assertSee('Organization Leadership');
        $this->get(route('public.activities'))->assertSee('COMSOC Activities');
        $this->get(route('public.achievements'))->assertSee('Verified Achievements');
        $this->get(route('public.gallery'))->assertSee('Approved Organization Gallery');
        $this->get(route('public.partners'))->assertSee('Partners');
    }

    public function test_president_message_renders_and_can_be_updated_by_admin(): void
    {
        // 1. Check default President's Message on About page
        $this->get(route('public.about'))
            ->assertOk()
            ->assertSee('A Message From The President')
            ->assertSee('A Computing Society That Does More')
            ->assertSee('John Mark M. Esperida');

        // 2. Super admin updates President's Message via Profile management
        $updatePayload = [
            'org_intro' => 'Updated introduction for testing.',
            'org_background' => 'Updated background narrative for testing.',
            'org_mission' => 'Updated mission statement.',
            'org_vision' => 'Updated vision statement.',
            'pillars' => WebsiteSettingsService::DEFAULTS['pillars'],
            'president_message_visible' => '1',
            'president_message_eyebrow' => 'Official Leadership Address',
            'president_message_headline' => 'Building Tomorrow’s Technologists Today',
            'president_message_quote' => 'Every line of code and every project brings us closer to a thriving community.',
            'president_message_body' => "First paragraph of leadership statement.\n\nSecond paragraph of leadership statement.",
            'president_name' => 'John Mark M. Esperida',
            'president_title' => 'President, Computing Society',
            'president_term' => 'Academic Year 2025–2026',
        ];

        $postRes = $this->actingAs($this->superAdmin)->post(route('admin.website.profile.update'), $updatePayload);
        $postRes->assertRedirect();
        $postRes->assertSessionHas('success');

        // 3. Verify public About page reflects updated message immediately
        $publicAbout = $this->get(route('public.about'));
        $publicAbout->assertOk();
        $publicAbout->assertSee('Official Leadership Address');
        $publicAbout->assertSee('Building Tomorrow’s Technologists Today');
        $publicAbout->assertSee('Every line of code and every project brings us closer to a thriving community.');
        $publicAbout->assertSee('First paragraph of leadership statement.');
    }

    public function test_public_pages_render_controlled_empty_states_when_database_is_empty(): void
    {
        $this->get(route('public.officers'))->assertOk()->assertSee('Leadership directory will be published shortly');
        $this->get(route('public.activities'))->assertOk()->assertSee('No activities found');
        $this->get(route('public.achievements'))->assertOk()->assertSee('Verified achievements and milestones will be updated');
        $this->get(route('public.gallery'))->assertOk()->assertSee('Approved photographs will be uploaded here shortly');
        $this->get(route('public.partners'))->assertOk()->assertSee('Confirmed partner collaborations will be published upon approval');
    }

    public function test_only_published_officers_are_visible_publicly(): void
    {
        $published = OrgOfficer::create([
            'name' => 'Maria Clara',
            'position' => 'President',
            'category' => 'executive',
            'is_current' => true,
            'is_published' => true,
        ]);

        $draft = OrgOfficer::create([
            'name' => 'Secret Officer',
            'position' => 'Undercover Secretary',
            'category' => 'executive',
            'is_current' => true,
            'is_published' => false,
        ]);

        $past = OrgOfficer::create([
            'name' => 'Former Leader',
            'position' => 'Past President',
            'category' => 'executive',
            'is_current' => false,
            'is_published' => true,
        ]);

        $response = $this->get(route('public.officers'));
        $response->assertOk();
        $response->assertSee('Maria Clara');
        $response->assertDontSee('Secret Officer');
        $response->assertDontSee('Former Leader');
    }

    public function test_activities_showcase_only_published_and_public_events(): void
    {
        $publicEvent = Event::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'From Idea to App: Building a Real-World Product',
            'public_slug' => 'from-idea-to-app-2026',
            'event_date' => now()->addDays(5)->toDateString(),
            'starts_at' => now()->addDays(5)->setTime(9, 0),
            'status' => 'published',
            'is_published' => true,
            'is_public' => true,
            'location' => 'Main Campus Hall',
            'created_by' => $this->superAdmin->id,
        ]);

        $privateEvent = Event::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Internal Officer Strategic Planning',
            'public_slug' => 'internal-officer-planning',
            'event_date' => now()->addDays(2)->toDateString(),
            'status' => 'published',
            'is_published' => true,
            'is_public' => false,
            'created_by' => $this->superAdmin->id,
        ]);

        $draftEvent = Event::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Unapproved Draft Hackathon',
            'public_slug' => 'unapproved-draft-hackathon',
            'event_date' => now()->addDays(10)->toDateString(),
            'status' => 'draft',
            'is_published' => false,
            'is_public' => true,
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->get(route('public.activities'));
        $response->assertOk();
        $response->assertSee('From Idea to App: Building a Real-World Product');
        $response->assertDontSee('Internal Officer Strategic Planning');
        $response->assertDontSee('Unapproved Draft Hackathon');

        // Individual activity detail resolves public event
        $detailRes = $this->get(route('public.activities.detail', 'from-idea-to-app-2026'));
        $detailRes->assertOk();
        $detailRes->assertSee('From Idea to App: Building a Real-World Product');

        // Private event detail returns 404
        $this->get(route('public.activities.detail', 'internal-officer-planning'))->assertNotFound();
    }

    public function test_only_published_achievements_gallery_and_partners_are_exposed(): void
    {
        OrgAchievement::create([
            'title' => 'National Hackathon Champion 2026',
            'description' => 'Developed an AI emergency dispatch solution.',
            'is_published' => true,
        ]);
        OrgAchievement::create([
            'title' => 'Unconfirmed Award Draft',
            'description' => 'Secret award not yet officially announced.',
            'is_published' => false,
        ]);

        $achRes = $this->get(route('public.achievements'));
        $achRes->assertOk();
        $achRes->assertSee('National Hackathon Champion 2026');
        $achRes->assertDontSee('Unconfirmed Award Draft');

        OrgPartner::create([
            'name' => 'Confirmed Tech Partner',
            'collaboration_type' => 'Industry',
            'is_published' => true,
        ]);
        OrgPartner::create([
            'name' => 'Unconfirmed Prospective Sponsor',
            'collaboration_type' => 'Industry',
            'is_published' => false,
        ]);

        $partnerRes = $this->get(route('public.partners'));
        $partnerRes->assertOk();
        $partnerRes->assertSee('Confirmed Tech Partner');
        $partnerRes->assertDontSee('Unconfirmed Prospective Sponsor');
    }

    public function test_super_admin_can_access_and_update_website_management(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.website.settings'));
        $response->assertOk();
        $response->assertSee('Public Website Management');

        $updatePayload = [
            'org_name' => 'Computing Society (COMSOC)',
            'org_short_name' => 'COMSOC',
            'org_institution' => 'Sorsogon State University – Bulan Campus',
            'org_tagline' => 'Innovating Today for a Better Community.',
            'hero_headline' => 'Transforming SorSU Technologists Today.',
            'hero_description' => 'Official student organization uniting computing disciplines.',
            'hero_cta_primary_label' => 'Explore Mission',
            'hero_cta_primary_url' => '/about',
            'hero_cta_secondary_label' => 'Open Hub',
            'hero_cta_secondary_url' => '/login',
            'hub_headline' => 'Your Student Platform.',
            'hub_description' => 'EXP, Attendance, and Digital Signatures.',
            'final_cta_headline' => 'Join the Community.',
            'final_cta_description' => 'Start your computing journey with us.',
            'meta_title' => 'COMSOC — Bulan Campus',
            'meta_description' => 'Official COMSOC Website',
            'section_hero_visible' => '1',
            'section_about_visible' => '1',
            'section_pillars_visible' => '1',
            'section_officers_visible' => '1',
            'section_activities_visible' => '1',
            'section_achievements_visible' => '1',
            'section_gallery_visible' => '1',
            'section_partners_visible' => '1',
            'section_hub_cta_visible' => '1',
            'section_faq_visible' => '1',
        ];

        $postRes = $this->actingAs($this->superAdmin)->post(route('admin.website.settings.update'), $updatePayload);
        $postRes->assertRedirect();
        $postRes->assertSessionHas('success');

        // Verify public site reflects updated headline immediately without code change
        $publicRes = $this->get(route('public.home'));
        $publicRes->assertOk();
        $publicRes->assertSee('Transforming SorSU Technologists Today.');
    }

    public function test_super_admin_can_toggle_event_public_visibility(): void
    {
        $event = Event::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Campus Tech Summit 2026',
            'event_date' => now()->addDays(7)->toDateString(),
            'status' => 'published',
            'is_published' => true,
            'is_public' => false,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->actingAs($this->superAdmin)
            ->post(route('admin.website.activities.toggle', $event), [
                'is_public' => 1,
                'public_slug' => 'campus-tech-summit-2026',
            ])
            ->assertRedirect();

        $this->assertTrue($event->fresh()->is_public);
        $this->assertEquals('campus-tech-summit-2026', $event->fresh()->public_slug);

        // Verify it is now visible on public activities page
        $this->get(route('public.activities'))->assertSee('Campus Tech Summit 2026');
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
