<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountAssistanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_assistance_screen_can_be_rendered(): void
    {
        $response = $this->get('/account-assistance');

        $response->assertStatus(200);
        $response->assertSee('Need Help Accessing Your Account?');
        $response->assertSee('Contact COMSOC');
        $response->assertSee('https://www.facebook.com/comsocsorsubc');
        $response->assertSee('Contact President');
        $response->assertSee('https://www.facebook.com/kxze1029');
        $response->assertSee('Send Us an Email');
        $response->assertSee('computingsocietysorsubc@gmail.com');
        $response->assertSee('mailto:computingsocietysorsubc@gmail.com', false);
    }

    public function test_login_screen_links_to_account_assistance_and_forgot_password(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee(route('account.assistance'));
        $response->assertSee('Having trouble accessing your account?');
        $response->assertSee('Get Help');
        $response->assertSee(route('password.request'));
        $response->assertSee('Forgot Password?');
    }

    public function test_account_assistance_page_is_accessible(): void
    {
        $this->get('/account-assistance')
            ->assertStatus(200)
            ->assertSee('Need Help Accessing Your Account?');
    }
}
