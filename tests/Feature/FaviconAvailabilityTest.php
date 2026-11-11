<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaviconAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_favicon_files_exist_and_are_non_empty_in_public_directory(): void
    {
        $expectedFiles = [
            'favicon.ico',
            'favicon.png',
            'favicon-16x16.png',
            'favicon-32x32.png',
            'favicon-48x48.png',
            'apple-touch-icon.png',
        ];

        foreach ($expectedFiles as $filename) {
            $path = public_path($filename);
            $this->assertFileExists($path, "Favicon file {$filename} does not exist in public directory.");
            $this->assertGreaterThan(0, filesize($path), "Favicon file {$filename} is empty.");
        }
    }

    public function test_login_page_renders_favicon_declarations(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('<link rel="icon" type="image/x-icon" href="', false);
        $response->assertSee('favicon.ico', false);
        $response->assertSee('favicon-32x32.png', false);
        $response->assertSee('apple-touch-icon.png', false);
    }

    public function test_dashboard_renders_favicon_declarations(): void
    {
        $user = User::factory()->create(['role' => 'student', 'is_active' => true]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('<link rel="icon" type="image/x-icon" href="', false);
        $response->assertSee('favicon.ico', false);
        $response->assertSee('favicon-32x32.png', false);
        $response->assertSee('apple-touch-icon.png', false);
    }
}

