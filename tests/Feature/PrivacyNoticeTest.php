<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacyNoticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_page_is_publicly_accessible(): void
    {
        $response = $this->get('/privacy');

        $response->assertStatus(200);
        $response->assertSee('Privacy Notice');
        $response->assertSee('Republic Act No. 10173');
        $response->assertSee('Data Privacy Act of 2012');
        $response->assertSee('Sorsogon State University');
    }

    public function test_privacy_notice_alias_redirects_to_privacy(): void
    {
        $response = $this->get('/privacy-notice');

        $response->assertRedirect('/privacy');
    }

    public function test_privacy_page_contains_all_statutory_sections(): void
    {
        $response = $this->get('/privacy');

        $response->assertStatus(200);
        $response->assertSee('Personal Information Collected');
        $response->assertSee('Legal Basis');
        $response->assertSee('Data Retention');
        $response->assertSee('Data Protection Officer');
    }

    public function test_account_assistance_page_contains_privacy_link(): void
    {
        $response = $this->get('/account-assistance');

        $response->assertStatus(200);
        $response->assertSee(route('privacy'));
    }

    public function test_login_page_contains_privacy_link(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee(route('privacy'));
    }

    public function test_signup_page_contains_privacy_link(): void
    {
        $response = $this->get('/signup');

        $response->assertStatus(200);
        $response->assertSee(route('privacy'));
    }
}
