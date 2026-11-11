<?php

namespace Tests\Feature;

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
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);

        $challenge = ArenaChallenge::create([
            'title' => 'Two Sum Indices',
            'slug' => 'two-sum-indices',
            'difficulty' => 'novice',
            'category' => 'arrays',
            'description_markdown' => 'Given an array of integers and target, return indices of two numbers such that they add up to target.',
            'sample_input' => '[2,7,11,15], target = 9',
            'sample_output' => '[0,1]',
            'exp_reward' => 50,
            'is_active' => true,
        ]);

        ArenaTestCase::create([
            'arena_challenge_id' => $challenge->id,
            'input_data' => '[2,7,11,15], target = 9',
            'expected_output' => '[0,1]',
            'is_hidden' => false,
        ]);

        // 1. Fetch challenge list
        $resList = $this->actingAs($student)
            ->getJson(route('arena.api.index'))
            ->assertOk()
            ->assertJsonStructure(['challenges']);

        $this->assertCount(1, $resList->json('challenges'));
        $this->assertSame('Two Sum Indices', $resList->json('challenges.0.title'));

        // 2. Submit solution
        $resSubmit = $this->actingAs($student)
            ->postJson(route('arena.api.submit', $challenge), [
                'language' => 'javascript',
                'source_code' => 'function twoSum(nums, target) { return [0, 1]; }',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'verdict' => 'accepted',
                'exp_earned' => 50,
            ]);

        $this->assertDatabaseHas('arena_submissions', [
            'arena_challenge_id' => $challenge->id,
            'user_id' => $student->id,
            'verdict' => 'accepted',
        ]);
    }
}
