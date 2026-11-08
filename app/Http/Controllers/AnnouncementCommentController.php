<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementComment;
use App\Models\AnnouncementCommentReport;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AnnouncementCommentController extends Controller
{
    public function index(Request $request, Announcement $announcement): JsonResponse
    {
        $user = $request->user();

        // Load main comments with user, student profile, reports (for admins), and active replies
        $comments = AnnouncementComment::where('announcement_id', $announcement->id)
            ->whereNull('parent_id')
            ->where(function ($query) use ($user) {
                $query->where('status', AnnouncementComment::STATUS_ACTIVE);
                if ($user && $user->isAdmin()) {
                    $query->orWhere('status', AnnouncementComment::STATUS_HIDDEN);
                }
            })
            ->with([
                'user:id,name,role,avatar_seed,avatar_style',
                'user.student:user_id,first_name,last_name',
                'replies' => function ($q) use ($user) {
                    $q->where(function ($query) use ($user) {
                        $query->where('status', AnnouncementComment::STATUS_ACTIVE);
                        if ($user && $user->isAdmin()) {
                            $query->orWhere('status', AnnouncementComment::STATUS_HIDDEN);
                        }
                    })->with([
                        'user:id,name,role,avatar_seed,avatar_style',
                        'user.student:user_id,first_name,last_name',
                    ])->oldest('created_at');
                },
            ])
            ->latest('created_at')
            ->paginate(15);

        $transformed = $comments->getCollection()->map(function ($comment) use ($user) {
            return $this->formatComment($comment, $user);
        });

        return response()->json([
            'success' => true,
            'data' => $transformed,
            'current_page' => $comments->currentPage(),
            'last_page' => $comments->lastPage(),
            'total' => $comments->total(),
        ]);
    }

    public function store(Request $request, Announcement $announcement): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'min:1', 'max:2000'],
            'parent_id' => [
                'nullable',
                Rule::exists('announcement_comments', 'id')->where(function ($query) use ($announcement) {
                    $query->where('announcement_id', $announcement->id)
                        ->whereNull('parent_id') // Ensure only 1-level nesting: parent must be a main comment
                        ->whereNull('deleted_at');
                }),
            ],
        ]);

        $parent = null;
        if (!empty($validated['parent_id'])) {
            $parent = AnnouncementComment::find($validated['parent_id']);
        }

        // Clean & strip dangerous HTML tags
        $cleanContent = trim(strip_tags($validated['content']));
        if (empty($cleanContent)) {
            return response()->json(['message' => 'Comment cannot be blank or contain only markup.'], 422);
        }

        $comment = AnnouncementComment::create([
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
            'parent_id' => $parent?->id,
            'content' => $cleanContent,
            'status' => AnnouncementComment::STATUS_ACTIVE,
        ]);

        $comment->load(['user:id,name,role,avatar_seed,avatar_style', 'user.student:user_id,first_name,last_name']);

        // Audit log
        AuditLogger::log(
            $parent ? 'announcement.reply.created' : 'announcement.comment.created',
            $comment,
            ['announcement_id' => $announcement->id, 'parent_id' => $parent?->id]
        );

        // Notifications
        $userName = $user->student?->first_name ?? $user->name;

        if ($parent) {
            // Replying to a comment: notify parent comment author if different
            if ($parent->user_id !== $user->id) {
                NotificationService::send(
                    $parent->user_id,
                    'announcement_reply',
                    'New Reply to Your Comment',
                    "{$userName} replied to your comment on: {$announcement->title}",
                    route('announcements.show', $announcement)
                );
            }
        } else {
            // Main comment: notify announcement poster if different
            if ($announcement->posted_by && $announcement->posted_by !== $user->id) {
                NotificationService::send(
                    $announcement->posted_by,
                    'announcement_comment',
                    'New Comment on Announcement',
                    "{$userName} commented on: {$announcement->title}",
                    route('announcements.show', $announcement)
                );
            }
        }

        // Check for mentions: @Username or @"Full Name"
        $this->handleMentions($cleanContent, $comment, $announcement, $user);

        return response()->json([
            'success' => true,
            'message' => $parent ? 'Reply posted successfully.' : 'Comment posted successfully.',
            'comment' => $this->formatComment($comment, $user),
        ], 201);
    }

    public function update(Request $request, AnnouncementComment $comment): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        Gate::authorize('update', $comment);

        $validated = $request->validate([
            'content' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        $cleanContent = trim(strip_tags($validated['content']));
        if (empty($cleanContent)) {
            return response()->json(['message' => 'Comment cannot be blank.'], 422);
        }

        $comment->update(['content' => $cleanContent]);

        AuditLogger::log('announcement.comment.updated', $comment);

        return response()->json([
            'success' => true,
            'message' => 'Updated successfully.',
            'comment' => $this->formatComment($comment->fresh(['user', 'user.student']), $user),
        ]);
    }

    public function destroy(Request $request, AnnouncementComment $comment): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        Gate::authorize('delete', $comment);

        $commentId = $comment->id;
        $parentId = $comment->parent_id;

        // If it's a main comment, also soft-delete replies so no orphaned active replies remain
        if ($comment->isMainComment()) {
            $comment->replies()->delete();
        }

        $comment->delete();

        AuditLogger::log('announcement.comment.deleted', $comment, ['comment_id' => $commentId]);

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully.',
            'deleted_id' => $commentId,
            'parent_id' => $parentId,
        ]);
    }

    public function report(Request $request, AnnouncementComment $comment): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        Gate::authorize('report', $comment);

        $validated = $request->validate([
            'reason' => ['required', 'string', Rule::in(array_keys(AnnouncementCommentReport::REASONS))],
            'details' => ['nullable', 'string', 'max:500'],
        ]);

        $report = AnnouncementCommentReport::firstOrCreate(
            [
                'comment_id' => $comment->id,
                'reporter_id' => $user->id,
            ],
            [
                'reason' => $validated['reason'],
                'details' => $validated['details'] ?? null,
                'status' => AnnouncementCommentReport::STATUS_PENDING,
            ]
        );

        AuditLogger::log('announcement.comment.reported', $comment, [
            'report_id' => $report->id,
            'reason' => $validated['reason'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Comment reported to administrators for review.',
        ]);
    }

    public function moderate(Request $request, AnnouncementComment $comment): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'action' => ['required', 'string', Rule::in(['hide', 'unhide', 'delete'])],
        ]);

        if ($validated['action'] === 'hide') {
            $comment->update(['status' => AnnouncementComment::STATUS_HIDDEN]);
            AuditLogger::log('announcement.comment.hidden', $comment);
            $message = 'Comment has been hidden.';
        } elseif ($validated['action'] === 'unhide') {
            $comment->update(['status' => AnnouncementComment::STATUS_ACTIVE]);
            AuditLogger::log('announcement.comment.unhidden', $comment);
            $message = 'Comment has been unhidden.';
        } else {
            if ($comment->isMainComment()) {
                $comment->replies()->delete();
            }
            $comment->delete();
            AuditLogger::log('announcement.comment.force_deleted_by_admin', $comment);
            $message = 'Comment has been removed by administrator.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'comment' => $this->formatComment($comment->fresh(['user', 'user.student']), $user),
        ]);
    }

    private function formatComment(AnnouncementComment $comment, ?User $viewer): array
    {
        $author = $comment->user;
        $student = $author?->student;

        $displayName = $student
            ? trim($student->first_name . ' ' . $student->last_name)
            : ($author?->name ?? 'Unknown Member');

        $avatarUrl = $author ? \App\Services\AvatarService::getUrl($author->avatar_seed, $author->avatar_style ?? 'adventurer') : \App\Services\AvatarService::getUrl();

        $replies = [];
        if ($comment->relationLoaded('replies')) {
            $replies = $comment->replies->map(fn($r) => $this->formatComment($r, $viewer))->all();
        }

        return [
            'id' => $comment->id,
            'announcement_id' => $comment->announcement_id,
            'parent_id' => $comment->parent_id,
            'content' => $comment->content,
            'status' => $comment->status,
            'created_at' => $comment->created_at?->diffForHumans(),
            'created_at_exact' => $comment->created_at?->toIso8601String(),
            'is_edited' => $comment->updated_at && $comment->updated_at->gt($comment->created_at->addSeconds(5)),
            'author' => [
                'id' => $author?->id,
                'name' => $displayName,
                'role' => $author?->role,
                'avatar' => $avatarUrl,
                'initials' => strtoupper(substr($displayName, 0, 2)),
            ],
            'can_edit' => $viewer ? $viewer->id === $comment->user_id : false,
            'can_delete' => $viewer ? ($viewer->id === $comment->user_id || $viewer->isAdmin()) : false,
            'can_moderate' => $viewer ? $viewer->isAdmin() : false,
            'can_report' => $viewer ? ($viewer->id !== $comment->user_id) : false,
            'replies_count' => count($replies),
            'replies' => $replies,
        ];
    }

    private function handleMentions(string $content, AnnouncementComment $comment, Announcement $announcement, User $sender): void
    {
        // Extract @mentions e.g. @username or @firstname
        if (!str_contains($content, '@')) {
            return;
        }

        preg_match_all('/@([a-zA-Z0-9_\.\-]+)/', $content, $matches);
        if (empty($matches[1])) {
            return;
        }

        $usernames = array_unique($matches[1]);
        $users = User::whereIn('username', $usernames)
            ->where('id', '!=', $sender->id)
            ->where('is_active', true)
            ->get();

        $senderName = $sender->student?->first_name ?? $sender->name;

        foreach ($users as $mentionedUser) {
            NotificationService::send(
                $mentionedUser->id,
                'announcement_mention',
                'You were mentioned in a comment',
                "{$senderName} mentioned you in an announcement discussion: {$announcement->title}",
                route('announcements.show', $announcement)
            );
        }
    }
}

