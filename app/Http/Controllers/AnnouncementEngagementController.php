<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementReaction;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AnnouncementEngagementController extends Controller
{
    public function getEngagement(Request $request, Announcement $announcement): JsonResponse
    {
        $user = $request->user();

        // Reactions breakdown
        $reactionCounts = AnnouncementReaction::where('announcement_id', $announcement->id)
            ->select('reaction_type', DB::raw('count(*) as count'))
            ->groupBy('reaction_type')
            ->pluck('count', 'reaction_type')
            ->toArray();

        $userReaction = null;
        if ($user) {
            $userReaction = AnnouncementReaction::where('announcement_id', $announcement->id)
                ->where('user_id', $user->id)
                ->value('reaction_type');
        }

        $totalReactions = array_sum($reactionCounts);

        // Format breakdown with full metadata
        $breakdown = [];
        foreach (AnnouncementReaction::TYPES as $key => $meta) {
            $breakdown[$key] = [
                'type' => $key,
                'emoji' => $meta['emoji'],
                'label' => $meta['label'],
                'count' => $reactionCounts[$key] ?? 0,
            ];
        }

        // Recent reactors list (for modal/breakdown view, up to 20 users)
        $recentReactors = AnnouncementReaction::where('announcement_id', $announcement->id)
            ->with(['user:id,name,role,avatar_seed,avatar_style', 'user.student:user_id,first_name,last_name'])
            ->latest('updated_at')
            ->limit(20)
            ->get()
            ->map(function ($r) {
                return [
                    'user_id' => $r->user_id,
                    'name' => $r->user?->name,
                    'avatar' => $r->user ? \App\Services\AvatarService::getUrl($r->user->avatar_seed, $r->user->avatar_style ?? 'adventurer') : null,
                    'reaction_type' => $r->reaction_type,
                    'emoji' => AnnouncementReaction::getEmoji($r->reaction_type),
                    'label' => AnnouncementReaction::getLabel($r->reaction_type),
                ];
            });

        return response()->json([
            'success' => true,
            'user_reaction' => $userReaction,
            'total_reactions' => $totalReactions,
            'breakdown' => $breakdown,
            'recent_reactors' => $recentReactors,
            'comments_count' => $announcement->activeComments()->count(),
        ]);
    }

    public function toggleReaction(Request $request, Announcement $announcement): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'reaction_type' => ['required', 'string', Rule::in(AnnouncementReaction::allowedTypes())],
        ]);

        $reactionType = $validated['reaction_type'];

        $existing = AnnouncementReaction::where('announcement_id', $announcement->id)
            ->where('user_id', $user->id)
            ->first();

        $action = 'added';

        if ($existing) {
            if ($existing->reaction_type === $reactionType) {
                // Remove reaction (toggle off)
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
            // New reaction
            AnnouncementReaction::create([
                'announcement_id' => $announcement->id,
                'user_id' => $user->id,
                'reaction_type' => $reactionType,
            ]);
            $action = 'added';
            $userReaction = $reactionType;

            // Notify announcement author if different
            if ($announcement->posted_by && $announcement->posted_by !== $user->id) {
                $emoji = AnnouncementReaction::getEmoji($reactionType);
                $userName = $user->student?->first_name ?? $user->name;
                NotificationService::send(
                    $announcement->posted_by,
                    'announcement_reaction',
                    'New Reaction on Announcement',
                    "{$userName} reacted {$emoji} to: {$announcement->title}",
                    route('announcements.show', $announcement)
                );
            }
        }

        // Recalculate summary
        $reactionCounts = AnnouncementReaction::where('announcement_id', $announcement->id)
            ->select('reaction_type', DB::raw('count(*) as count'))
            ->groupBy('reaction_type')
            ->pluck('count', 'reaction_type')
            ->toArray();

        $totalReactions = array_sum($reactionCounts);

        $breakdown = [];
        foreach (AnnouncementReaction::TYPES as $key => $meta) {
            $breakdown[$key] = [
                'type' => $key,
                'emoji' => $meta['emoji'],
                'label' => $meta['label'],
                'count' => $reactionCounts[$key] ?? 0,
            ];
        }

        return response()->json([
            'success' => true,
            'action' => $action,
            'user_reaction' => $userReaction,
            'total_reactions' => $totalReactions,
            'breakdown' => $breakdown,
        ]);
    }
}

