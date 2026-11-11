<?php

namespace App\Http\Controllers;

use App\Models\CodeCritique;
use App\Models\CodeShowcase;
use App\Services\AuditLogger;
use App\Services\ExpLedgerService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CodeCritiqueController extends Controller
{
    public function indexView(): View
    {
        return view('critique.index');
    }

    public function index(Request $request): JsonResponse
    {
        $viewer = $request->user();

        $query = CodeShowcase::query()
            ->with(['author:id,name,role,avatar_seed,avatar_style'])
            ->latest('created_at');

        if ($lang = $request->input('language')) {
            if ($lang !== 'all') {
                $query->where('language', $lang);
            }
        }

        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        if ($search = trim((string) $request->input('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $showcases = $query->paginate(12)->through(function ($s) use ($viewer) {
            $author = $s->author;
            $canEdit = $viewer && ($viewer->id === $s->user_id || $viewer->isAdmin() || $viewer->isSuperAdmin());
            $canDelete = $viewer && ($viewer->id === $s->user_id || $viewer->isAdmin() || $viewer->isSuperAdmin());

            return [
                'id' => $s->id,
                'title' => $s->title,
                'description' => $s->description,
                'language' => $s->language,
                'repository_url' => $s->repository_url,
                'live_demo_url' => $s->live_demo_url,
                'code_snippet' => $s->code_snippet,
                'bounty_exp' => $s->bounty_exp,
                'status' => $s->status,
                'views_count' => $s->views_count,
                'reviews_count' => $s->reviews_count,
                'can_edit' => $canEdit,
                'can_delete' => $canDelete,
                'author' => [
                    'id' => $author?->id,
                    'name' => $author?->name,
                    'avatar' => $author ? \App\Services\AvatarService::getUrl($author->avatar_seed, $author->avatar_style ?? 'adventurer') : null,
                ],
                'created_at' => $s->created_at?->diffForHumans(),
            ];
        });

        return response()->json($showcases);
    }

    public function show(CodeShowcase $showcase): JsonResponse
    {
        $showcase->increment('views_count');
        $showcase->load([
            'author:id,name,role,avatar_seed,avatar_style',
            'critiques.reviewer:id,name,role,avatar_seed,avatar_style',
        ]);

        return response()->json([
            'showcase' => $showcase,
            'critiques' => $showcase->critiques->map(function ($c) {
                $rev = $c->reviewer;
                return [
                    'id' => $c->id,
                    'summary' => $c->summary,
                    'suggested_diff' => $c->suggested_diff,
                    'line_number' => $c->line_number,
                    'is_accepted' => $c->is_accepted,
                    'helpful_votes' => $c->helpful_votes,
                    'reviewer' => [
                        'id' => $rev?->id,
                        'name' => $rev?->name,
                        'avatar' => $rev ? \App\Services\AvatarService::getUrl($rev->avatar_seed, $rev->avatar_style ?? 'adventurer') : null,
                    ],
                    'created_at' => $c->created_at?->diffForHumans(),
                ];
            }),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'description' => ['required', 'string', 'min:10', 'max:2500'],
            'repository_url' => ['nullable', 'url', 'max:255'],
            'live_demo_url' => ['nullable', 'url', 'max:255'],
            'code_snippet' => ['nullable', 'string', 'max:10000'],
            'language' => ['required', 'string', 'max:30'],
            'tech_stack' => ['nullable', 'array'],
            'review_goals' => ['nullable', 'array'],
            'bounty_exp' => ['nullable', 'integer', 'min:0', 'max:500'],
        ], [
            'title.min' => 'The project title must be at least 5 characters.',
            'description.min' => 'Please provide at least 10 characters describing your project or review questions.',
        ]);

        $showcase = CodeShowcase::create([
            'user_id' => $user->id,
            'title' => trim(strip_tags($validated['title'])),
            'description' => trim(strip_tags($validated['description'])),
            'repository_url' => $validated['repository_url'] ?? null,
            'live_demo_url' => $validated['live_demo_url'] ?? null,
            'code_snippet' => $validated['code_snippet'] ?? null,
            'language' => strtolower($validated['language']),
            'tech_stack' => $validated['tech_stack'] ?? [],
            'review_goals' => $validated['review_goals'] ?? [],
            'bounty_exp' => $validated['bounty_exp'] ?? 0,
            'status' => 'open',
        ]);

        AuditLogger::log('code_critique.showcase.created', $showcase);

        return response()->json([
            'success' => true,
            'message' => 'Project showcase submitted for peer review!',
            'showcase_id' => $showcase->id,
        ], 201);
    }

    public function update(Request $request, CodeShowcase $showcase): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $isAuthorized = ($showcase->user_id === $user->id || $user->isAdmin() || $user->isSuperAdmin());
        if (!$isAuthorized) {
            return response()->json(['message' => 'Unauthorized. You cannot edit this showcase.'], 403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'description' => ['required', 'string', 'min:10', 'max:2500'],
            'repository_url' => ['nullable', 'url', 'max:255'],
            'live_demo_url' => ['nullable', 'url', 'max:255'],
            'code_snippet' => ['nullable', 'string', 'max:10000'],
            'language' => ['required', 'string', 'max:30'],
            'bounty_exp' => ['nullable', 'integer', 'min:0', 'max:500'],
        ], [
            'title.min' => 'The project title must be at least 5 characters.',
            'description.min' => 'Please provide at least 10 characters describing your project or review questions.',
        ]);

        $showcase->update([
            'title' => trim(strip_tags($validated['title'])),
            'description' => trim(strip_tags($validated['description'])),
            'repository_url' => $validated['repository_url'] ?? null,
            'live_demo_url' => $validated['live_demo_url'] ?? null,
            'code_snippet' => $validated['code_snippet'] ?? null,
            'language' => strtolower($validated['language']),
            'bounty_exp' => $validated['bounty_exp'] ?? $showcase->bounty_exp,
        ]);

        AuditLogger::log('code_critique.showcase.updated', $showcase, [
            'actor_id' => $user->id,
            'title' => $showcase->title,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Project showcase updated successfully.',
            'showcase' => $showcase->fresh('author:id,name,role,avatar_seed,avatar_style'),
        ]);
    }

    public function destroy(Request $request, CodeShowcase $showcase): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $isAuthorized = ($showcase->user_id === $user->id || $user->isAdmin() || $user->isSuperAdmin());
        if (!$isAuthorized) {
            return response()->json(['message' => 'Unauthorized. You cannot delete this showcase.'], 403);
        }

        AuditLogger::log('code_critique.showcase.deleted', $showcase, [
            'actor_id' => $user->id,
            'title' => $showcase->title,
        ]);

        $showcase->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project showcase deleted successfully.',
        ]);
    }

    public function storeCritique(Request $request, CodeShowcase $showcase): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'summary' => ['required', 'string', 'min:10', 'max:5000'],
            'suggested_diff' => ['nullable', 'string', 'max:10000'],
            'line_number' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ], [
            'summary.min' => 'The critique summary must be at least 10 characters.',
        ]);

        $critique = CodeCritique::create([
            'code_showcase_id' => $showcase->id,
            'reviewer_id' => $user->id,
            'summary' => trim(strip_tags($validated['summary'])),
            'suggested_diff' => $validated['suggested_diff'] ?? null,
            'line_number' => $validated['line_number'] ?? null,
        ]);

        $showcase->increment('reviews_count');
        if ($showcase->status === 'open') {
            $showcase->update(['status' => 'in_review']);
        }

        // Notify showcase author
        if ($showcase->user_id !== $user->id) {
            NotificationService::send(
                $showcase->user_id,
                'code_critique',
                'Peer Code Review Received',
                "{$user->name} posted a constructive critique on your project '{$showcase->title}'.",
                route('critique.index')
            );
        }

        AuditLogger::log('code_critique.review.created', $critique);

        return response()->json([
            'success' => true,
            'message' => 'Critique posted! Thank you for peer mentoring.',
            'critique_id' => $critique->id,
        ], 201);
    }

    public function acceptCritique(Request $request, CodeCritique $critique, ExpLedgerService $expLedgerService): JsonResponse
    {
        $user = $request->user();
        $showcase = $critique->showcase;

        if ($showcase->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['message' => 'Only the project author can accept a solution.'], 403);
        }

        if ($critique->is_accepted) {
            return response()->json(['message' => 'This review is already marked as accepted.'], 400);
        }

        $critique->update(['is_accepted' => true]);
        $showcase->update(['status' => 'solved']);

        // Award bounty EXP to reviewer via authoritative ExpLedgerService
        if ($showcase->bounty_exp > 0) {
            $reviewer = $critique->reviewer;
            if ($reviewer) {
                $idempotencyKey = "critique_bounty_{$critique->id}";
                $expLedgerService->creditExp(
                    $reviewer,
                    $showcase->bounty_exp,
                    'contribution',
                    $showcase->id,
                    "Code review bounty reward for: {$showcase->title}",
                    null,
                    $idempotencyKey,
                    $user
                );

                NotificationService::send(
                    $reviewer->id,
                    'reward',
                    'Solution Accepted! Bounty Credited',
                    "Your code review on '{$showcase->title}' was accepted as the solution! +{$showcase->bounty_exp} EXP credited.",
                    route('critique.index')
                );
            }
        }

        AuditLogger::log('code_critique.solution.accepted', $critique, [
            'showcase_id' => $showcase->id,
            'reviewer_id' => $critique->reviewer_id,
            'bounty_exp' => $showcase->bounty_exp,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Solution marked as accepted! Bounty awarded.',
            'showcase_status' => 'solved',
        ]);
    }
}
