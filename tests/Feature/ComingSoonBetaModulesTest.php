<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComingSoonBetaModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_and_public_see_coming_soon_experience(): void
    {
        AcademicYear::create([
            'label' => 'AY 2025–2026',
            'year_start' => 2025,
            'year_end' => 2026,
            'is_active' => true,
        ]);

        $student = User::factory()->create(['role' => 'student']);

        // 1. /arena web view displays Arena Coming Soon
        $arenaRes = $this->actingAs($student)->get('/arena');
        $arenaRes->assertStatus(200);
        $arenaRes->assertSee('Ready, Player One?');
        $arenaRes->assertSee('LEVEL UP INCOMING');
        $arenaRes->assertSee('COMPILING YOUR NEXT CHALLENGE...');

        // 2. /pulse web view displays Pulse Coming Soon
        $pulseRes = $this->actingAs($student)->get('/pulse');
        $pulseRes->assertStatus(200);
        $pulseRes->assertSee("It's Quiet... Too Quiet.");
        $pulseRes->assertSee('SOMETHING IS BREWING');

        // 3. /critique web view displays Critique Coming Soon
        $critiqueRes = $this->actingAs($student)->get('/critique');
        $critiqueRes->assertStatus(200);
        $critiqueRes->assertSee('Your Code Deserves Better.');
        $critiqueRes->assertSee('DEBUGGING THE FUTURE');

        // 4. API endpoints return 403 status with clean coming_soon payload for non-superadmin
        $apiRes = $this->actingAs($student)->getJson('/arena/api/challenges');
        $apiRes->assertStatus(403);
        $apiRes->assertJson([
            'success' => false,
            'status' => 'coming_soon',
            'module' => 'arena',
        ]);
    }

    public function test_super_admin_can_bypass_coming_soon_for_maintenance(): void
    {
        AcademicYear::create([
            'label' => 'AY 2025–2026',
            'year_start' => 2025,
            'year_end' => 2026,
            'is_active' => true,
        ]);

        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        // Super Admin bypasses and gets actual JSON API response
        $apiRes = $this->actingAs($superAdmin)->getJson('/arena/api/challenges');
        $apiRes->assertStatus(200);
        $apiRes->assertJsonStructure(['challenges']);
    }
}
