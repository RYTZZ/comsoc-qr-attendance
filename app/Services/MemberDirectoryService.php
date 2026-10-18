<?php

namespace App\Services;

use App\Models\Skill;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MemberDirectoryService
{
    public function searchDirectory(
        ?string $search = null,
        ?string $skillId = null,
        ?string $projectCategory = null,
        ?string $program = null,
        ?string $yearLevel = null,
        int $perPage = 18,
        int $page = 1
    ): LengthAwarePaginator {
        $query = User::with(['student', 'profile', 'skills'])
            ->where('role', 'student')
            ->whereHas('profile', function (Builder $pq) {
                $pq->where('is_public', true);
            });

        if ($search) {
            $query->where(function (Builder $q) use ($search) {
                $q->whereLike('name', "%{$search}%")
                    ->orWhereHas('profile', function (Builder $pq) use ($search) {
                        $pq->where(fn(Builder $g) => $g->whereLike('bio', "%{$search}%")
                            ->orWhereLike('github_url', "%{$search}%"));
                    })
                    ->orWhereHas('skills', function (Builder $sq) use ($search) {
                        $sq->whereLike('name', "%{$search}%");
                    });
            });
        }

        if ($skillId && $skillId !== 'all') {
            $query->whereHas('skills', function (Builder $sq) use ($skillId) {
                $sq->where('skills.id', $skillId);
            });
        }

        if ($projectCategory && $projectCategory !== 'all') {
            $query->whereHas('profile', function (Builder $pq) use ($projectCategory) {
                $pq->whereJsonContains('project_categories', $projectCategory);
            });
        }

        if ($program && $program !== 'all') {
            $query->whereHas('student', function (Builder $sq) use ($program) {
                $sq->where('program', $program);
            });
        }

        if ($yearLevel && $yearLevel !== 'all') {
            $query->whereHas('student', function (Builder $sq) use ($yearLevel) {
                $sq->where('year_level', $yearLevel);
            });
        }

        return $query->orderBy('name', 'asc')->paginate($perPage, ['*'], 'page', $page);
    }

    public function findCollaboratorMatches(User $user, int $limit = 6): Collection
    {
        $myProfile = $user->profile;
        $mySkills = $user->skills->pluck('id')->all();
        $myInterests = $myProfile?->interests ?? [];
        $myCollabPreferences = $myProfile?->collaboration_preferences ?? [];
        $myProjectCategories = $myProfile?->project_categories ?? [];

        $candidates = User::with(['student', 'profile', 'skills'])
            ->where('role', 'student')
            ->where('id', '!=', $user->id)
            ->whereHas('profile', function (Builder $pq) {
                $pq->where('is_public', true)
                    ->where('open_to_collaborate', true);
            })
            ->get();

        $scoredCandidates = $candidates->map(function (User $candidate) use (
            $mySkills,
            $myInterests,
            $myCollabPreferences,
            $myProjectCategories
        ) {
            $score = 0;
            $matchReasons = [];

            $candidateSkills = $candidate->skills;
            $sharedSkills = $candidateSkills->whereIn('id', $mySkills)->pluck('name')->all();
            $complementarySkills = $candidateSkills->whereNotIn('id', $mySkills)->pluck('name')->all();

            if (!empty($sharedSkills)) {
                $count = count($sharedSkills);
                $score += $count * 3;
                $matchReasons[] = 'Shared technical skills: ' . implode(', ', array_slice($sharedSkills, 0, 3));
            }

            if (!empty($complementarySkills)) {
                $count = count($complementarySkills);
                $score += min(6, $count * 2);
                $matchReasons[] = 'Complementary skills: ' . implode(', ', array_slice($complementarySkills, 0, 3));
            }

            $candProfile = $candidate->profile;
            if ($candProfile) {
                $candInterests = $candProfile->interests ?? [];
                $sharedInterests = array_values(array_intersect($myInterests, $candInterests));
                if (!empty($sharedInterests)) {
                    $score += count($sharedInterests) * 2;
                    $matchReasons[] = 'Shared interests: ' . implode(', ', array_slice($sharedInterests, 0, 2));
                }

                $candCollab = $candProfile->collaboration_preferences ?? [];
                $sharedCollab = array_values(array_intersect($myCollabPreferences, $candCollab));
                if (!empty($sharedCollab)) {
                    $score += count($sharedCollab) * 3;
                    $matchReasons[] = 'Aligned collaboration styles: ' . implode(', ', array_slice($sharedCollab, 0, 2));
                }

                $candCategories = $candProfile->project_categories ?? [];
                $sharedCategories = array_values(array_intersect($myProjectCategories, $candCategories));
                if (!empty($sharedCategories)) {
                    $score += count($sharedCategories) * 3;
                    $matchReasons[] = 'Matching project interests: ' . implode(', ', array_slice($sharedCategories, 0, 2));
                }
            }

            return [
                'user' => $candidate,
                'score' => $score,
                'match_reasons' => $matchReasons,
            ];
        })
        ->filter(fn($item) => $item['score'] > 0)
        ->sortByDesc('score')
        ->take($limit)
        ->values();

        return $scoredCandidates;
    }
}
