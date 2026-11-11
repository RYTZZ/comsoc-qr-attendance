<?php

namespace App\Http\Controllers;

use App\Models\PulseTopic;
use App\Models\PulseVote;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PulseController extends Controller
{
    /**
     * Show Pulse interactive student voice page
     */
    public function indexView(): View
    {
        return view('pulse.index');
    }

    /**
     * Fetch list of anonymous Pulse feedback topics
     */
    public function index(Request $request): JsonResponse
    {
        $viewer = $request->user();
        $category = $request->input('category');
        $status = $request->input('status');
        $search = trim((string) $request->input('q', ''));

        $query = PulseTopic::query()
            ->with('responder:id,name,role')
            ->orderByDesc('is_pinned')
            ->orderByDesc('votes_count')
            ->orderByDesc('created_at');

        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $voterHash = $viewer ? PulseTopic::generateBlindHash($viewer->id, 'pulse_vote') : null;

        $topics = $query->paginate(15)->through(function ($t) use ($voterHash) {
            $hasVoted = $voterHash ? PulseVote::where('pulse_topic_id', $t->id)->where('voter_hash', $voterHash)->exists() : false;

            return [
                'id' => $t->id,
                'category' => $t->category,
                'title' => $t->title,
                'content' => $t->content,
                'status' => $t->status,
                'votes_count' => $t->votes_count,
                'has_voted' => $hasVoted,
                'is_pinned' => $t->is_pinned,
                'official_response' => $t->official_response,
                'responder' => $t->responder ? [
                    'name' => $t->responder->name,
                    'role' => $t->responder->role,
                ] : null,
                'responded_at' => $t->responded_at?->diffForHumans(),
                'created_at' => $t->created_at?->diffForHumans(),
            ];
        });

        return response()->json($topics);
    }

    /**
     * Submit an anonymous feedback topic
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'category' => ['required', 'string', Rule::in(['lab_facilities', 'curriculum', 'org_governance', 'student_welfare', 'general'])],
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'content' => ['required', 'string', 'min:15', 'max:2500'],
        ]);

        $cleanTitle = trim(strip_tags($validated['title']));
        $cleanContent = trim(strip_tags($validated['content']));

        // Blind token: no correlation to user_id saved
        $blindHash = PulseTopic::generateBlindHash($user->id, 'pulse_topic_' . date('Y-m-d'));

        // Prevent duplicate spam submissions within the same day
        $duplicate = PulseTopic::where('blind_session_hash', $blindHash)
            ->where('title', $cleanTitle)
            ->exists();

        if ($duplicate) {
            return response()->json(['message' => 'You have already submitted this feedback topic today.'], 422);
        }

        $topic = PulseTopic::create([
            'category' => $validated['category'],
            'title' => $cleanTitle,
            'content' => $cleanContent,
            'blind_session_hash' => $blindHash,
            'status' => 'under_review',
            'votes_count' => 1,
        ]);

        // Auto-cast initial vote for author using blind voter hash
        $voterHash = PulseTopic::generateBlindHash($user->id, 'pulse_vote');
        PulseVote::create([
            'pulse_topic_id' => $topic->id,
            'voter_hash' => $voterHash,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Feedback submitted anonymously. Thank you for your voice!',
            'topic_id' => $topic->id,
        ], 201);
    }

    /**
     * Upvote an existing feedback topic anonymously
     */
    public function vote(Request $request, PulseTopic $topic): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $voterHash = PulseTopic::generateBlindHash($user->id, 'pulse_vote');

        $existingVote = PulseVote::where('pulse_topic_id', $topic->id)
            ->where('voter_hash', $voterHash)
            ->first();

        if ($existingVote) {
            // Toggle vote off
            $existingVote->delete();
            $topic->decrement('votes_count');
            $action = 'removed';
            $hasVoted = false;
        } else {
            // Upvote
            PulseVote::create([
                'pulse_topic_id' => $topic->id,
                'voter_hash' => $voterHash,
            ]);
            $topic->increment('votes_count');
            $action = 'added';
            $hasVoted = true;
        }

        return response()->json([
            'success' => true,
            'action' => $action,
            'votes_count' => $topic->fresh()->votes_count,
            'has_voted' => $hasVoted,
        ]);
    }

    /**
     * Officer response & status update
     */
    public function respond(Request $request, PulseTopic $topic): JsonResponse
    {
        $officer = $request->user();
        if (!$officer || !$officer->isAdmin()) {
            return response()->json(['message' => 'Unauthorized officer.'], 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(['under_review', 'in_progress', 'resolved', 'closed'])],
            'official_response' => ['required', 'string', 'min:5', 'max:2000'],
            'is_pinned' => ['nullable', 'boolean'],
        ]);

        $topic->update([
            'status' => $validated['status'],
            'official_response' => trim(strip_tags($validated['official_response'])),
            'responded_by' => $officer->id,
            'responded_at' => now(),
            'is_pinned' => $validated['is_pinned'] ?? $topic->is_pinned,
        ]);

        AuditLogger::log('pulse.topic.responded', $topic, [
            'officer_id' => $officer->id,
            'status' => $validated['status'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Official response published.',
            'topic' => $topic->fresh('responder:id,name,role'),
        ]);
    }
}
