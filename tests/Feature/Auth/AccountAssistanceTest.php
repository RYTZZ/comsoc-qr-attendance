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

    public function test_login_screen_links_to_account_assistance_and_omits_forgot_password(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee(route('account.assistance'));
        $response->assertSee('Having trouble accessing your account?');
        $response->assertSee('Get Help');
        $response->assertDontSee('Forgot password?');
    }

    public function test_legacy_forgot_password_and_reset_endpoints_redirect_to_account_assistance(): void
    {
        $this->get('/forgot-password')
            ->assertRedirect(route('account.assistance'));

        $this->post('/forgot-password', ['email' => 'user@example.com'])
            ->assertRedirect(route('account.assistance'));

        $this->get('/reset-password/sample-token')
            ->assertRedirect(route('account.assistance'));

        $this->post('/reset-password', [
            'token' => 'sample-token',
            'email' => 'user@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertRedirect(route('account.assistance'));
    }
}
