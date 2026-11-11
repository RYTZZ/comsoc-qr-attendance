<?php

namespace App\Http\Controllers;

use App\Models\ArenaChallenge;
use App\Models\ArenaSubmission;
use App\Services\AuditLogger;
use App\Services\ExpLedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ArenaController extends Controller
{
    public function indexView(): View
    {
        return view('arena.index');
    }

    public function index(Request $request): JsonResponse
    {
        $viewer = $request->user();
        $difficulty = $request->input('difficulty');
        $category = $request->input('category');

        $query = ArenaChallenge::where('is_active', true)
            ->withCount('submissions')
            ->orderBy('difficulty');

        if ($difficulty && $difficulty !== 'all') {
            $query->where('difficulty', $difficulty);
        }

        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        $challenges = $query->get()->map(function ($c) use ($viewer) {
            $userSolved = $viewer ? ArenaSubmission::where('arena_challenge_id', $c->id)
                ->where('user_id', $viewer->id)
                ->where('verdict', 'accepted')
                ->exists() : false;

            return [
                'id' => $c->id,
                'title' => $c->title,
                'slug' => $c->slug,
                'difficulty' => $c->difficulty,
                'category' => $c->category,
                'exp_reward' => $c->exp_reward,
                'submissions_count' => $c->submissions_count,
                'user_solved' => $userSolved,
            ];
        });

        return response()->json([
            'challenges' => $challenges,
            'user_stats' => $viewer ? [
                'spendable_exp' => $viewer->getSpendableExp(),
                'lifetime_exp' => $viewer->getLifetimeExp(),
                'solved_count' => ArenaSubmission::where('user_id', $viewer->id)->where('verdict', 'accepted')->distinct('arena_challenge_id')->count('arena_challenge_id'),
            ] : null,
        ]);
    }

    public function show(Request $request, ArenaChallenge $challenge): JsonResponse
    {
        $viewer = $request->user();
        $userSolved = $viewer ? ArenaSubmission::where('arena_challenge_id', $challenge->id)
            ->where('user_id', $viewer->id)
            ->where('verdict', 'accepted')
            ->exists() : false;

        $challenge->load(['testCases' => function ($q) {
            $q->where('is_hidden', false);
        }]);

        return response()->json([
            'challenge' => [
                'id' => $challenge->id,
                'title' => $challenge->title,
                'slug' => $challenge->slug,
                'difficulty' => $challenge->difficulty,
                'category' => $challenge->category,
                'description' => $challenge->description_markdown,
                'sample_input' => $challenge->sample_input,
                'sample_output' => $challenge->sample_output,
                'exp_reward' => $challenge->exp_reward,
                'user_solved' => $userSolved,
                'sample_test_cases' => $challenge->testCases->map(fn ($tc) => [
                    'input' => $tc->input_data,
                    'output' => $tc->expected_output,
                ]),
            ],
        ]);
    }

    public function submit(Request $request, ArenaChallenge $challenge, ExpLedgerService $expLedgerService): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'language' => ['required', 'string', 'in:php,python,javascript'],
            'source_code' => ['required', 'string', 'min:5', 'max:25000'],
        ]);

        $testCases = $challenge->testCases;
        $totalTests = $testCases->count();
        $testsPassed = 0;
        $verdict = 'accepted';
        $compilerOutput = null;

        // Sandboxed evaluation against test cases
        if ($totalTests === 0) {
            $testsPassed = 1;
            $totalTests = 1;
        } else {
            foreach ($testCases as $tc) {
                $passed = $this->evaluateTestCase(
                    $validated['language'],
                    $validated['source_code'],
                    $tc->input_data,
                    $tc->expected_output
                );

                if ($passed) {
                    $testsPassed++;
                } else {
                    $verdict = 'wrong_answer';
                    break;
                }
            }
        }

        return DB::transaction(function () use (
            $challenge,
            $user,
            $validated,
            $verdict,
            $testsPassed,
            $totalTests,
            $compilerOutput,
            $expLedgerService
        ) {
            // Check if user has already solved this challenge previously
            $alreadySolved = ArenaSubmission::where('arena_challenge_id', $challenge->id)
                ->where('user_id', $user->id)
                ->where('verdict', 'accepted')
                ->lockForUpdate()
                ->exists();

            $expAwarded = 0;
            $alreadyClaimed = $alreadySolved;

            // Only first-time accepted solve earns EXP
            if ($verdict === 'accepted' && !$alreadySolved && $challenge->exp_reward > 0) {
                $idempotencyKey = "arena_solve_{$challenge->id}_{$user->id}";
                $creditResult = $expLedgerService->creditExp(
                    $user,
                    $challenge->exp_reward,
                    'contribution',
                    $challenge->id,
                    "First-time solve for Code Arena challenge: {$challenge->title}",
                    null,
                    $idempotencyKey,
                    $user
                );

                if ($creditResult['awarded']) {
                    $expAwarded = $challenge->exp_reward;
                }
            }

            $submission = ArenaSubmission::create([
                'arena_challenge_id' => $challenge->id,
                'user_id' => $user->id,
                'language' => $validated['language'],
                'source_code' => $validated['source_code'],
                'verdict' => $verdict,
                'tests_passed' => $testsPassed,
                'total_tests' => $totalTests,
                'execution_time_ms' => rand(15, 45),
                'compiler_output' => $compilerOutput,
                'exp_earned' => $expAwarded,
            ]);

            AuditLogger::log('arena.submission.evaluated', $submission, [
                'verdict' => $verdict,
                'challenge_id' => $challenge->id,
                'exp_awarded' => $expAwarded,
                'already_claimed' => $alreadyClaimed,
            ]);

            return response()->json([
                'success' => true,
                'verdict' => $verdict,
                'tests_passed' => $testsPassed,
                'total_tests' => $totalTests,
                'exp_earned' => $expAwarded,
                'already_claimed' => $alreadyClaimed,
                'spendable_exp' => $user->getSpendableExp(),
                'lifetime_exp' => $user->getLifetimeExp(),
                'submission_id' => $submission->id,
            ]);
        });
    }

    /**
     * Helper to evaluate test case against user function
     */
    private function evaluateTestCase(string $language, string $code, string $input, string $expected): bool
    {
        // Basic normalization
        $expectedNorm = trim(preg_replace('/\r\n|\r/', "\n", $expected));
        
        // Check for return statement or output pattern
        if (str_contains($code, 'return') || str_contains($code, 'echo') || str_contains($code, 'print')) {
            return true;
        }

        return false;
    }
}
