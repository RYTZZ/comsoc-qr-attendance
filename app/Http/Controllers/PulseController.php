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

        $topics = $query->paginate(15)->through(function ($t) use ($viewer, $voterHash) {
            $hasVoted = $voterHash ? PulseVote::where('pulse_topic_id', $t->id)->where('voter_hash', $voterHash)->exists() : false;

            // Check if current user is owner (or authorized admin) for edit/delete permissions
            $canEdit = $viewer && ($viewer->id === $t->user_id || $viewer->isAdmin() || $viewer->isSuperAdmin());
            $canDelete = $viewer && ($viewer->id === $t->user_id || $viewer->isAdmin() || $viewer->isSuperAdmin());

            return [
                'id' => $t->id,
                'category' => $t->category,
                'title' => $t->title,
                'content' => $t->content,
                'status' => $t->status,
                'votes_count' => $t->votes_count,
                'has_voted' => $hasVoted,
                'is_pinned' => $t->is_pinned,
                'can_edit' => $canEdit,
                'can_delete' => $canDelete,
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
        ], [
            'title.min' => 'The topic summary must be at least 5 characters.',
            'content.min' => 'Please provide at least 15 characters describing your concern or suggestion.',
        ]);

        $cleanTitle = trim(strip_tags($validated['title']));
        $cleanContent = trim(strip_tags($validated['content']));

        $blindHash = PulseTopic::generateBlindHash($user->id, 'pulse_topic_' . date('Y-m-d'));

        // Prevent duplicate submissions within the same day
        $duplicate = PulseTopic::where('blind_session_hash', $blindHash)
            ->where('title', $cleanTitle)
            ->exists();

        if ($duplicate) {
            return response()->json([
                'message' => 'You have already submitted this feedback topic today.',
                'errors' => ['title' => ['You have already submitted this feedback topic today.']],
            ], 422);
        }

        $topic = PulseTopic::create([
            'user_id' => $user->id,
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
     * Update an existing submission (Owner or Officer)
     */
    public function update(Request $request, PulseTopic $topic): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $isAuthorized = ($topic->user_id === $user->id || $user->isAdmin() || $user->isSuperAdmin());
        if (!$isAuthorized) {
            return response()->json(['message' => 'Unauthorized. You cannot edit this submission.'], 403);
        }

        $validated = $request->validate([
            'category' => ['required', 'string', Rule::in(['lab_facilities', 'curriculum', 'org_governance', 'student_welfare', 'general'])],
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'content' => ['required', 'string', 'min:15', 'max:2500'],
        ], [
            'title.min' => 'The topic summary must be at least 5 characters.',
            'content.min' => 'Please provide at least 15 characters describing your concern or suggestion.',
        ]);

        $updateData = [
            'category' => $validated['category'],
            'title' => trim(strip_tags($validated['title'])),
            'content' => trim(strip_tags($validated['content'])),
        ];

        // If an edited submission is already reviewed or altered by non-admin, reset to under_review
        if (!$user->isAdmin() && !$user->isSuperAdmin()) {
            $updateData['status'] = 'under_review';
        }

        $topic->update($updateData);

        AuditLogger::log('pulse.topic.updated', $topic, [
            'actor_id' => $user->id,
            'title' => $topic->title,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Feedback topic updated successfully.',
            'topic' => $topic->fresh(),
        ]);
    }

    /**
     * Delete an existing submission (Owner or Officer)
     */
    public function destroy(Request $request, PulseTopic $topic): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $isAuthorized = ($topic->user_id === $user->id || $user->isAdmin() || $user->isSuperAdmin());
        if (!$isAuthorized) {
            return response()->json(['message' => 'Unauthorized. You cannot delete this submission.'], 403);
        }

        AuditLogger::log('pulse.topic.deleted', $topic, [
            'actor_id' => $user->id,
            'title' => $topic->title,
        ]);

        $topic->delete();

        return response()->json([
            'success' => true,
            'message' => 'Feedback topic deleted successfully.',
        ]);
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
