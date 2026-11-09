@props(['announcement', 'reactionsConfig', 'commentReactionsConfig'])

@php
    $user = auth()->user();
    $student = $user?->student;
    $userAvatar = $user ? \App\Services\AvatarService::getUrl($user->avatar_seed, $user->avatar_style ?? 'adventurer') : \App\Services\AvatarService::getUrl();
    $userName = $student ? trim($student->first_name . ' ' . $student->last_name) : ($user?->name ?? 'You');
    $userInitials = $user ? strtoupper(substr($user->nickname ?: $user->name, 0, 2)) : 'ME';
@endphp

<div x-data="announcementEngagement({
    announcementId: '{{ $announcement->id }}',
    routes: {
        engagement: '{{ route('announcements.engagement', $announcement) }}',
        reactionsToggle: '{{ route('announcements.reactions.toggle', $announcement) }}',
        commentsIndex: '{{ route('announcements.comments.index', $announcement) }}',
        commentsStore: '{{ route('announcements.comments.store', $announcement) }}',
        commentReactionToggleBase: '{{ url('/announcements/comments') }}',
        mentionableUsers: '{{ route('announcements.mentionable-users') }}',
    },
    currentUser: {
        id: '{{ $user?->id }}',
        name: '{{ addslashes($userName) }}',
        initials: '{{ $userInitials }}',
        avatar: '{{ $userAvatar }}',
        isAdmin: {{ $user && $user->isAdmin() ? 'true' : 'false' }},
    }
})"
     class="mt-6 pt-6 border-t border-slate-800 space-y-6">

    <!-- Engagement Summary Bar & Reaction Breakdown (Hidden when zero reactions and zero comments) -->
    <div x-show="totalReactions > 0 || totalComments > 0"
         x-cloak
         class="flex items-center justify-between gap-3 text-xs text-slate-400 px-1 py-1">
        <!-- Reaction icons + count preview (Only displayed when totalReactions > 0) -->
        <div class="flex items-center gap-2">
            <template x-if="totalReactions > 0">
                <button type="button"
                        @click="showReactorsModal = true"
                        class="flex items-center gap-1.5 hover:text-white transition group focus:outline-none focus:ring-1 focus:ring-rose-500/50 rounded-full px-2 py-0.5 bg-slate-900/60 border border-slate-800">
                    <span class="flex items-center -space-x-1">
                        <template x-for="r in topReactions" :key="r.type">
                            <span class="inline-flex items-center justify-center w-5 h-5 text-sm rounded-full bg-slate-800 border border-slate-700 shadow-sm"
                                  x-text="r.emoji"></span>
                        </template>
                    </span>
                    <span class="font-bold text-slate-300 ml-1" x-text="totalReactions"></span>
                    <span class="text-slate-500 group-hover:text-slate-400">reactions</span>
                </button>
            </template>
        </div>

        <!-- Comments Count (Only displayed when totalComments > 0) -->
        <div>
            <template x-if="totalComments > 0">
                <button type="button"
                        @click="focusMainCommentBox()"
                        class="flex items-center gap-1.5 text-slate-400 hover:text-white transition focus:outline-none">
                    <span class="font-medium text-slate-300" x-text="totalComments"></span>
                    <span x-text="totalComments === 1 ? 'comment' : 'comments'"></span>
                </button>
            </template>
        </div>
    </div>

    <!-- Facebook-Style Shared Full-Width Action Container: [ ❤️ React ]  [ 💬 Comment ] -->
    <div class="relative flex items-center justify-start gap-2 py-1.5 px-2 rounded-2xl border border-slate-800/80 bg-[#172033]/70 backdrop-blur-sm">
        <!-- React Button Container with Hover / Click / Long-Press Picker (with 250ms persistence bridge) -->
        <div class="relative"
             x-data="{ closeTimer: null }"
             @click.away="pickerOpen = false"
             @mouseenter="if (closeTimer) { clearTimeout(closeTimer); closeTimer = null; } pickerOpen = true"
             @mouseleave="closeTimer = setTimeout(() => { pickerOpen = false; }, 250)">
            <button type="button"
                    @click="handleReactClick($event, () => pickerOpen = !pickerOpen)"
                    @touchstart.passive="startLongPress($event, () => pickerOpen = true)"
                    @touchmove.passive="moveLongPress($event)"
                    @touchend.passive="cancelLongPress()"
                    @touchcancel.passive="cancelLongPress()"
                    aria-label="React to announcement"
                    class="flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-semibold transition-all duration-150 select-none cursor-pointer hover:bg-slate-800/70"
                    :class="userReaction
                        ? 'text-rose-400 hover:text-rose-300'
                        : 'text-slate-300 hover:text-white'">
                <template x-if="userReaction">
                    <span class="flex items-center gap-1.5">
                        <span class="text-base" x-text="breakdown[userReaction]?.emoji"></span>
                        <span class="capitalize" x-text="breakdown[userReaction]?.label"></span>
                    </span>
                </template>
                <template x-if="!userReaction">
                    <span class="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                        </svg>
                        <span>React</span>
                    </span>
                </template>
            </button>

            <!-- Interactive Reaction Picker (6 reactions: Love, Like, Haha, Wow, Sad, Angry) - Clean Icons Only -->
            <div x-show="pickerOpen"
                 x-cloak
                 @mouseenter="if (closeTimer) { clearTimeout(closeTimer); closeTimer = null; }"
                 x-transition:enter="transition ease-out duration-150 transform"
                 x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-100 transform"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                 class="absolute left-0 bottom-full mb-2 z-30 p-1.5 bg-[#1E293B] border border-slate-700/80 rounded-2xl shadow-2xl flex items-center gap-1 backdrop-blur-md">
                <!-- Invisible bridge preventing cursor drop between button and popup -->
                <div class="absolute left-0 -bottom-2.5 w-full h-2.5"></div>

                <button type="button"
                        @click="pickerOpen = false; toggleReaction('love')"
                        class="p-2 sm:px-2.5 sm:py-2 rounded-xl hover:bg-slate-700/60 transition-transform active:scale-90 hover:scale-125 flex items-center justify-center group focus:outline-none cursor-pointer select-none"
                        :class="userReaction === 'love' ? 'bg-rose-500/20 ring-1 ring-rose-500/50' : ''">
                    <span class="text-2xl leading-none">❤️</span>
                </button>

                <button type="button"
                        @click="pickerOpen = false; toggleReaction('like')"
                        class="p-2 sm:px-2.5 sm:py-2 rounded-xl hover:bg-slate-700/60 transition-transform active:scale-90 hover:scale-125 flex items-center justify-center group focus:outline-none cursor-pointer select-none"
                        :class="userReaction === 'like' ? 'bg-rose-500/20 ring-1 ring-rose-500/50' : ''">
                    <span class="text-2xl leading-none">👍</span>
                </button>

                <button type="button"
                        @click="pickerOpen = false; toggleReaction('haha')"
                        class="p-2 sm:px-2.5 sm:py-2 rounded-xl hover:bg-slate-700/60 transition-transform active:scale-90 hover:scale-125 flex items-center justify-center group focus:outline-none cursor-pointer select-none"
                        :class="userReaction === 'haha' ? 'bg-rose-500/20 ring-1 ring-rose-500/50' : ''">
                    <span class="text-2xl leading-none">😂</span>
                </button>

                <button type="button"
                        @click="pickerOpen = false; toggleReaction('wow')"
                        class="p-2 sm:px-2.5 sm:py-2 rounded-xl hover:bg-slate-700/60 transition-transform active:scale-90 hover:scale-125 flex items-center justify-center group focus:outline-none cursor-pointer select-none"
                        :class="userReaction === 'wow' ? 'bg-rose-500/20 ring-1 ring-rose-500/50' : ''">
                    <span class="text-2xl leading-none">😮</span>
                </button>

                <button type="button"
                        @click="pickerOpen = false; toggleReaction('sad')"
                        class="p-2 sm:px-2.5 sm:py-2 rounded-xl hover:bg-slate-700/60 transition-transform active:scale-90 hover:scale-125 flex items-center justify-center group focus:outline-none cursor-pointer select-none"
                        :class="userReaction === 'sad' ? 'bg-rose-500/20 ring-1 ring-rose-500/50' : ''">
                    <span class="text-2xl leading-none">😢</span>
                </button>

                <button type="button"
                        @click="pickerOpen = false; toggleReaction('angry')"
                        class="p-2 sm:px-2.5 sm:py-2 rounded-xl hover:bg-slate-700/60 transition-transform active:scale-90 hover:scale-125 flex items-center justify-center group focus:outline-none cursor-pointer select-none"
                        :class="userReaction === 'angry' ? 'bg-rose-500/20 ring-1 ring-rose-500/50' : ''">
                    <span class="text-2xl leading-none">😡</span>
                </button>
            </div>
        </div>

        <!-- Jump to Comment Button -->
        <button type="button"
                @click="focusMainCommentBox()"
                class="flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-300 hover:bg-slate-800/70 hover:text-white transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>
            </svg>
            <span>Comment</span>
        </button>
    </div>

    <!-- Main Comment Composer -->
    <div class="space-y-3 pt-2">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-white font-brand-display flex items-center gap-2">
                <span>Discussion</span>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 border border-slate-700/60"
                      x-show="totalComments > 0"
                      x-cloak
                      x-text="totalComments"></span>
            </h3>
        </div>

        <!-- Unified Comment Composer: [USER AVATAR] [ Write a comment... ] [Send] -->
        <div class="relative flex items-center gap-2.5 sm:gap-3">
            <!-- Student Avatar -->
            <x-user-avatar :user="$user" size="md" class="w-10 h-10 rounded-full border border-[#8F3033]/60 shadow-md shrink-0 self-center" />

            <!-- Unified Input Container -->
            <div class="relative flex-1 min-w-0 flex items-center gap-2 rounded-2xl bg-[#0B1120] border border-[#334155] focus-within:border-[#8F3033] focus-within:ring-2 focus-within:ring-[#8F3033]/40 transition p-1.5 pl-3 sm:pl-3.5 shadow-inner">
                <!-- Auto-expanding Textarea -->
                <textarea x-ref="mainCommentInput"
                          x-model="newCommentText"
                          @input="onInputMention($event, 'main'); autoResizeTextarea($event.target)"
                          @keydown="handleComposerKeydown($event, 'main')"
                          rows="1"
                          maxlength="2000"
                          :placeholder="'Comment as ' + currentUser.name"
                          class="flex-1 min-w-0 bg-transparent border-0 p-0 py-1 text-xs sm:text-sm text-slate-100 placeholder-slate-400 focus:ring-0 focus:outline-none resize-none leading-relaxed min-h-[38px] max-h-32 overflow-y-auto"></textarea>

                <!-- Mention Suggestion Dropdown (Main Composer) -->
                <div x-show="mentionDropdownOpen && mentionContext === 'main'"
                     x-cloak
                     @click.away="closeMentionDropdown()"
                     class="absolute left-0 bottom-full mb-2 w-80 max-w-[calc(100vw-2rem)] bg-[#0B1120] border border-[#334155] rounded-xl shadow-2xl overflow-hidden z-50 py-1 divide-y divide-slate-800/80 backdrop-blur-md">
                    <div class="px-3 py-1.5 text-[10px] uppercase font-bold tracking-wider text-slate-400 flex items-center justify-between bg-slate-900/60">
                        <span>Mention Member</span>
                        <span class="text-rose-400 font-mono text-[11px]" x-show="mentionQuery" x-text="'@' + mentionQuery"></span>
                    </div>

                    <div class="max-h-52 overflow-y-auto py-1">
                        <template x-if="mentionSuggestions.length === 0">
                            <div class="px-3 py-3 text-xs text-slate-400 italic text-center">
                                No members found
                            </div>
                        </template>

                        <template x-for="(user, idx) in mentionSuggestions" :key="user.id">
                            <button type="button"
                                    @mousedown.prevent="selectMention(user, 'main')"
                                    @mouseenter="mentionHighlightedIndex = idx"
                                    class="w-full text-left px-3 py-2 flex items-center gap-2.5 transition text-xs cursor-pointer select-none"
                                    :class="mentionHighlightedIndex === idx ? 'bg-gradient-to-r from-[#560E0F] to-[#8F3033]/80 text-white' : 'text-slate-200 hover:bg-slate-800/80'">
                                <div class="w-7 h-7 rounded-full bg-[#560E0F] border border-[#8F3033]/60 flex items-center justify-center shrink-0 overflow-hidden text-[10px] font-bold text-white font-brand-display shadow-sm">
                                    <img :src="user.avatar" :alt="user.name" class="w-full h-full object-cover" x-show="user.avatar" x-on:error="$el.style.display='none'; $el.nextElementSibling.style.display='flex'">
                                    <span :style="user.avatar ? 'display: none;' : 'display: flex;'" x-text="user.initials"></span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="font-semibold truncate text-slate-100 flex items-center gap-1.5">
                                        <span x-text="user.name"></span>
                                        <span x-show="user.role && user.role !== 'student'" class="text-[9px] uppercase tracking-wider px-1.5 py-0.2 rounded bg-amber-500/20 text-amber-300 font-mono" x-text="user.role"></span>
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-mono truncate" x-text="'@' + user.handle"></div>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Submit Button: Visible Lucide Send SVG Icon + 'Send' text label -->
                <button type="button"
                        @click="submitMainComment()"
                        :disabled="submitting || !newCommentText.trim()"
                        aria-label="Send comment"
                        title="Send comment"
                        class="h-9 px-3.5 sm:px-4 shrink-0 rounded-xl bg-gradient-to-r from-[#560E0F] to-[#8F3033] hover:from-[#6d1416] hover:to-[#a2383c] border border-[#8F3033]/60 shadow-md shadow-[#560E0F]/40 flex items-center justify-center gap-1.5 text-white font-semibold text-xs sm:text-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#8F3033] focus-visible:ring-offset-[#0B1120] transition-all duration-150 disabled:opacity-40 disabled:cursor-not-allowed active:scale-95 cursor-pointer">
                    <!-- Lucide Send Icon -->
                    <svg x-show="!submitting" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                    <!-- Loading Spinner -->
                    <svg x-show="submitting" x-cloak class="animate-spin w-4 h-4 text-white shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span x-text="submitting ? 'Sending...' : 'Send'">Send</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Comments List & Threads -->
    <div class="space-y-4 pt-2">
        <template x-if="loadingComments">
            <div class="text-center py-8 text-xs text-slate-500">
                <i data-lucide="loader" class="w-5 h-5 animate-spin mx-auto mb-2 text-slate-400"></i>
                <span>Loading discussion...</span>
            </div>
        </template>

        <template x-if="!loadingComments && comments.length === 0">
            <div class="text-center py-8 px-4 rounded-xl border border-dashed border-slate-800 text-slate-500 text-xs">
                <i data-lucide="message-square-dashed" class="w-6 h-6 mx-auto mb-1.5 text-slate-600"></i>
                <p class="font-medium text-slate-400">No comments yet</p>
                <p class="mt-0.5">Start the conversation by leaving the first thought above!</p>
            </div>
        </template>

        <!-- Main Comments Loop -->
        <template x-for="comment in comments" :key="comment.id">
            <div class="p-4 rounded-xl bg-slate-900/40 border border-slate-800/80 space-y-3 transition"
                 :class="comment.status === 'hidden' ? 'opacity-60 bg-amber-950/10 border-amber-900/30' : ''">

                <!-- Comment Header -->
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <div class="relative w-8 h-8 rounded-full bg-[#560E0F]/40 border border-[#8F3033]/60 flex items-center justify-center shrink-0 overflow-hidden shadow-sm">
                            <img :src="comment.author.avatar"
                                 class="w-full h-full object-cover"
                                 :alt="comment.author.name"
                                 x-show="comment.author.avatar"
                                 x-on:error="$el.style.display='none'; $el.nextElementSibling.style.display='flex'">
                            <span class="w-full h-full flex items-center justify-center bg-[#560E0F] text-white text-[11px] font-bold font-brand-display"
                                  :style="comment.author.avatar ? 'display: none;' : 'display: flex;'"
                                  x-text="comment.author.initials"></span>
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-xs font-bold text-white" x-text="comment.author.name"></span>
                                <template x-if="comment.author.role === 'super_admin' || comment.author.role === 'admin' || comment.author.role === 'officer'">
                                    <span class="px-1.5 py-0.2 rounded text-[10px] bg-rose-500/20 text-rose-300 font-semibold border border-rose-500/30">Officer</span>
                                </template>
                                <template x-if="comment.status === 'hidden'">
                                    <span class="px-1.5 py-0.2 rounded text-[10px] bg-amber-500/20 text-amber-300 font-semibold border border-amber-500/30">Hidden by Moderator</span>
                                </template>
                            </div>
                            <div class="flex items-center gap-2 text-[11px] text-slate-500">
                                <span x-text="comment.created_at"></span>
                                <template x-if="comment.is_edited">
                                    <span class="italic text-[10px]">(edited)</span>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Dropdown / Action Menu -->
                    <div class="relative" x-data="{ menuOpen: false }" @click.away="menuOpen = false">
                        <button type="button"
                                @click="menuOpen = !menuOpen"
                                aria-label="Comment actions"
                                title="Comment options"
                                class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition focus:outline-none focus:ring-1 focus:ring-slate-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="1.5"></circle>
                                <circle cx="19" cy="12" r="1.5"></circle>
                                <circle cx="5" cy="12" r="1.5"></circle>
                            </svg>
                        </button>
                        <div x-show="menuOpen"
                             x-cloak
                             class="absolute right-0 mt-1 w-36 bg-[#1E293B] border border-slate-700 rounded-xl shadow-xl py-1 z-20 text-xs">
                            <template x-if="comment.can_edit">
                                <button type="button"
                                        @click="menuOpen = false; startEditing(comment)"
                                        class="w-full text-left px-3 py-1.5 text-slate-300 hover:bg-slate-700 hover:text-white flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path>
                                        <path d="m15 5 4 4"></path>
                                    </svg>
                                    <span>Edit</span>
                                </button>
                            </template>
                            <template x-if="comment.can_delete">
                                <button type="button"
                                        @click="menuOpen = false; openDeleteModal(comment)"
                                        class="w-full text-left px-3 py-1.5 text-rose-400 hover:bg-rose-950/30 flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-rose-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 6h18"></path>
                                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                        <line x1="10" x2="10" y1="11" y2="17"></line>
                                        <line x1="14" x2="14" y1="11" y2="17"></line>
                                    </svg>
                                    <span>Delete</span>
                                </button>
                            </template>
                            <template x-if="comment.can_report">
                                <button type="button"
                                        @click="menuOpen = false; openReportModal(comment)"
                                        class="w-full text-left px-3 py-1.5 text-amber-400 hover:bg-amber-950/30 flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                                        <line x1="4" x2="4" y1="22" y2="15"></line>
                                    </svg>
                                    <span>Report</span>
                                </button>
                            </template>
                            <template x-if="comment.can_moderate">
                                <div class="border-t border-slate-700/80 my-1"></div>
                                <button type="button"
                                        @click="menuOpen = false; moderateComment(comment, comment.status === 'hidden' ? 'unhide' : 'hide')"
                                        class="w-full text-left px-3 py-1.5 text-slate-300 hover:bg-slate-700 flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path>
                                    </svg>
                                    <span x-text="comment.status === 'hidden' ? 'Unhide' : 'Hide'"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Comment Content (or inline edit composer) -->
                <div>
                    <template x-if="editingCommentId !== comment.id">
                        <div class="text-xs sm:text-sm text-slate-300 whitespace-pre-line leading-relaxed pl-1"
                             x-html="renderFormattedContent(comment.content)"></div>
                    </template>
                    <template x-if="editingCommentId === comment.id">
                        <div class="relative space-y-2">
                            <textarea x-ref="'editInput_' + comment.id"
                                      x-model="editCommentText"
                                      @input="onInputMention($event, 'edit', comment.id); autoResizeTextarea($event.target)"
                                      @keydown="handleComposerKeydown($event, 'edit', comment)"
                                      rows="2"
                                      placeholder="Edit your comment..."
                                      class="w-full bg-slate-950 border border-slate-700 focus:border-[#8F3033] rounded-lg p-2.5 text-xs sm:text-sm text-slate-100 focus:outline-none"></textarea>

                            <!-- Mention Suggestions for Edit -->
                            <div x-show="mentionDropdownOpen && mentionContext === 'edit' && mentionActiveCommentId === comment.id"
                                 x-cloak
                                 @click.away="closeMentionDropdown()"
                                 class="absolute left-0 bottom-full mb-1 w-64 max-w-[calc(100vw-2rem)] bg-[#172033] border border-slate-700 rounded-xl shadow-2xl overflow-hidden z-40 py-1">
                                <div class="max-h-40 overflow-y-auto">
                                    <template x-for="(user, idx) in mentionSuggestions" :key="user.id">
                                        <button type="button"
                                                @mousedown.prevent="selectMention(user, 'edit', comment.id)"
                                                class="w-full text-left px-3 py-1.5 flex items-center gap-2 text-xs"
                                                :class="mentionHighlightedIndex === idx ? 'bg-[#560E0F]/60 text-white' : 'text-slate-300 hover:bg-slate-800'">
                                            <div class="w-5 h-5 rounded-full bg-[#560E0F] overflow-hidden shrink-0 flex items-center justify-center text-[8px] text-white">
                                                <img :src="user.avatar" class="w-full h-full object-cover" x-show="user.avatar">
                                                <span :style="user.avatar ? 'display: none;' : 'display: flex;'" x-text="user.initials"></span>
                                            </div>
                                            <span class="truncate" x-text="user.name"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 justify-end">
                                <button type="button" @click="cancelEditing()" :disabled="savingEdit" class="btn-secondary text-[11px] py-1 px-3">Cancel</button>
                                <button type="button" @click="saveEdit(comment)" :disabled="savingEdit || !editCommentText.trim()" class="btn-primary text-[11px] py-1 px-3 flex items-center gap-1.5">
                                    <svg x-show="savingEdit" x-cloak class="animate-spin w-3 h-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span x-text="savingEdit ? 'Saving...' : 'Save Changes'">Save Changes</span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Comment Actions: React, Reply button & Expand replies toggle -->
                <div class="flex items-center gap-3 pt-1 text-xs">
                    <!-- Comment React Button with Hover / Click / Long-Press Reaction Picker (with 250ms persistence bridge) -->
                    <div class="relative"
                         x-data="{ pickerOpen: false, closeTimer: null }"
                         @click.away="pickerOpen = false"
                         @mouseenter="if (closeTimer) { clearTimeout(closeTimer); closeTimer = null; } pickerOpen = true"
                         @mouseleave="closeTimer = setTimeout(() => { pickerOpen = false; }, 250)">
                        <button type="button"
                                @click="handleReactClick($event, () => pickerOpen = !pickerOpen)"
                                @touchstart.passive="startLongPress($event, () => pickerOpen = true)"
                                @touchmove.passive="moveLongPress($event)"
                                @touchend.passive="cancelLongPress()"
                                @touchcancel.passive="cancelLongPress()"
                                class="font-semibold flex items-center gap-1.5 transition rounded px-1.5 py-0.5 hover:bg-slate-800 select-none cursor-pointer"
                                :class="comment.user_reaction ? 'text-rose-400 hover:text-rose-300' : 'text-slate-400 hover:text-white'">
                            <span x-text="getCommentReactionDisplay(comment).emoji"></span>
                            <span x-text="getCommentReactionDisplay(comment).label"></span>
                            <template x-if="comment.total_reactions > 0">
                                <span class="text-[11px] font-bold text-slate-300 ml-0.5" x-text="comment.total_reactions"></span>
                            </template>
                        </button>

                        <!-- Reaction Picker Dropdown - Clean Icons Only -->
                        <div x-show="pickerOpen"
                             x-cloak
                             @mouseenter="if (closeTimer) { clearTimeout(closeTimer); closeTimer = null; }"
                             x-transition:enter="transition ease-out duration-150 transform"
                             x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                             x-transition:leave="transition ease-in duration-100 transform"
                             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                             x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                             class="absolute left-0 bottom-full mb-1.5 z-30 p-1.5 bg-[#1E293B] border border-slate-700/80 rounded-2xl shadow-2xl flex items-center gap-1 backdrop-blur-md">
                            <!-- Invisible bridge preventing cursor drop between button and popup -->
                            <div class="absolute left-0 -bottom-2 w-full h-2"></div>

                            <template x-for="r in commentReactionTypes" :key="r.key">
                                <button type="button"
                                        @click="pickerOpen = false; toggleCommentReaction(comment, r.key)"
                                        class="p-1.5 sm:px-2 rounded-xl hover:bg-slate-700/60 transition-transform active:scale-90 hover:scale-125 flex items-center justify-center group focus:outline-none cursor-pointer select-none"
                                        :class="comment.user_reaction === r.key ? 'bg-rose-500/20 ring-1 ring-rose-500/50' : ''">
                                    <span class="text-xl leading-none" x-text="r.emoji"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Reply Action Button -->
                    <button type="button"
                            @click="openReplyBox(comment)"
                            class="font-semibold text-slate-400 hover:text-white flex items-center gap-1.5 transition">
                        <i data-lucide="reply" class="w-3.5 h-3.5"></i>
                        <span>Reply</span>
                    </button>

                    <!-- Expand / Collapse Thread Button -->
                    <template x-if="comment.replies_count > 0">
                        <button type="button"
                                @click="toggleThread(comment.id)"
                                class="text-rose-400 hover:text-rose-300 font-semibold flex items-center gap-1 transition">
                            <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform"
                               :class="expandedThreads[comment.id] ? 'rotate-180' : ''"></i>
                            <span x-text="expandedThreads[comment.id] ? 'Hide Replies' : ('View ' + comment.replies_count + (comment.replies_count === 1 ? ' Reply' : ' Replies'))"></span>
                        </button>
                    </template>
                </div>

                <!-- Threaded Replies Section (Expandable) -->
                <div x-show="expandedThreads[comment.id]"
                     x-cloak
                     class="mt-3 pl-4 sm:pl-6 border-l-2 border-slate-800 space-y-3">

                    <template x-for="reply in comment.replies" :key="reply.id">
                        <div class="p-3 rounded-lg bg-slate-900/60 border border-slate-800/60 space-y-2"
                             :class="reply.status === 'hidden' ? 'opacity-60 bg-amber-950/10' : ''">

                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <div class="relative w-6 h-6 rounded-full bg-[#560E0F]/40 border border-[#8F3033]/60 flex items-center justify-center shrink-0 overflow-hidden shadow-xs">
                                        <img :src="reply.author.avatar"
                                             class="w-full h-full object-cover"
                                             :alt="reply.author.name"
                                             x-show="reply.author.avatar"
                                             x-on:error="$el.style.display='none'; $el.nextElementSibling.style.display='flex'">
                                        <span class="w-full h-full flex items-center justify-center bg-[#560E0F] text-white text-[9px] font-bold font-brand-display"
                                              :style="reply.author.avatar ? 'display: none;' : 'display: flex;'"
                                              x-text="reply.author.initials"></span>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="text-xs font-bold text-white" x-text="reply.author.name"></span>
                                            <template x-if="reply.status === 'hidden'">
                                                <span class="px-1.5 py-0.2 rounded text-[9px] bg-amber-500/20 text-amber-300 font-semibold">Hidden</span>
                                            </template>
                                        </div>
                                        <div class="flex items-center gap-2 text-[10px] text-slate-500">
                                            <span x-text="reply.created_at"></span>
                                            <template x-if="reply.is_edited">
                                                <span class="italic">(edited)</span>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- Reply Action Menu -->
                                <div class="relative" x-data="{ replyMenu: false }" @click.away="replyMenu = false">
                                    <button type="button"
                                            @click="replyMenu = !replyMenu"
                                            aria-label="Reply actions"
                                            title="Reply options"
                                            class="w-6 h-6 flex items-center justify-center rounded text-slate-500 hover:text-white hover:bg-slate-800 transition focus:outline-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="1.5"></circle>
                                            <circle cx="19" cy="12" r="1.5"></circle>
                                            <circle cx="5" cy="12" r="1.5"></circle>
                                        </svg>
                                    </button>
                                    <div x-show="replyMenu"
                                         x-cloak
                                         class="absolute right-0 mt-1 w-32 bg-[#1E293B] border border-slate-700 rounded-lg shadow-xl py-1 z-20 text-xs">
                                        <template x-if="reply.can_edit">
                                            <button type="button"
                                                    @click="replyMenu = false; startEditing(reply)"
                                                    class="w-full text-left px-3 py-1 text-slate-300 hover:bg-slate-700 hover:text-white flex items-center gap-1.5">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"></path>
                                                    <path d="m15 5 4 4"></path>
                                                </svg>
                                                <span>Edit</span>
                                            </button>
                                        </template>
                                        <template x-if="reply.can_delete">
                                            <button type="button"
                                                    @click="replyMenu = false; openDeleteModal(reply, comment)"
                                                    class="w-full text-left px-3 py-1 text-rose-400 hover:bg-rose-950/30 flex items-center gap-1.5">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-rose-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M3 6h18"></path>
                                                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                                                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                                    <line x1="10" x2="10" y1="11" y2="17"></line>
                                                    <line x1="14" x2="14" y1="11" y2="17"></line>
                                                </svg>
                                                <span>Delete</span>
                                            </button>
                                        </template>
                                        <template x-if="reply.can_report">
                                            <button type="button"
                                                    @click="replyMenu = false; openReportModal(reply)"
                                                    class="w-full text-left px-3 py-1 text-amber-400 hover:bg-amber-950/30 flex items-center gap-1.5">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                                                    <line x1="4" x2="4" y1="22" y2="15"></line>
                                                </svg>
                                                <span>Report</span>
                                            </button>
                                        </template>
                                        <template x-if="reply.can_moderate">
                                            <button type="button"
                                                    @click="replyMenu = false; moderateComment(reply, reply.status === 'hidden' ? 'unhide' : 'hide', comment)"
                                                    class="w-full text-left px-3 py-1 text-slate-300 hover:bg-slate-700 flex items-center gap-1.5">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path>
                                                </svg>
                                                <span x-text="reply.status === 'hidden' ? 'Unhide' : 'Hide'"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Reply Content (or edit mode) -->
                            <div>
                                <template x-if="editingCommentId !== reply.id">
                                    <div class="text-xs text-slate-300 whitespace-pre-line leading-relaxed"
                                         x-html="renderFormattedContent(reply.content)"></div>
                                </template>
                                <template x-if="editingCommentId === reply.id">
                                    <div class="relative space-y-2">
                                        <textarea x-ref="'editInput_' + reply.id"
                                                  x-model="editCommentText"
                                                  @input="onInputMention($event, 'edit', reply.id); autoResizeTextarea($event.target)"
                                                  @keydown="handleComposerKeydown($event, 'edit', reply)"
                                                  rows="2"
                                                  placeholder="Edit your reply..."
                                                  class="w-full bg-slate-950 border border-slate-700 focus:border-[#8F3033] rounded-lg p-2 text-xs text-slate-100 focus:outline-none"></textarea>

                                        <!-- Mention Suggestions for Edit Reply -->
                                        <div x-show="mentionDropdownOpen && mentionContext === 'edit' && mentionActiveCommentId === reply.id"
                                             x-cloak
                                             @click.away="closeMentionDropdown()"
                                             class="absolute left-0 bottom-full mb-1 w-64 max-w-[calc(100vw-2rem)] bg-[#172033] border border-slate-700 rounded-xl shadow-2xl overflow-hidden z-40 py-1">
                                            <div class="max-h-40 overflow-y-auto">
                                                <template x-for="(user, idx) in mentionSuggestions" :key="user.id">
                                                    <button type="button"
                                                            @mousedown.prevent="selectMention(user, 'edit', reply.id)"
                                                            class="w-full text-left px-3 py-1.5 flex items-center gap-2 text-xs"
                                                            :class="mentionHighlightedIndex === idx ? 'bg-[#560E0F]/60 text-white' : 'text-slate-300 hover:bg-slate-800'">
                                                        <div class="w-5 h-5 rounded-full bg-[#560E0F] overflow-hidden shrink-0 flex items-center justify-center text-[8px] text-white">
                                                            <img :src="user.avatar" class="w-full h-full object-cover" x-show="user.avatar">
                                                            <span :style="user.avatar ? 'display: none;' : 'display: flex;'" x-text="user.initials"></span>
                                                        </div>
                                                        <span class="truncate" x-text="user.name"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2 justify-end">
                                            <button type="button" @click="cancelEditing()" :disabled="savingEdit" class="btn-secondary text-[10px] py-1 px-2.5">Cancel</button>
                                            <button type="button" @click="saveEdit(reply, comment)" :disabled="savingEdit || !editCommentText.trim()" class="btn-primary text-[10px] py-1 px-2.5 flex items-center gap-1.5">
                                                <svg x-show="savingEdit" x-cloak class="animate-spin w-3 h-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                                </svg>
                                                <span x-text="savingEdit ? 'Saving...' : 'Save Changes'">Save Changes</span>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                                <!-- Reply Actions: React & Action menu (with 250ms persistence bridge) -->
                                <div class="flex items-center gap-2 pt-1 text-[11px]">
                                    <div class="relative"
                                         x-data="{ replyPickerOpen: false, closeTimer: null }"
                                         @click.away="replyPickerOpen = false"
                                         @mouseenter="if (closeTimer) { clearTimeout(closeTimer); closeTimer = null; } replyPickerOpen = true"
                                         @mouseleave="closeTimer = setTimeout(() => { replyPickerOpen = false; }, 250)">
                                        <button type="button"
                                                @click="handleReactClick($event, () => replyPickerOpen = !replyPickerOpen)"
                                                @touchstart.passive="startLongPress($event, () => replyPickerOpen = true)"
                                                @touchmove.passive="moveLongPress($event)"
                                                @touchend.passive="cancelLongPress()"
                                                @touchcancel.passive="cancelLongPress()"
                                                class="font-semibold flex items-center gap-1 transition rounded px-1.5 py-0.5 hover:bg-slate-800 select-none cursor-pointer"
                                                :class="reply.user_reaction ? 'text-rose-400 hover:text-rose-300' : 'text-slate-400 hover:text-white'">
                                            <span x-text="getCommentReactionDisplay(reply).emoji"></span>
                                            <span x-text="getCommentReactionDisplay(reply).label"></span>
                                            <template x-if="reply.total_reactions > 0">
                                                <span class="text-[10px] font-bold text-slate-300 ml-0.5" x-text="reply.total_reactions"></span>
                                            </template>
                                        </button>

                                        <!-- Reply Reaction Picker - Clean Icons Only -->
                                        <div x-show="replyPickerOpen"
                                             x-cloak
                                             @mouseenter="if (closeTimer) { clearTimeout(closeTimer); closeTimer = null; }"
                                             x-transition:enter="transition ease-out duration-150 transform"
                                             x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                                             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                             x-transition:leave="transition ease-in duration-100 transform"
                                             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                             x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                                             class="absolute left-0 bottom-full mb-1.5 z-30 p-1.5 bg-[#1E293B] border border-slate-700/80 rounded-2xl shadow-2xl flex items-center gap-1 backdrop-blur-md">
                                            <!-- Invisible bridge preventing cursor drop between button and popup -->
                                            <div class="absolute left-0 -bottom-2 w-full h-2"></div>

                                            <template x-for="r in commentReactionTypes" :key="r.key">
                                                <button type="button"
                                                        @click="replyPickerOpen = false; toggleCommentReaction(reply, r.key)"
                                                        class="p-1.5 sm:px-2 rounded-xl hover:bg-slate-700/60 transition-transform active:scale-90 hover:scale-125 flex items-center justify-center group focus:outline-none cursor-pointer select-none"
                                                        :class="reply.user_reaction === r.key ? 'bg-rose-500/20 ring-1 ring-rose-500/50' : ''">
                                                    <span class="text-xl leading-none" x-text="r.emoji"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Reply to user trigger (tags parent) -->
                                    <button type="button"
                                            @click="openReplyBox(comment, reply)"
                                            class="font-semibold text-slate-400 hover:text-white flex items-center gap-1 transition">
                                        <i data-lucide="reply" class="w-3 h-3"></i>
                                        <span>Reply</span>
                                    </button>
                                </div>
                            </div>

                        </div>
                    </template>
                </div>

                <!-- Dedicated Inline Reply Composer for this parent comment -->
                <div x-show="activeReplyParentId === comment.id"
                     x-cloak
                     class="mt-3 pl-4 sm:pl-6 border-l-2 border-[#8F3033] space-y-2">
                    <div class="text-[11px] text-slate-400 flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-[#df747a]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 10 20 15 15 20"/>
                            <path d="M4 4v7a4 4 0 0 0 4 4h12"/>
                        </svg>
                        <span>Replying to <strong class="text-white" x-text="comment.author.name"></strong></span>
                    </div>

                    <!-- Cohesive Reply Input Card: [USER AVATAR] [ Write a reply... ] [Send] -->
                    <div class="relative flex items-center gap-2 rounded-xl bg-[#0B1120] border border-[#334155] focus-within:border-[#8F3033] focus-within:ring-2 focus-within:ring-[#8F3033]/40 transition p-1.5 pl-3 shadow-inner">
                        <!-- Auto-expanding Textarea -->
                        <textarea x-ref="'replyInput_' + comment.id"
                                  x-model="replyText"
                                  @input="onInputMention($event, 'reply', comment.id); autoResizeTextarea($event.target)"
                                  @keydown="handleComposerKeydown($event, 'reply', comment)"
                                  rows="1"
                                  maxlength="2000"
                                  placeholder="Write a reply..."
                                  class="flex-1 min-w-0 bg-transparent border-0 p-0 py-1 text-xs text-slate-100 placeholder-slate-400 focus:ring-0 focus:outline-none resize-none leading-relaxed min-h-[34px] max-h-28 overflow-y-auto"></textarea>

                        <!-- Mention Suggestion Dropdown (Reply Composer) -->
                        <div x-show="mentionDropdownOpen && mentionContext === 'reply' && mentionActiveCommentId === comment.id"
                             x-cloak
                             @click.away="closeMentionDropdown()"
                             class="absolute left-0 bottom-full mb-1 w-72 max-w-[calc(100vw-2rem)] bg-[#0B1120] border border-[#334155] rounded-xl shadow-2xl overflow-hidden z-50 py-1 divide-y divide-slate-800/80">
                            <div class="px-2.5 py-1 text-[9px] uppercase font-bold tracking-wider text-slate-400 flex items-center justify-between bg-slate-900/60">
                                <span>Mention Member</span>
                                <span class="text-rose-400 font-mono text-[10px]" x-show="mentionQuery" x-text="'@' + mentionQuery"></span>
                            </div>
                            <div class="max-h-44 overflow-y-auto py-0.5">
                                <template x-if="mentionSuggestions.length === 0">
                                    <div class="px-3 py-2 text-xs text-slate-400 italic text-center">
                                        No members found
                                    </div>
                                </template>
                                <template x-for="(user, idx) in mentionSuggestions" :key="user.id">
                                    <button type="button"
                                            @mousedown.prevent="selectMention(user, 'reply', comment.id)"
                                            @mouseenter="mentionHighlightedIndex = idx"
                                            class="w-full text-left px-2.5 py-1.5 flex items-center gap-2 transition text-xs cursor-pointer select-none"
                                            :class="mentionHighlightedIndex === idx ? 'bg-gradient-to-r from-[#560E0F] to-[#8F3033]/80 text-white' : 'text-slate-200 hover:bg-slate-800/80'">
                                        <div class="w-6 h-6 rounded-full bg-[#560E0F] border border-[#8F3033]/60 flex items-center justify-center shrink-0 overflow-hidden text-[9px] font-bold text-white">
                                            <img :src="user.avatar" class="w-full h-full object-cover" x-show="user.avatar">
                                            <span :style="user.avatar ? 'display: none;' : 'display: flex;'" x-text="user.initials"></span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="font-semibold truncate text-[11px] text-slate-100 flex items-center gap-1">
                                                <span x-text="user.name"></span>
                                                <span x-show="user.role && user.role !== 'student'" class="text-[8px] uppercase tracking-wider px-1 rounded bg-amber-500/20 text-amber-300 font-mono" x-text="user.role"></span>
                                            </div>
                                            <div class="text-[9px] text-slate-400 font-mono truncate" x-text="'@' + user.handle"></div>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-1.5 shrink-0">
                            <button type="button"
                                    @click="cancelReply()"
                                    class="px-2 py-1 rounded-lg text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
                                Cancel
                            </button>
                            <button type="button"
                                    @click="submitReply(comment)"
                                    :disabled="submittingReply || !replyText.trim()"
                                    aria-label="Send reply"
                                    title="Send reply"
                                    class="h-8 px-3 shrink-0 rounded-lg bg-gradient-to-r from-[#560E0F] to-[#8F3033] hover:from-[#6d1416] hover:to-[#a2383c] border border-[#8F3033]/60 shadow flex items-center justify-center gap-1 text-white font-semibold text-xs focus:outline-none focus-visible:ring-2 focus-visible:ring-[#8F3033] transition-all disabled:opacity-40 disabled:cursor-not-allowed active:scale-95 cursor-pointer">
                                <svg x-show="!submittingReply" xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-white shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="22" y1="2" x2="11" y2="13"></line>
                                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                </svg>
                                <svg x-show="submittingReply" x-cloak class="animate-spin w-3.5 h-3.5 text-white shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span x-text="submittingReply ? 'Sending...' : 'Reply'">Reply</span>
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </template>
    </div>

    <!-- Modal: View Who Reacted / Reaction Breakdown -->
    <div x-show="showReactorsModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
         @keydown.escape.window="showReactorsModal = false">
        <div class="bg-[#172033] border border-slate-700 rounded-2xl max-w-md w-full shadow-2xl overflow-hidden"
             @click.away="showReactorsModal = false">
            <div class="p-4 border-b border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-base font-bold text-white font-brand-display">Reactions</span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 font-semibold" x-text="totalReactions"></span>
                </div>
                <button type="button" @click="showReactorsModal = false" class="text-slate-400 hover:text-white p-1 rounded-lg">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Breakdown tabs / counts -->
            <div class="p-4 grid grid-cols-3 gap-2 bg-slate-900/60 border-b border-slate-800/80">
                <template x-for="r in breakdown" :key="r.type">
                    <div class="p-2 rounded-xl bg-slate-800/50 border border-slate-700/60 flex items-center gap-2">
                        <span class="text-lg leading-none" x-text="r.emoji"></span>
                        <div class="min-w-0">
                            <span class="block text-[10px] text-slate-400 truncate" x-text="r.label"></span>
                            <span class="block text-xs font-bold text-white" x-text="r.count"></span>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Recent Reactors List -->
            <div class="p-4 max-h-60 overflow-y-auto space-y-2.5">
                <template x-if="recentReactors.length === 0">
                    <p class="text-xs text-slate-500 text-center py-4">No reactors yet.</p>
                </template>
                <template x-for="reactor in recentReactors" :key="reactor.user_id + '_' + reactor.reaction_type">
                    <div class="flex items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-base leading-none" x-text="reactor.emoji"></span>
                            <span class="text-slate-200 font-medium truncate" x-text="reactor.name"></span>
                        </div>
                        <span class="text-[11px] text-slate-400" x-text="reactor.label"></span>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Modal: Report Comment -->
    <div x-show="showReportModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
         @keydown.escape.window="showReportModal = false">
        <div class="bg-[#172033] border border-slate-700 rounded-2xl max-w-md w-full shadow-2xl p-5 space-y-4"
             @click.away="showReportModal = false">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-bold text-white font-brand-display flex items-center gap-2">
                    <i data-lucide="flag" class="w-4 h-4 text-amber-400"></i>
                    <span>Report Comment</span>
                </h3>
                <button type="button" @click="showReportModal = false" class="text-slate-400 hover:text-white">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <p class="text-xs text-slate-300">
                Help keep the COMSOC Hub community respectful. Select why you are reporting this content:
            </p>

            <div class="space-y-2">
                <label class="block text-xs font-semibold text-slate-300">Reason</label>
                <select x-model="reportReason" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-slate-200 focus:border-rose-500 focus:outline-none">
                    <option value="harassment">Harassment or Hate Speech</option>
                    <option value="spam">Spam or Misleading Content</option>
                    <option value="inappropriate">Inappropriate or Offensive Material</option>
                    <option value="academic_dishonesty">Academic Dishonesty or Cheating</option>
                    <option value="other">Other Violation</option>
                </select>
            </div>

            <div class="space-y-2">
                <label class="block text-xs font-semibold text-slate-300">Additional Details (Optional)</label>
                <textarea x-model="reportDetails" rows="3" placeholder="Provide extra context if helpful..." class="w-full bg-slate-900 border border-slate-700 rounded-xl p-2.5 text-xs text-slate-200 focus:border-rose-500 focus:outline-none"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" @click="showReportModal = false" class="btn-secondary text-xs py-1.5 px-3">Cancel</button>
                <button type="button" @click="submitReport()" :disabled="submittingReport" class="btn-primary text-xs py-1.5 px-4 bg-amber-600 hover:bg-amber-500 border-amber-600">Submit Report</button>
            </div>
        </div>
    </div>

    <!-- Modal: Delete Comment Confirmation Dialog -->
    <div x-show="showDeleteModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm"
         @keydown.escape.window="closeDeleteModal()">
        <div class="bg-[#172033] border border-rose-900/40 rounded-2xl max-w-sm w-full shadow-2xl p-5 space-y-4 animate-in fade-in zoom-in-95 duration-150"
             @click.away="closeDeleteModal()">
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-full bg-rose-500/20 border border-rose-500/40 flex items-center justify-center shrink-0 text-rose-400">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white font-brand-display">Delete Comment?</h3>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                        Are you sure you want to delete this comment? This action cannot be undone.
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-2">
                <button type="button"
                        @click="closeDeleteModal()"
                        :disabled="isDeleting"
                        class="btn-secondary text-xs py-1.5 px-3.5">
                    Cancel
                </button>
                <button type="button"
                        @click="confirmDelete()"
                        :disabled="isDeleting"
                        class="h-8 px-4 rounded-xl bg-gradient-to-r from-[#560E0F] to-[#8F3033] hover:from-[#6d1416] hover:to-[#a2383c] border border-[#8F3033]/60 text-white font-semibold text-xs shadow-md shadow-[#560E0F]/40 flex items-center gap-1.5 transition disabled:opacity-50 active:scale-95 cursor-pointer">
                    <svg x-show="isDeleting" x-cloak class="animate-spin w-3.5 h-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span x-text="isDeleting ? 'Deleting...' : 'Delete Comment'">Delete Comment</span>
                </button>
            </div>
        </div>
    </div>

</div>

