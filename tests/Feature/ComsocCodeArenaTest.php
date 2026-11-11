<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ArenaChallenge;
use App\Models\ArenaSubmission;
use App\Models\ArenaTestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComsocCodeArenaTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_can_view_challenges_and_submit_solutions(): void
    {
        AcademicYear::create([
            'label' => 'AY 2025–2026',
            'year_start' => 2025,
            'year_end' => 2026,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['role' => 'student']);

        $challenge = ArenaChallenge::create([
            'title' => 'Palindrome Test',
            'slug' => 'palindrome-test',
            'difficulty' => 'novice',
            'category' => 'strings',
            'description_markdown' => 'Return true if palindrome.',
            'exp_reward' => 50,
            'is_active' => true,
        ]);

        ArenaTestCase::create([
            'arena_challenge_id' => $challenge->id,
            'input_data' => '"racecar"',
            'expected_output' => 'true',
            'is_hidden' => false,
        ]);

        $headers = ['X-Bypass-Coming-Soon' => 'true'];

        // 1. Fetch challenge listing
        $response = $this->actingAs($user)->getJson(route('arena.api.index'), $headers);
        $response->assertStatus(200);
        $response->assertJsonFragment(['title' => 'Palindrome Test']);
        $this->assertEquals(0, $response->json('user_stats.spendable_exp'));

        // 2. First-time solve awards 50 EXP
        $submit = $this->actingAs($user)->postJson(route('arena.api.submit', $challenge), [
            'language' => 'javascript',
            'source_code' => 'function isPalindrome(s) { return true; }',
        ], $headers);

        $submit->assertStatus(200);
        $submit->assertJson([
            'success' => true,
            'verdict' => 'accepted',
            'exp_earned' => 50,
            'already_claimed' => false,
        ]);

        $user->refresh();
        $this->assertEquals(50, $user->getSpendableExp());

        // 3. Repeated solve does NOT award duplicate EXP (Idempotency)
        $repeatSubmit = $this->actingAs($user)->postJson(route('arena.api.submit', $challenge), [
            'language' => 'javascript',
            'source_code' => 'function isPalindrome(s) { return true; }',
        ], $headers);

        $repeatSubmit->assertStatus(200);
        $repeatSubmit->assertJson([
            'success' => true,
            'verdict' => 'accepted',
            'exp_earned' => 0,
            'already_claimed' => true,
        ]);

        $user->refresh();
        $this->assertEquals(50, $user->getSpendableExp()); // Still 50, no duplicate
    }
}
