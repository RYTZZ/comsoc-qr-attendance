<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use App\Services\AvatarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdventurerAvatarFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_avatar_is_provided_for_user_without_explicit_selection(): void
    {
        $user = User::factory()->create([
            'avatar_style' => 'adventurer',
            'avatar_seed' => null,
        ]);

        $this->assertEquals('Felix', $user->effective_avatar_seed);
        $this->assertEquals('https://api.dicebear.com/9.x/adventurer/svg?seed=Felix', $user->avatar_url);
    }

    public function test_user_can_view_avatar_gallery_on_profile_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertSee('Adventurer Character Avatar');
        $response->assertSee('Felix');
        $response->assertSee('Aneka');
        $response->assertSee('Callie');
    }

    public function test_user_can_select_and_persist_adventurer_avatar(): void
    {
        $user = User::factory()->create([
            'avatar_seed' => 'Felix',
        ]);

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Adventurer Hero',
            'email' => $user->email,
            'avatar_style' => 'adventurer',
            'avatar_seed' => 'Aria',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/profile');

        $user->refresh();
        $this->assertEquals('Aria', $user->avatar_seed);
        $this->assertEquals('adventurer', $user->avatar_style);
        $this->assertEquals('https://api.dicebear.com/9.x/adventurer/svg?seed=Aria', $user->avatar_url);
    }

    public function test_avatar_persists_across_sessions_and_refreshes(): void
    {
        $user = User::factory()->create([
            'avatar_style' => 'adventurer',
            'avatar_seed' => 'Milo',
        ]);

        $this->actingAs($user);
        $response1 = $this->get('/profile');
        $response1->assertOk();
        $response1->assertSee('https://api.dicebear.com/9.x/adventurer/svg?seed=Milo');

        // Simulate re-login
        auth()->logout();
        $this->actingAs($user->fresh());
        $response2 = $this->get('/profile');
        $response2->assertOk();
        $response2->assertSee('https://api.dicebear.com/9.x/adventurer/svg?seed=Milo');
    }

    public function test_student_registration_allows_selecting_adventurer_avatar(): void
    {
        $payload = [
            'student_id' => '2026-9999',
            'first_name' => 'Geralt',
            'last_name' => 'Rivia',
            'middle_name' => '',
            'email' => 'geralt.rivia@sorsu.edu.ph',
            'program' => 'Bachelor of Science in Information Technology',
            'year' => '1st',
            'block' => '1',
            'username' => 'geralt_rivia',
            'avatar_seed' => 'Jasper',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
            'signature' => (function () {
                $im = imagecreatetruecolor(200, 80);
                imagealphablending($im, false);
                imagesavealpha($im, true);
                imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 127));
                imagesetthickness($im, 3);
                imageline($im, 10, 10, 180, 70, imagecolorallocate($im, 0, 0, 0));
                ob_start();
                imagepng($im);
                $png = ob_get_clean();
                return 'data:image/png;base64,' . base64_encode($png);
            })(),
            'signature_consent' => '1',
        ];

        $postResponse = $this->post('/signup', $payload);
        $postResponse->assertSessionHasNoErrors();
        $postResponse->assertRedirect('/dashboard');

        $user = User::where('email', 'geralt.rivia@sorsu.edu.ph')->first();
        $this->assertNotNull($user);
        $this->assertEquals('geralt_rivia', $user->username);
        $this->assertEquals('Jasper', $user->avatar_seed);
        $this->assertEquals('adventurer', $user->avatar_style);
        $this->assertEquals('https://api.dicebear.com/9.x/adventurer/svg?seed=Jasper', $user->avatar_url);
        $this->assertNotNull($user->adventurer_setup_at);
    }

    public function test_different_users_can_have_independent_avatars(): void
    {
        $user1 = User::factory()->create(['avatar_seed' => 'Leo']);
        $user2 = User::factory()->create(['avatar_seed' => 'Luna']);

        $this->assertEquals('Leo', $user1->avatar_seed);
        $this->assertEquals('Luna', $user2->avatar_seed);

        $this->actingAs($user1)->patch('/profile', [
            'name' => $user1->name,
            'email' => $user1->email,
            'avatar_style' => 'adventurer',
            'avatar_seed' => 'Orion',
        ]);

        $this->assertEquals('Orion', $user1->fresh()->avatar_seed);
        $this->assertEquals('Luna', $user2->fresh()->avatar_seed);
    }

    public function test_user_cannot_update_another_users_avatar(): void
    {
        $user1 = User::factory()->create(['avatar_seed' => 'Leo']);
        $user2 = User::factory()->create(['avatar_seed' => 'Luna']);

        // User1 is logged in, attempt to send patch - request only affects logged in user
        $this->actingAs($user1)->patch('/profile', [
            'name' => $user1->name,
            'email' => $user1->email,
            'avatar_style' => 'adventurer',
            'avatar_seed' => 'Zoe',
        ]);

        $this->assertEquals('Zoe', $user1->fresh()->avatar_seed);
        $this->assertEquals('Luna', $user2->fresh()->avatar_seed);
    }

    public function test_invalid_avatar_style_fails_validation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'avatar_style' => 'bottts-invalid',
            'avatar_seed' => 'Felix',
        ]);

        $response->assertSessionHasErrors(['avatar_style']);
    }

    public function test_avatar_service_generates_official_adventurer_endpoint(): void
    {
        $this->assertEquals('adventurer', AvatarService::STYLE);
        $url = AvatarService::getUrl('Felix');
        $this->assertStringStartsWith('https://api.dicebear.com/9.x/adventurer/svg?seed=', $url);
        $this->assertStringNotContainsString('adventurer-neutral', $url);
        $this->assertStringNotContainsString('avataaars', $url);
        $this->assertStringNotContainsString('bottts', $url);
    }

    public function test_avatar_service_sanitizes_malicious_or_empty_seeds(): void
    {
        $this->assertEquals(AvatarService::DEFAULT_SEED, AvatarService::sanitizeSeed(''));
        $this->assertEquals(AvatarService::DEFAULT_SEED, AvatarService::sanitizeSeed('   '));
        $this->assertEquals(AvatarService::DEFAULT_SEED, AvatarService::sanitizeSeed('<script>alert("xss")</script>'));
        $this->assertEquals('Geralt_99', AvatarService::sanitizeSeed('Geralt_99'));
    }

    public function test_avatar_presets_collection_contains_diverse_adventurer_characters(): void
    {
        $presets = AvatarService::getPresets();
        $this->assertCount(16, $presets);

        foreach ($presets as $preset) {
            $this->assertArrayHasKey('name', $preset);
            $this->assertArrayHasKey('seed', $preset);
            $this->assertArrayHasKey('url', $preset);
            $this->assertStringStartsWith('https://api.dicebear.com/9.x/adventurer/svg?seed=', $preset['url']);
        }
    }
}
