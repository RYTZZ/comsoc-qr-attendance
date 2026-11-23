<?php

namespace Tests\Feature;

use App\Models\TriviaQuestion;
use App\Models\User;
use App\Services\FridayTriviaAutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TriviaBulkImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_parse_multiple_format_variations_and_detect_duplicates_and_invalid(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        TriviaQuestion::create([
            'question' => 'What does HTTP stand for in web networking?',
            'category' => 'networking',
            'difficulty' => 'easy',
            'options' => ['HyperText Transfer Protocol', 'High Text', 'Host Transfer', 'Hyper Tool'],
            'correct_index' => 0,
            'points' => 15,
            'status' => 'published',
        ]);

        $raw = <<<TXT
Q1. Which component is commonly referred to as the brain of the computer?
A. RAM
B. CPU
C. SSD
D. GPU
Correct Answer: B
Category: General Technology
Difficulty: Easy
Explanation: The CPU executes instructions and processes data.

Question 2: What does HTML stand for?
A) Hyper Trainer Marking Language
B) HyperText Markup Language
C) HighText Machine Language
D) HyperText Marketing Language
Answer: B
Category: Web Development
Difficulty: Easy

3. What does HTTP stand for in web networking?
A. HyperText Transfer Protocol
B. High Text
C. Host Transfer
D. Hyper Tool
Correct: A

Q4. Broken question missing options?
A. Only one
Correct Answer: B
TXT;

        $resp = $this->actingAs($admin)->postJson(route('admin.trivia.bulk-parse'), [
            'raw_text' => $raw,
        ]);

        $resp->assertOk();
        $resp->assertJsonPath('summary.detected', 4);
        $resp->assertJsonPath('summary.valid', 2);
        $resp->assertJsonPath('summary.duplicates', 1);
        $resp->assertJsonPath('summary.invalid', 1);
    }

    public function test_admin_can_bulk_import_valid_questions_into_automated_friday_bank(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $topics = [
            'Which data structure uses LIFO ordering for push and pop?',
            'What does CSS stand for in frontend web styling?',
            'Which TCP port is used by default for SSH connections?',
            'What is the binary representation of decimal number 10?',
            'Which SQL keyword is used to filter grouped aggregate results?',
        ];
        $items = [];
        foreach ($topics as $i => $qText) {
            $items[] = [
                'selected' => '1',
                'question' => $qText,
                'options' => ["Opt A {$i}", "Opt B {$i}", "Opt C {$i}", "Opt D {$i}"],
                'correct_index' => 1,
                'category' => 'computer_science',
                'difficulty' => 'medium',
                'explanation' => "Explanation {$i}",
            ];
        }

        $resp = $this->actingAs($admin)->post(route('admin.trivia.bulk-import'), [
            'items' => $items,
            'feedback_mode' => 'immediate',
            'points' => 15,
            'status' => 'published',
        ]);

        $resp->assertRedirect(route('admin.trivia.index'));
        $resp->assertSessionHas('success');
        $this->assertSame(5, TriviaQuestion::count());

        // Verify imported questions are immediately eligible for Automated Friday Trivia
        $sessionRes = app(FridayTriviaAutomationService::class)->activateWeeklySession(
            Carbon::create(2026, 11, 20, 0, 0, 0, 'Asia/Manila')
        );
        $this->assertSame('activated', $sessionRes['status']);
        $this->assertCount(5, $sessionRes['session']->sessionQuestions);
    }

    public function test_existing_single_question_creation_remains_functional(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $friday = Carbon::now('Asia/Manila')->next(Carbon::FRIDAY);

        $resp = $this->actingAs($admin)->post(route('admin.trivia.store'), [
            'question' => 'Which protocol secures HTTP traffic using TLS?',
            'category' => 'cybersecurity',
            'options' => ['FTP', 'HTTPS', 'Telnet', 'SMTP'],
            'correct_index' => 1,
            'explanation' => 'HTTPS wraps HTTP inside TLS.',
            'points' => 10,
            'feedback_mode' => 'immediate',
            'status' => 'published',
            'publish_at' => $friday->copy()->setTime(8, 0)->format('Y-m-d\TH:i'),
            'closes_at' => $friday->copy()->setTime(23, 59)->format('Y-m-d\TH:i'),
        ]);

        $resp->assertRedirect(route('admin.trivia.index'));
        $this->assertSame(1, TriviaQuestion::count());
    }
}
