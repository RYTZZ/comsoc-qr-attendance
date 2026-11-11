<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementComment;
use App\Models\AnnouncementCommentReaction;
use App\Models\AnnouncementCommentReport;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AnnouncementCommentController extends Controller
{
    public function searchMentionableUsers(Request $request): JsonResponse
    {
        $viewer = $request->user();
        if (!$viewer) {
            return response()->json(['users' => []], 401);
        }

        $rawQuery = (string) $request->input('q', '');
        // Strip leading @ or whitespace if user types @name
        $query = trim(ltrim(trim($rawQuery), '@'));

        $usersQuery = User::where('is_active', true)
            ->whereNull('deleted_at')
            ->whereNull('suspended_at')
            ->where('role', '!=', 'super_admin')
            ->where('id', '!=', $viewer->id)
            ->with(['student:id,first_name,last_name,program,year_level']);

        if ($query !== '') {
            $lower = strtolower($query);
            $usersQuery->where(function ($q) use ($lower) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$lower}%"])
                    ->orWhereRaw('LOWER(username) LIKE ?', ["%{$lower}%"])
                    ->orWhereRaw('LOWER(nickname) LIKE ?', ["%{$lower}%"])
                    ->orWhereHas('student', function ($sq) use ($lower) {
                        $sq->whereRaw('LOWER(first_name) LIKE ?', ["%{$lower}%"])
                            ->orWhereRaw('LOWER(last_name) LIKE ?', ["%{$lower}%"]);
                    });
            });
        }

        $users = $usersQuery->limit(20)->get()->map(function ($u) {
            $student = $u->student;
            $displayName = $student ? trim($student->first_name . ' ' . $student->last_name) : $u->name;

            return [
                'id' => $u->id,
                'name' => $displayName,
                'initials' => strtoupper(substr($displayName, 0, 2)),
                'avatar' => \App\Services\AvatarService::getUrl($u->avatar_seed, $u->avatar_style ?? 'adventurer'),
                'role' => $u->role,
            ];
        });

        return response()->json(['users' => $users]);
    }

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
                'user:id,name,role,student_id,avatar_seed,avatar_style',
                'user.student:id,first_name,last_name',
                'reactions.user:id,name,role,student_id,avatar_seed,avatar_style',
                'reactions.user.student:id,first_name,last_name',
                'replies' => function ($q) use ($user) {
                    $q->where(function ($query) use ($user) {
                        $query->where('status', AnnouncementComment::STATUS_ACTIVE);
                        if ($user && $user->isAdmin()) {
                            $query->orWhere('status', AnnouncementComment::STATUS_HIDDEN);
                        }
                    })->with([
                        'user:id,name,role,student_id,avatar_seed,avatar_style',
                        'user.student:id,first_name,last_name',
                        'reactions.user:id,name,role,student_id,avatar_seed,avatar_style',
                        'reactions.user.student:id,first_name,last_name',
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
            'mentioned_user_ids' => ['nullable', 'array'],
            'mentioned_user_ids.*' => [
                'uuid',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', '!=', 'super_admin');
                }),
            ],
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

        $comment->load(['user:id,name,role,student_id,avatar_seed,avatar_style', 'user.student:id,first_name,last_name']);

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

        // Check for mentions: direct mentioned_user_ids, @Username or @"Full Name"
        $this->handleMentions($cleanContent, $comment, $announcement, $user, $validated['mentioned_user_ids'] ?? []);

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
            'mentioned_user_ids' => ['nullable', 'array'],
            'mentioned_user_ids.*' => [
                'uuid',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', '!=', 'super_admin');
                }),
            ],
        ]);

        $cleanContent = trim(strip_tags($validated['content']));
        if (empty($cleanContent)) {
            return response()->json(['message' => 'Comment cannot be blank.'], 422);
        }

        $comment->update(['content' => $cleanContent]);

        // Process mentions on edit as well
        $this->handleMentions($cleanContent, $comment, $comment->announcement, $user, $validated['mentioned_user_ids'] ?? []);

        AuditLogger::log('announcement.comment.updated', $comment);

        return response()->json([
            'success' => true,
            'message' => 'Updated successfully.',
            'comment' => $this->formatComment($comment->fresh(['user', 'user.student', 'reactions.user', 'reactions.user.student']), $user),
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

    public function toggleReaction(Request $request, AnnouncementComment $comment): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Validate comment status
        if ($comment->status === AnnouncementComment::STATUS_HIDDEN && !$user->isAdmin()) {
            return response()->json(['message' => 'Cannot react to a hidden comment.'], 403);
        }

        $validated = $request->validate([
            'reaction_type' => ['required', 'string', Rule::in(AnnouncementCommentReaction::allowedTypes())],
        ]);

        $reactionType = $validated['reaction_type'];

        $existing = AnnouncementCommentReaction::where('comment_id', $comment->id)
            ->where('user_id', $user->id)
            ->first();

        $action = 'added';
        $userReaction = $reactionType;

        if ($existing) {
            if ($existing->reaction_type === $reactionType) {
                // Toggle off
                $existing->delete();
                $action = 'removed';
                $userReaction = null;
            } else {
                // Change reaction
                $existing->update(['reaction_type' => $reactionType]);
                $action = 'changed';
                $userReaction = $reactionType;
            }
        } else {
            // Create new reaction
            AnnouncementCommentReaction::create([
                'comment_id' => $comment->id,
                'user_id' => $user->id,
                'reaction_type' => $reactionType,
            ]);
            $action = 'added';
            $userReaction = $reactionType;

            // Notify comment author if different
            if ($comment->user_id && $comment->user_id !== $user->id) {
                $emoji = AnnouncementCommentReaction::getEmoji($reactionType);
                $userName = $user->student?->first_name ?? $user->name;
                $targetType = $comment->isReply() ? 'reply' : 'comment';
                NotificationService::send(
                    $comment->user_id,
                    'announcement_comment_reaction',
                    'New Reaction on Your Comment',
                    "{$userName} reacted {$emoji} to your {$targetType}",
                    route('announcements.show', $comment->announcement_id)
                );
            }
        }

        // Recalculate reaction stats for this comment
        $reactionCounts = AnnouncementCommentReaction::where('comment_id', $comment->id)
            ->select('reaction_type', DB::raw('count(*) as count'))
            ->groupBy('reaction_type')
            ->pluck('count', 'reaction_type')
            ->toArray();

        $totalReactions = array_sum($reactionCounts);

        $reactors = AnnouncementCommentReaction::where('comment_id', $comment->id)
            ->with(['user:id,name,role,student_id', 'user.student:id,first_name,last_name'])
            ->latest('updated_at')
            ->limit(10)
            ->get()
            ->map(function ($cr) {
                $rUser = $cr->user;
                $rStudent = $rUser?->student;
                $rName = $rStudent ? trim($rStudent->first_name . ' ' . $rStudent->last_name) : ($rUser?->name ?? 'Member');
                return [
                    'user_id' => $cr->user_id,
                    'name' => $rName,
                    'reaction_type' => $cr->reaction_type,
                    'emoji' => AnnouncementCommentReaction::getEmoji($cr->reaction_type),
                    'label' => AnnouncementCommentReaction::getLabel($cr->reaction_type),
                ];
            })
            ->all();

        return response()->json([
            'success' => true,
            'action' => $action,
            'comment_id' => $comment->id,
            'user_reaction' => $userReaction,
            'total_reactions' => $totalReactions,
            'reaction_counts' => $reactionCounts,
            'reactors' => $reactors,
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
            'comment' => $this->formatComment($comment->fresh(['user', 'user.student', 'reactions.user', 'reactions.user.student']), $user),
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

        // Reactions calculation
        $userReaction = null;
        $reactionCounts = [];
        $reactors = [];

        if ($comment->relationLoaded('reactions')) {
            $commentReactions = $comment->reactions;
            foreach ($commentReactions as $cr) {
                $reactionCounts[$cr->reaction_type] = ($reactionCounts[$cr->reaction_type] ?? 0) + 1;
                if ($viewer && $cr->user_id === $viewer->id) {
                    $userReaction = $cr->reaction_type;
                }
            }
            $reactors = $commentReactions->take(10)->map(function ($cr) {
                $rUser = $cr->user;
                $rStudent = $rUser?->student;
                $rName = $rStudent ? trim($rStudent->first_name . ' ' . $rStudent->last_name) : ($rUser?->name ?? 'Member');
                return [
                    'user_id' => $cr->user_id,
                    'name' => $rName,
                    'reaction_type' => $cr->reaction_type,
                    'emoji' => AnnouncementCommentReaction::getEmoji($cr->reaction_type),
                    'label' => AnnouncementCommentReaction::getLabel($cr->reaction_type),
                ];
            })->all();
        }

        $totalReactions = array_sum($reactionCounts);

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
            'user_reaction' => $userReaction,
            'total_reactions' => $totalReactions,
            'reaction_counts' => $reactionCounts,
            'reactors' => $reactors,
            'replies_count' => count($replies),
            'replies' => $replies,
        ];
    }

    private function handleMentions(string $content, AnnouncementComment $comment, Announcement $announcement, User $sender, array $explicitUserIds = []): void
    {
        $mentionedUserIds = is_array($explicitUserIds) ? $explicitUserIds : [];

        // 1. Match structured mentions: @[Display Name](user:UUID)
        if (preg_match_all('/@\[([^\]]+)\]\(user:([a-zA-Z0-9\-]+)\)/', $content, $structuredMatches)) {
            $mentionedUserIds = array_merge($mentionedUserIds, $structuredMatches[2]);
        }

        // 2. Match plain mentions: @username or @handle or @"Full Name"
        if (preg_match_all('/@([a-zA-Z0-9_\.\-]+)/', $content, $plainMatches)) {
            $plainHandles = array_unique($plainMatches[1]);
            $usersByUsername = User::whereIn('username', $plainHandles)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->whereNull('suspended_at')
                ->pluck('id')
                ->all();
            $mentionedUserIds = array_merge($mentionedUserIds, $usersByUsername);
        }

        $mentionedUserIds = array_unique(array_filter($mentionedUserIds, fn($id) => !empty($id) && $id !== $sender->id));
        if (empty($mentionedUserIds)) {
            return;
        }

        $users = User::whereIn('id', $mentionedUserIds)
            ->where('is_active', true)
            ->where('role', '!=', 'super_admin')
            ->whereNull('deleted_at')
            ->whereNull('suspended_at')
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

