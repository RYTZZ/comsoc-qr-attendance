<?php

namespace Tests\Feature;

use App\Models\TriviaQuestion;
use App\Models\TriviaSession;
use App\Models\TriviaSessionCompletion;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\FridayTriviaAutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AutomatedFridayTriviaTest extends TestCase
{
    use RefreshDatabase;

    private function seedQuestions(int $count = 10): void
    {
        for ($i = 1; $i <= $count; $i++) {
            TriviaQuestion::create([
                'question' => "Computing trivia question number {$i} for database bank?",
                'category' => 'computer_science',
                'difficulty' => 'medium',
                'options' => ["Opt A {$i}", "Opt B {$i}", "Opt C {$i}", "Opt D {$i}"],
                'correct_index' => 0,
                'explanation' => "Explanation for question {$i}.",
                'points' => 15,
                'is_active' => true,
                'status' => 'published',
            ]);
        }
    }

    public function test_weekly_activation_shuffles_and_prevents_duplicate_week_session(): void
    {
        $this->seedQuestions(10);
        $automation = app(FridayTriviaAutomationService::class);
        $friday = Carbon::create(2026, 11, 20, 0, 0, 0, 'Asia/Manila');

        $first = $automation->activateWeeklySession($friday);
        $this->assertSame('activated', $first['status']);
        $this->assertNotNull($first['session']);
        $this->assertSame(15, $first['session']->exp_reward);
        $this->assertCount(5, $first['session']->sessionQuestions);

        // Triggering again for the same Friday week must be skipped, not duplicated
        $second = $automation->activateWeeklySession($friday);
        $this->assertSame('skipped', $second['status']);
        $this->assertSame(1, TriviaSession::count());
    }

    public function test_consecutive_weeks_prioritize_unused_questions_and_archive_previous(): void
    {
        $this->seedQuestions(10);
        $automation = app(FridayTriviaAutomationService::class);

        $week1 = Carbon::create(2026, 11, 13, 0, 0, 0, 'Asia/Manila');
        $res1 = $automation->activateWeeklySession($week1);
        $session1 = $res1['session'];
        $week1QuestionIds = $session1->sessionQuestions->pluck('trivia_question_id')->all();

        $week2 = Carbon::create(2026, 11, 20, 0, 0, 0, 'Asia/Manila');
        $res2 = $automation->activateWeeklySession($week2);
        $session2 = $res2['session'];
        $week2QuestionIds = $session2->sessionQuestions->pluck('trivia_question_id')->all();

        $this->assertSame('archived', $session1->fresh()->status);
        $this->assertSame('active', $session2->status);
        $this->assertEmpty(array_intersect($week1QuestionIds, $week2QuestionIds));
    }

    public function test_member_completion_awards_exact_15_exp_once_and_prevents_duplicates(): void
    {
        $this->seedQuestions(5);
        $member = User::factory()->create(['role' => 'student']);
        $automation = app(FridayTriviaAutomationService::class);
        $res = $automation->activateWeeklySession(Carbon::create(2026, 11, 20, 0, 0, 0, 'Asia/Manila'));
        $session = $res['session'];

        $answers = [];
        foreach ($session->sessionQuestions as $sq) {
            // Pick index 0 regardless of correctness; reward must still be 15 EXP
            $answers[$sq->trivia_question_id] = 0;
        }

        $firstResp = $this->actingAs($member)->post(route('trivia.session.submit', $session), [
            'answers' => $answers,
        ]);
        $firstResp->assertRedirect(route('trivia.index'));
        $firstResp->assertSessionHas('success');

        $this->assertSame(1, TriviaSessionCompletion::where('trivia_session_id', $session->id)->where('user_id', $member->id)->count());
        $this->assertSame(15, (int) XpTransaction::where('user_id', $member->id)->sum('amount'));

        // Duplicate submission must be rejected and must not award additional EXP
        $secondResp = $this->actingAs($member)->post(route('trivia.session.submit', $session), [
            'answers' => $answers,
        ]);
        $secondResp->assertRedirect(route('trivia.index'));
        $secondResp->assertSessionHas('error');
        $this->assertSame(15, (int) XpTransaction::where('user_id', $member->id)->sum('amount'));
    }

    public function test_admin_can_view_automation_status_and_trigger_weekly_session(): void
    {
        $this->seedQuestions(5);
        $admin = User::factory()->create(['role' => 'super_admin']);

        $indexResp = $this->actingAs($admin)->get(route('admin.trivia.index'));
        $indexResp->assertOk();
        $indexResp->assertSee('Every Friday');

        $triggerResp = $this->actingAs($admin)->post(route('admin.trivia.trigger'));
        $triggerResp->assertRedirect();
        $this->assertSame(1, TriviaSession::count());
        $this->assertGreaterThanOrEqual(5, TriviaQuestion::count());
    }
}
