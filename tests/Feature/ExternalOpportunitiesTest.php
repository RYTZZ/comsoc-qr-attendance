<?php

namespace Tests\Feature;

use App\Models\JobBookmark;
use App\Models\JobListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalOpportunitiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_view_active_opportunities_directory(): void
    {
        $member = User::factory()->create([
            'role' => 'student',
        ]);

        $activeJob = JobListing::create([
            'title' => 'Backend Laravel Intern',
            'company' => 'Acme Cloud PH',
            'job_type' => 'internship',
            'workplace_type' => 'remote',
            'location' => 'Makati / Remote',
            'description' => 'Build high-traffic REST APIs and microservices.',
            'requirements' => 'PHP 8.2, Laravel, MySQL',
            'compensation' => 'PHP 15,000 / month allowance',
            'application_url' => 'https://careers.acme.ph/interns',
            'is_active' => true,
            'is_verified' => true,
        ]);

        $inactiveJob = JobListing::create([
            'title' => 'Archived Legacy Developer',
            'company' => 'Old Tech Corp',
            'job_type' => 'full_time',
            'workplace_type' => 'onsite',
            'description' => 'Archived role.',
            'application_url' => 'https://careers.oldtech.ph',
            'is_active' => false,
        ]);

        $response = $this->actingAs($member)->get(route('opportunities.index'));

        $response->assertOk();
        $response->assertSee('Backend Laravel Intern');
        $response->assertSee('Acme Cloud PH');
        $response->assertDontSee('Archived Legacy Developer');
    }

    public function test_member_can_filter_by_job_type_and_workplace(): void
    {
        $member = User::factory()->create([
            'role' => 'student',
        ]);

        JobListing::create([
            'title' => 'Frontend React Engineer',
            'company' => 'Veloce Labs',
            'job_type' => 'Full-Time',
            'is_remote' => true,
            'description' => 'Next.js frontend work.',
            'application_url' => 'https://veloce.ph/apply',
            'is_active' => true,
        ]);

        JobListing::create([
            'title' => 'Cybersecurity Analyst',
            'company' => 'SecureCorp',
            'job_type' => 'Internship',
            'is_remote' => false,
            'description' => 'Onsite SOC operations.',
            'application_url' => 'https://securecorp.ph/apply',
            'is_active' => true,
        ]);

        $filteredResponse = $this->actingAs($member)->get(route('opportunities.index', [
            'job_type' => 'Full-Time',
            'workplace' => 'remote',
        ]));

        $filteredResponse->assertOk();
        $filteredResponse->assertSee('Frontend React Engineer');
        $filteredResponse->assertDontSee('Cybersecurity Analyst');
    }

    public function test_member_can_toggle_and_view_saved_bookmarks(): void
    {
        $member = User::factory()->create([
            'role' => 'student',
        ]);

        $job = JobListing::create([
            'title' => 'Cloud Infrastructure Fellow',
            'company' => 'ScaleOps',
            'job_type' => 'Fellowship',
            'is_remote' => true,
            'description' => 'Kubernetes and AWS fellowship.',
            'application_url' => 'https://scaleops.ph/fellows',
            'is_active' => true,
        ]);

        $toggleResponse = $this->actingAs($member)->post(route('opportunities.bookmark', $job));
        $toggleResponse->assertRedirect();

        $this->assertDatabaseHas('job_bookmarks', [
            'user_id' => $member->id,
            'job_listing_id' => $job->id,
        ]);

        $bookmarkedPage = $this->actingAs($member)->get(route('opportunities.index', ['tab' => 'bookmarked']));
        $bookmarkedPage->assertOk();
        $bookmarkedPage->assertSee('Cloud Infrastructure Fellow');

        $untoggleResponse = $this->actingAs($member)->post(route('opportunities.bookmark', $job));
        $untoggleResponse->assertRedirect();

        $this->assertDatabaseMissing('job_bookmarks', [
            'user_id' => $member->id,
            'job_listing_id' => $job->id,
        ]);
    }

    public function test_member_can_view_job_detail_page_with_external_application_warning(): void
    {
        $member = User::factory()->create([
            'role' => 'student',
        ]);

        $job = JobListing::create([
            'title' => 'AI Prompt Engineer',
            'company' => 'Nexus Intelligent Systems',
            'job_type' => 'Part-Time',
            'is_remote' => false,
            'location' => 'BGC, Taguig / 2 days onsite',
            'description' => 'Fine-tuning LLM pipelines.',
            'required_skills' => ['Python', 'PyTorch', 'LangChain'],
            'compensation' => 'PHP 30,000 / month',
            'application_url' => 'https://careers.nexus.ph/jobs/prompt-eng',
            'source_url' => 'https://linkedin.com/jobs/view/123456789',
            'is_active' => true,
            'is_verified' => true,
        ]);

        $response = $this->actingAs($member)->get(route('opportunities.show', $job));

        $response->assertOk();
        $response->assertSee('AI Prompt Engineer');
        $response->assertSee('Nexus Intelligent Systems');
        $response->assertSee('https://careers.nexus.ph/jobs/prompt-eng');
        $response->assertSee('rel="noopener noreferrer"', false);
        $response->assertSee('target="_blank"', false);
    }

    public function test_officer_can_create_edit_and_toggle_active_opportunities(): void
    {
        $officer = User::factory()->create([
            'role' => 'admin',
        ]);

        $postResponse = $this->actingAs($officer)->post(route('admin.opportunities.store'), [
            'title' => 'Mobile App Intern (Flutter)',
            'company' => 'AppCraft Studios',
            'job_type' => 'Internship',
            'is_remote' => '1',
            'location' => 'Remote (PH)',
            'description' => 'Build high-performance Flutter apps.',
            'required_skills' => 'Flutter, Dart, Bloc architecture',
            'compensation' => 'PHP 12,000 allowance',
            'application_url' => 'https://appcraft.ph/careers',
            'source_url' => 'https://facebook.com/appcraft/posts/1',
            'is_verified' => '1',
            'is_active' => '1',
        ]);

        $postResponse->assertRedirect(route('admin.opportunities.index'));

        $this->assertDatabaseHas('job_listings', [
            'title' => 'Mobile App Intern (Flutter)',
            'company' => 'AppCraft Studios',
            'is_verified' => true,
            'is_active' => true,
        ]);

        $job = JobListing::where('title', 'Mobile App Intern (Flutter)')->first();

        $updateResponse = $this->actingAs($officer)->put(route('admin.opportunities.update', $job), [
            'title' => 'Lead Mobile App Intern (Flutter & iOS)',
            'company' => 'AppCraft Studios Inc.',
            'job_type' => 'Internship',
            'is_remote' => '0',
            'location' => 'Cebu / Hybrid',
            'description' => 'Updated job description.',
            'required_skills' => 'Flutter, Swift',
            'compensation' => 'PHP 16,000 allowance',
            'application_url' => 'https://appcraft.ph/careers/lead',
            'is_verified' => '0',
            'is_active' => '1',
        ]);

        $updateResponse->assertRedirect(route('admin.opportunities.index'));

        $this->assertDatabaseHas('job_listings', [
            'id' => $job->id,
            'title' => 'Lead Mobile App Intern (Flutter & iOS)',
            'is_remote' => false,
            'is_verified' => false,
        ]);

        $toggleResponse = $this->actingAs($officer)->post(route('admin.opportunities.toggle-active', $job));
        $toggleResponse->assertRedirect();

        $this->assertDatabaseHas('job_listings', [
            'id' => $job->id,
            'is_active' => false,
        ]);
    }
}
