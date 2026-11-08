@props(['announcement', 'reactionsConfig'])

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

    <!-- Engagement Summary Bar & Reaction Breakdown -->
    <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-slate-400">
        <!-- Reaction icons + count preview -->
        <div class="flex items-center gap-2">
            <template x-if="totalReactions > 0">
                <button type="button"
                        @click="showReactorsModal = true"
                        class="flex items-center gap-1.5 hover:text-white transition group focus:outline-none focus:ring-1 focus:ring-rose-500/50 rounded-full px-2 py-1 bg-slate-900/60 border border-slate-800">
                    <span class="flex items-center -space-x-1">
                        <template x-for="r in topReactions" :key="r.type">
                            <span class="inline-flex items-center justify-center w-5 h-5 text-sm rounded-full bg-slate-800 border border-slate-700 shadow-sm"
                                  :title="r.label + ': ' + r.count"
                                  x-text="r.emoji"></span>
                        </template>
                    </span>
                    <span class="font-bold text-slate-300 ml-1" x-text="totalReactions"></span>
                    <span class="text-slate-500 group-hover:text-slate-400">reactions</span>
                </button>
            </template>
            <template x-if="totalReactions === 0">
                <span class="text-slate-500 italic">Be the first to react</span>
            </template>
        </div>

        <!-- Comments & Replies Total Count -->
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-1.5">
                <i data-lucide="message-square" class="w-3.5 h-3.5 text-slate-500"></i>
                <span class="font-medium text-slate-300" x-text="totalComments"></span>
                <span>comments</span>
            </span>
        </div>
    </div>

    <!-- Engagement Action Buttons: Reaction Trigger & Quick Scroll to Comment -->
    <div class="relative flex items-center gap-2 py-2 border-y border-slate-800/80 bg-slate-900/30 rounded-xl px-2 sm:px-4">
        <!-- React Button Container with Hover / Click Picker -->
        <div class="relative" @click.away="pickerOpen = false">
            <button type="button"
                    @click="pickerOpen = !pickerOpen"
                    @mouseenter="pickerOpen = true"
                    class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold transition-all duration-150"
                    :class="userReaction
                        ? 'bg-rose-500/10 text-rose-300 border border-rose-500/30'
                        : 'text-slate-300 hover:bg-slate-800/70 hover:text-white'">
                <template x-if="userReaction">
                    <span class="flex items-center gap-1.5">
                        <span class="text-base" x-text="breakdown[userReaction]?.emoji"></span>
                        <span class="capitalize" x-text="breakdown[userReaction]?.label"></span>
                    </span>
                </template>
                <template x-if="!userReaction">
                    <span class="flex items-center gap-1.5">
                        <i data-lucide="smile-plus" class="w-4 h-4 text-slate-400"></i>
                        <span>React</span>
                    </span>
                </template>
            </button>

            <!-- Interactive Reaction Picker (6 reactions: Like, Love, Haha, Wow, Celebrate, Support) -->
            <div x-show="pickerOpen"
                 x-cloak
                 x-transition:enter="transition ease-out duration-150 transform"
                 x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-100 transform"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                 class="absolute left-0 bottom-full mb-2 z-30 p-1.5 bg-[#1E293B] border border-slate-700/80 rounded-2xl shadow-2xl flex items-center gap-1 backdrop-blur-md">

                <button type="button"
                        @click="toggleReaction('like')"
                        title="Like — General appreciation or acknowledgment"
                        class="p-2 sm:px-2.5 sm:py-1.5 rounded-xl hover:bg-slate-700/60 transition-transform active:scale-90 hover:scale-125 flex flex-col items-center gap-0.5 group focus:outline-none"
                        :class="userReaction === 'like' ? 'bg-rose-500/20 ring-1 ring-rose-500/50' : ''">
                    <span class="text-2xl leading-none">👍</span>
                    <span class="text-[9px] font-bold text-slate-400 group-hover:text-white hidden sm:block">Like</span>
                </button>

                <button type="button"
                        @click="toggleReaction('love')"
                        title="Love — Strong appreciation or support"
                        class="p-2 sm:px-2.5 sm:py-1.5 rounded-xl hover:bg-slate-700/60 transition-transform active:scale-90 hover:scale-125 flex flex-col items-center gap-0.5 group focus:outline-none"
                        :class="userReaction === 'love' ? 'bg-rose-500/20 ring-1 ring-rose-500/50' : ''">
                    <span class="text-2xl leading-none">❤️</span>
                    <span class="text-[9px] font-bold text-slate-400 group-hover:text-white hidden sm:block">Love</span>
                </button>

                <button type="button"
                        @click="toggleReaction('haha')"
                        title="Haha — Humor or amusement"
                        class="p-2 sm:px-2.5 sm:py-1.5 rounded-xl hover:bg-slate-700/60 transition-transform active:scale-90 hover:scale-125 flex flex-col items-center gap-0.5 group focus:outline-none"
                        :class="userReaction === 'haha' ? 'bg-rose-500/20 ring-1 ring-rose-500/50' : ''">
                    <span class="text-2xl leading-none">😂</span>
                    <span class="text-[9px] font-bold text-slate-400 group-hover:text-white hidden sm:block">Haha</span>
                </button>

                <button type="button"
                        @click="toggleReaction('wow')"
                        title="Wow — Surprise or excitement"
                        class="p-2 sm:px-2.5 sm:py-1.5 rounded-xl hover:bg-slate-700/60 transition-transform active:scale-90 hover:scale-125 flex flex-col items-center gap-0.5 group focus:outline-none"
                        :class="userReaction === 'wow' ? 'bg-rose-500/20 ring-1 ring-rose-500/50' : ''">
                    <span class="text-2xl leading-none">😮</span>
                    <span class="text-[9px] font-bold text-slate-400 group-hover:text-white hidden sm:block">Wow</span>
                </button>

                <button type="button"
                        @click="toggleReaction('celebrate')"
                        title="Celebrate — Congratulations or celebration"
                        class="p-2 sm:px-2.5 sm:py-1.5 rounded-xl hover:bg-slate-700/60 transition-transform active:scale-90 hover:scale-125 flex flex-col items-center gap-0.5 group focus:outline-none"
                        :class="userReaction === 'celebrate' ? 'bg-rose-500/20 ring-1 ring-rose-500/50' : ''">
                    <span class="text-2xl leading-none">🎉</span>
                    <span class="text-[9px] font-bold text-slate-400 group-hover:text-white hidden sm:block">Celebrate</span>
                </button>

                <button type="button"
                        @click="toggleReaction('support')"
                        title="Support — Gratitude or encouragement"
                        class="p-2 sm:px-2.5 sm:py-1.5 rounded-xl hover:bg-slate-700/60 transition-transform active:scale-90 hover:scale-125 flex flex-col items-center gap-0.5 group focus:outline-none"
                        :class="userReaction === 'support' ? 'bg-rose-500/20 ring-1 ring-rose-500/50' : ''">
                    <span class="text-2xl leading-none">🙏</span>
                    <span class="text-[9px] font-bold text-slate-400 group-hover:text-white hidden sm:block">Support</span>
                </button>
            </div>
        </div>

        <!-- Jump to Comment Button -->
        <button type="button"
                @click="focusMainCommentBox()"
                class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold text-slate-300 hover:bg-slate-800/70 hover:text-white transition">
            <i data-lucide="message-circle" class="w-4 h-4 text-slate-400"></i>
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

        <div class="flex items-start gap-3">
            <!-- Student Avatar (Server-rendered using COMSOC Hub Avatar System) -->
            <x-user-avatar :user="$user" size="md" class="w-10 h-10 rounded-full border border-[#8F3033]/60 shadow-md mt-0.5" />

            <!-- Cohesive Comment Input & Submit Action Card -->
            <div class="flex-1 rounded-2xl bg-slate-900/90 border border-slate-700/80 focus-within:border-[#8F3033] focus-within:ring-1 focus-within:ring-[#8F3033]/50 transition shadow-inner">
                <div class="p-2.5 sm:p-3">
                    <textarea x-ref="mainCommentInput"
                              x-model="newCommentText"
                              rows="2"
                              maxlength="2000"
                              placeholder="Write a comment or share your thoughts... (use @name to mention someone)"
                              class="w-full bg-transparent border-0 p-0 text-xs sm:text-sm text-slate-200 placeholder-slate-500 focus:ring-0 focus:outline-none resize-none leading-relaxed"></textarea>
                </div>

                <div class="flex items-center justify-between px-3 py-1.5 border-t border-slate-800/80 bg-slate-950/40 rounded-b-2xl">
                    <span class="text-[11px] text-slate-500 font-mono" x-text="newCommentText.length + '/2000'"></span>

                    <!-- Fixed Dimension, Non-Shrinking Send Button (40x40px, Maroon Gradient, Direct SVG Send Icon) -->
                    <button type="button"
                            @click="submitMainComment()"
                            :disabled="submitting || !newCommentText.trim()"
                            aria-label="Send comment"
                            title="Send comment"
                            class="w-9 h-9 min-w-[36px] min-h-[36px] sm:w-10 sm:h-10 sm:min-w-[40px] sm:min-h-[40px] shrink-0 rounded-xl bg-gradient-to-r from-[#560E0F] to-[#8F3033] hover:from-[#6d1416] hover:to-[#a2383c] border border-[#8F3033]/60 shadow-md shadow-[#560E0F]/40 flex items-center justify-center text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-[#8F3033] focus-visible:ring-offset-[#0B1120] transition-all duration-150 disabled:opacity-40 disabled:cursor-not-allowed active:scale-95">
                        <svg x-show="!submitting" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 sm:w-5 sm:h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m22 2-7 20-4-9-9-4Z"/>
                            <path d="M22 2 11 13"/>
                        </svg>
                        <svg x-show="submitting" x-cloak class="animate-spin w-4 h-4 sm:w-5 sm:h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                    </button>
                </div>
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
                                class="p-1 rounded text-slate-500 hover:text-slate-300 hover:bg-slate-800 transition focus:outline-none">
                            <i data-lucide="more-horizontal" class="w-4 h-4"></i>
                        </button>
                        <div x-show="menuOpen"
                             x-cloak
                             class="absolute right-0 mt-1 w-36 bg-[#1E293B] border border-slate-700 rounded-xl shadow-xl py-1 z-20 text-xs">
                            <template x-if="comment.can_edit">
                                <button type="button"
                                        @click="menuOpen = false; startEditing(comment)"
                                        class="w-full text-left px-3 py-1.5 text-slate-300 hover:bg-slate-700 hover:text-white flex items-center gap-2">
                                    <i data-lucide="edit-3" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span>Edit</span>
                                </button>
                            </template>
                            <template x-if="comment.can_delete">
                                <button type="button"
                                        @click="menuOpen = false; deleteComment(comment)"
                                        class="w-full text-left px-3 py-1.5 text-rose-400 hover:bg-rose-950/30 flex items-center gap-2">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    <span>Delete</span>
                                </button>
                            </template>
                            <template x-if="comment.can_report">
                                <button type="button"
                                        @click="menuOpen = false; openReportModal(comment)"
                                        class="w-full text-left px-3 py-1.5 text-amber-400 hover:bg-amber-950/30 flex items-center gap-2">
                                    <i data-lucide="flag" class="w-3.5 h-3.5"></i>
                                    <span>Report</span>
                                </button>
                            </template>
                            <template x-if="comment.can_moderate">
                                <div class="border-t border-slate-700/80 my-1"></div>
                                <button type="button"
                                        @click="menuOpen = false; moderateComment(comment, comment.status === 'hidden' ? 'unhide' : 'hide')"
                                        class="w-full text-left px-3 py-1.5 text-slate-300 hover:bg-slate-700 flex items-center gap-2">
                                    <i data-lucide="shield" class="w-3.5 h-3.5 text-slate-400"></i>
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
                             x-text="comment.content"></div>
                    </template>
                    <template x-if="editingCommentId === comment.id">
                        <div class="space-y-2">
                            <textarea x-model="editCommentText"
                                      rows="2"
                                      class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2.5 text-xs text-slate-200 focus:border-rose-500 focus:outline-none"></textarea>
                            <div class="flex items-center gap-2 justify-end">
                                <button type="button" @click="cancelEditing()" class="btn-secondary text-[11px] py-1 px-3">Cancel</button>
                                <button type="button" @click="saveEdit(comment)" class="btn-primary text-[11px] py-1 px-3">Save</button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Comment Actions: Reply button & Expand replies toggle -->
                <div class="flex items-center gap-3 pt-1 text-xs">
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
                                            class="p-0.5 rounded text-slate-500 hover:text-slate-300 transition">
                                        <i data-lucide="more-horizontal" class="w-3.5 h-3.5"></i>
                                    </button>
                                    <div x-show="replyMenu"
                                         x-cloak
                                         class="absolute right-0 mt-1 w-32 bg-[#1E293B] border border-slate-700 rounded-lg shadow-xl py-1 z-20 text-xs">
                                        <template x-if="reply.can_edit">
                                            <button type="button"
                                                    @click="replyMenu = false; startEditing(reply)"
                                                    class="w-full text-left px-3 py-1 text-slate-300 hover:bg-slate-700 flex items-center gap-1.5">
                                                <i data-lucide="edit-3" class="w-3 h-3 text-slate-400"></i>
                                                <span>Edit</span>
                                            </button>
                                        </template>
                                        <template x-if="reply.can_delete">
                                            <button type="button"
                                                    @click="replyMenu = false; deleteComment(reply, comment)"
                                                    class="w-full text-left px-3 py-1 text-rose-400 hover:bg-rose-950/30 flex items-center gap-1.5">
                                                <i data-lucide="trash-2" class="w-3 h-3"></i>
                                                <span>Delete</span>
                                            </button>
                                        </template>
                                        <template x-if="reply.can_report">
                                            <button type="button"
                                                    @click="replyMenu = false; openReportModal(reply)"
                                                    class="w-full text-left px-3 py-1 text-amber-400 hover:bg-amber-950/30 flex items-center gap-1.5">
                                                <i data-lucide="flag" class="w-3 h-3"></i>
                                                <span>Report</span>
                                            </button>
                                        </template>
                                        <template x-if="reply.can_moderate">
                                            <button type="button"
                                                    @click="replyMenu = false; moderateComment(reply, reply.status === 'hidden' ? 'unhide' : 'hide', comment)"
                                                    class="w-full text-left px-3 py-1 text-slate-300 hover:bg-slate-700 flex items-center gap-1.5">
                                                <i data-lucide="shield" class="w-3 h-3 text-slate-400"></i>
                                                <span x-text="reply.status === 'hidden' ? 'Unhide' : 'Hide'"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Reply Content (or edit mode) -->
                            <div>
                                <template x-if="editingCommentId !== reply.id">
                                    <div class="text-xs text-slate-300 whitespace-pre-line leading-relaxed" x-text="reply.content"></div>
                                </template>
                                <template x-if="editingCommentId === reply.id">
                                    <div class="space-y-2">
                                        <textarea x-model="editCommentText"
                                                  rows="2"
                                                  class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-xs text-slate-200 focus:border-rose-500 focus:outline-none"></textarea>
                                        <div class="flex items-center gap-2 justify-end">
                                            <button type="button" @click="cancelEditing()" class="btn-secondary text-[10px] py-1 px-2.5">Cancel</button>
                                            <button type="button" @click="saveEdit(reply, comment)" class="btn-primary text-[10px] py-1 px-2.5">Save</button>
                                        </div>
                                    </div>
                                </template>
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

                    <!-- Cohesive Reply Input Card -->
                    <div class="rounded-xl bg-slate-950 border border-slate-700/80 focus-within:border-[#8F3033] focus-within:ring-1 focus-within:ring-[#8F3033]/50 transition shadow-inner">
                        <div class="p-2.5">
                            <textarea x-ref="'replyInput_' + comment.id"
                                      x-model="replyText"
                                      rows="2"
                                      maxlength="2000"
                                      placeholder="Write your reply..."
                                      class="w-full bg-transparent border-0 p-0 text-xs text-slate-200 placeholder-slate-500 focus:ring-0 focus:outline-none resize-none leading-relaxed"></textarea>
                        </div>

                        <div class="flex items-center justify-between px-3 py-1.5 border-t border-slate-800/80 bg-slate-900/40 rounded-b-xl">
                            <span class="text-[10px] text-slate-500 font-mono" x-text="replyText.length + '/2000'"></span>
                            <div class="flex items-center gap-2">
                                <button type="button"
                                        @click="cancelReply()"
                                        class="px-2.5 py-1 rounded-lg text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
                                    Cancel
                                </button>
                                <button type="button"
                                        @click="submitReply(comment)"
                                        :disabled="submittingReply || !replyText.trim()"
                                        aria-label="Send reply"
                                        title="Send reply"
                                        class="w-8 h-8 min-w-[32px] min-h-[32px] shrink-0 rounded-lg bg-gradient-to-r from-[#560E0F] to-[#8F3033] hover:from-[#6d1416] hover:to-[#a2383c] border border-[#8F3033]/60 shadow flex items-center justify-center text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-[#8F3033] transition-all disabled:opacity-40 disabled:cursor-not-allowed active:scale-95">
                                    <svg x-show="!submittingReply" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m22 2-7 20-4-9-9-4Z"/>
                                        <path d="M22 2 11 13"/>
                                    </svg>
                                    <svg x-show="submittingReply" x-cloak class="animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                </button>
                            </div>
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

</div>

