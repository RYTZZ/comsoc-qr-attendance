<?php

namespace Database\Seeders;

use App\Models\ArenaChallenge;
use App\Models\ArenaTestCase;
use Illuminate\Database\Seeder;

class CodeArenaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Two Sum
        $twoSum = ArenaChallenge::updateOrCreate(
            ['slug' => 'two-sum'],
            [
                'title' => 'Two Sum',
                'difficulty' => 'novice',
                'category' => 'arrays',
                'description_markdown' => "Given an array of integers `nums` and an integer `target`, return the indices of the two numbers such that they add up to `target`.\n\nInput format: JSON array and integer, e.g. `[2,7,11,15], 9`.\nOutput format: JSON array of two indices `[0,1]`.",
                'sample_input' => "[2,7,11,15], 9",
                'sample_output' => "[0,1]",
                'exp_reward' => 50,
                'is_active' => true,
            ]
        );

        ArenaTestCase::updateOrCreate(
            ['arena_challenge_id' => $twoSum->id, 'expected_output' => '[0,1]'],
            ['input_data' => '[2,7,11,15], 9', 'is_hidden' => false]
        );
        ArenaTestCase::updateOrCreate(
            ['arena_challenge_id' => $twoSum->id, 'expected_output' => '[1,2]'],
            ['input_data' => '[3,2,4], 6', 'is_hidden' => false]
        );
        ArenaTestCase::updateOrCreate(
            ['arena_challenge_id' => $twoSum->id, 'expected_output' => '[0,1]'],
            ['input_data' => '[3,3], 6', 'is_hidden' => true]
        );

        // 2. Valid Palindrome
        $palindrome = ArenaChallenge::updateOrCreate(
            ['slug' => 'valid-palindrome'],
            [
                'title' => 'Valid Palindrome',
                'difficulty' => 'novice',
                'category' => 'strings',
                'description_markdown' => "A phrase is a palindrome if, after converting uppercase to lowercase and removing non-alphanumeric characters, it reads the same forward and backward.\n\nReturn `true` if it is a palindrome, or `false` otherwise.",
                'sample_input' => '"A man, a plan, a canal: Panama"',
                'sample_output' => 'true',
                'exp_reward' => 40,
                'is_active' => true,
            ]
        );

        ArenaTestCase::updateOrCreate(
            ['arena_challenge_id' => $palindrome->id, 'input_data' => '"A man, a plan, a canal: Panama"'],
            ['expected_output' => 'true', 'is_hidden' => false]
        );
        ArenaTestCase::updateOrCreate(
            ['arena_challenge_id' => $palindrome->id, 'input_data' => '"race a car"'],
            ['expected_output' => 'false', 'is_hidden' => false]
        );

        // 3. Fibonacci Sequence
        $fib = ArenaChallenge::updateOrCreate(
            ['slug' => 'fibonacci-number'],
            [
                'title' => 'Fibonacci Number',
                'difficulty' => 'adept',
                'category' => 'recursion',
                'description_markdown' => "The Fibonacci numbers `F(n)` form a sequence such that each number is the sum of the two preceding ones, starting from 0 and 1.\n\nGiven `n`, calculate `F(n)`.",
                'sample_input' => '4',
                'sample_output' => '3',
                'exp_reward' => 75,
                'is_active' => true,
            ]
        );

        ArenaTestCase::updateOrCreate(
            ['arena_challenge_id' => $fib->id, 'input_data' => '2'],
            ['expected_output' => '1', 'is_hidden' => false]
        );
        ArenaTestCase::updateOrCreate(
            ['arena_challenge_id' => $fib->id, 'input_data' => '4'],
            ['expected_output' => '3', 'is_hidden' => false]
        );
        ArenaTestCase::updateOrCreate(
            ['arena_challenge_id' => $fib->id, 'input_data' => '10'],
            ['expected_output' => '55', 'is_hidden' => true]
        );
    }
}
