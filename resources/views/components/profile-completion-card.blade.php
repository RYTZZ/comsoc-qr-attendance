@props([
    'completion' => null,
    'compact' => false,
])

@php
    if (! is_array($completion) && auth()->check()) {
        $completion = app(\App\Services\ProfileCompletionService::class)->evaluate(auth()->user());
    }

    $percentage = (int) ($completion['percentage'] ?? 0);
    $isComplete = (bool) ($completion['is_complete'] ?? false);
    $rewardIssued = (bool) ($completion['reward_issued'] ?? false);
    $rewardRecord = $completion['reward_record'] ?? null;
    $expReward = (int) ($completion['exp_reward'] ?? 100);
    $pointsReward = (int) ($completion['points_reward'] ?? 50);
    $completedCount = (int) ($completion['completed_count'] ?? 0);
    $totalCount = (int) ($completion['total_count'] ?? 8);
    $completedFields = $completion['completed_fields'] ?? [];
    $missingFields = $completion['missing_fields'] ?? [];
    $categories = $completion['categories'] ?? [];
    $referenceKey = $completion['reference_key'] ?? null;
    $celebration = session('profile_completion_rewarded');
@endphp

@if (is_array($completion))
<div class="space-y-4">
    @if ($celebration)
        <div role="status" aria-live="polite" class="relative overflow-hidden rounded-2xl border border-emerald-500/40 bg-gradient-to-r from-emerald-950/90 via-slate-900 to-indigo-950/90 p-5 text-white shadow-xl">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3.5">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-300 ring-1 ring-emerald-400/30">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold tracking-tight text-emerald-300">
                            {{ $celebration['title'] ?? 'Profile Completed!' }}
                        </h3>
                        <p class="mt-0.5 text-sm text-slate-200">
                            {{ $celebration['message'] ?? "Congratulations! You've completed your COMSOC Hub profile. Your rewards have been credited successfully." }}
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2 sm:shrink-0">
                    <span class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-500/20 px-3.5 py-1.5 text-xs font-extrabold text-indigo-200 ring-1 ring-indigo-400/30">
                        +{{ $celebration['exp_awarded'] ?? $expReward }} EXP
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500/20 px-3.5 py-1.5 text-xs font-extrabold text-amber-200 ring-1 ring-amber-400/30">
                        +{{ $celebration['points_awarded'] ?? $pointsReward }} Points
                    </span>
                </div>
            </div>
        </div>
    @endif

    <div class="rounded-2xl border {{ $isComplete ? 'border-emerald-500/30 bg-slate-900/90' : 'border-slate-800 bg-slate-900/90' }} p-5 shadow-lg">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h3 class="text-base font-bold text-white">Profile Completion Progress</h3>
                    @if ($rewardIssued)
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-2.5 py-0.5 text-xs font-semibold text-emerald-300 ring-1 ring-emerald-500/30">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            Rewards Credited
                        </span>
                    @elseif ($isComplete)
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-2.5 py-0.5 text-xs font-semibold text-emerald-300 ring-1 ring-emerald-500/30">
                            100% Completed
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-500/15 px-2.5 py-0.5 text-xs font-semibold text-amber-300 ring-1 ring-amber-500/30">
                            {{ count($missingFields) }} {{ Str::plural('requirement', count($missingFields)) }} remaining
                        </span>
                    @endif
                </div>
                <p class="mt-1 text-xs text-slate-400">
                    @if ($rewardIssued)
                        You have completed your COMSOC Hub profile and earned your one-time completion reward.
                    @else
                        Complete 100% of your required profile fields to automatically unlock your one-time reward.
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex items-center gap-1.5 rounded-xl border border-indigo-500/30 bg-indigo-500/10 px-3 py-1.5 text-xs font-bold text-indigo-300">
                    <span>+{{ $expReward }} EXP</span>
                </div>
                <div class="inline-flex items-center gap-1.5 rounded-xl border border-amber-500/30 bg-amber-500/10 px-3 py-1.5 text-xs font-bold text-amber-300">
                    <span>+{{ $pointsReward }} Points</span>
                </div>
                <div class="ml-1 text-right">
                    <span class="text-xl font-extrabold {{ $isComplete ? 'text-emerald-400' : 'text-white' }}">{{ $percentage }}%</span>
                    <span class="block text-[11px] text-slate-400">{{ $completedCount }}/{{ $totalCount }} complete</span>
                </div>
            </div>
        </div>

        {{-- Progress Bar --}}
        <div class="mt-4">
            <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-800">
                <div
                    class="h-full rounded-full transition-all duration-500 {{ $isComplete ? 'bg-gradient-to-r from-emerald-500 to-teal-400' : 'bg-gradient-to-r from-indigo-500 via-sky-500 to-emerald-400' }}"
                    style="width: {{ max(0, min(100, $percentage)) }}%"
                    role="progressbar"
                    aria-valuenow="{{ $percentage }}"
                    aria-valuemin="0"
                    aria-valuemax="100"
                ></div>
            </div>
        </div>

        @if (! $compact || ! $isComplete)
            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($categories as $category)
                    <div class="rounded-xl border border-slate-800/90 bg-slate-950/60 p-3">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-bold text-slate-200">{{ $category['name'] }}</span>
                            <span class="text-[11px] font-semibold {{ $category['is_complete'] ? 'text-emerald-400' : 'text-amber-400' }}">
                                {{ $category['completed'] }}/{{ $category['total'] }}
                            </span>
                        </div>
                        <ul class="mt-2 space-y-1.5">
                            @foreach ($category['items'] as $item)
                                <li class="flex items-start justify-between gap-2 text-xs">
                                    <span class="flex items-center gap-1.5 {{ $item['completed'] ? 'text-slate-300' : 'text-slate-400' }}">
                                        @if ($item['completed'])
                                            <svg class="h-3.5 w-3.5 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                        @else
                                            <svg class="h-3.5 w-3.5 shrink-0 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="8" />
                                            </svg>
                                        @endif
                                        <span>{{ $item['label'] }}</span>
                                    </span>
                                    @if (! $item['completed'])
                                        @if ($item['editable_by_member'])
                                            <a href="{{ $item['action_url'] }}" class="shrink-0 font-semibold text-indigo-400 hover:text-indigo-300">
                                                Edit
                                            </a>
                                        @else
                                            <span class="shrink-0 text-[10px] font-medium text-slate-500" title="Managed by COMSOC Administrators">
                                                Official Record
                                            </span>
                                        @endif
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        @endif

        @if (! empty($missingFields))
            <div class="mt-4 flex flex-col gap-2 rounded-xl border border-amber-500/20 bg-amber-500/5 px-3.5 py-2.5 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-xs text-amber-200/90">
                    <span class="font-bold text-amber-300">Next Steps:</span>
                    Complete {{ collect($missingFields)->pluck('label')->join(', ', ' and ') }} to reach 100%.
                </div>
                <a href="{{ route('profile.edit') }}" class="inline-flex shrink-0 items-center gap-1 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-indigo-500">
                    Complete Profile
                </a>
            </div>
        @elseif ($rewardIssued && $rewardRecord)
            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-slate-800/80 pt-3 text-[11px] text-slate-400">
                <span>Reward Status: <strong class="text-emerald-400">Credited (+{{ $rewardRecord->exp_awarded }} EXP, +{{ $rewardRecord->points_awarded }} Points)</strong></span>
                <span>Reference: <code class="rounded bg-slate-800 px-1.5 py-0.5 text-slate-300">{{ $rewardRecord->reference_key }}</code></span>
            </div>
        @endif
    </div>
</div>
@endif

