/**
 * Alpine component for Announcement Engagement (Reactions & Threaded Comments)
 */
export default (config) => ({
    announcementId: config.announcementId,
    routes: config.routes,
    currentUser: config.currentUser,

    // Reactions state
    pickerOpen: false,
    userReaction: null,
    totalReactions: 0,
    breakdown: {
        like: { type: 'like', emoji: '👍', label: 'Like', count: 0 },
        love: { type: 'love', emoji: '❤️', label: 'Love', count: 0 },
        haha: { type: 'haha', emoji: '😂', label: 'Haha', count: 0 },
        wow: { type: 'wow', emoji: '😮', label: 'Wow', count: 0 },
        celebrate: { type: 'celebrate', emoji: '🎉', label: 'Celebrate', count: 0 },
        support: { type: 'support', emoji: '🙏', label: 'Support', count: 0 },
    },
    recentReactors: [],
    showReactorsModal: false,

    // Comments state
    comments: [],
    loadingComments: true,
    totalComments: 0,
    newCommentText: '',
    submitting: false,

    // Threading state
    expandedThreads: {},
    activeReplyParentId: null,
    replyText: '',
    submittingReply: false,

    // Editing state
    editingCommentId: null,
    editCommentText: '',
    savingEdit: false,

    // Delete confirmation modal state
    showDeleteModal: false,
    deletingCommentTarget: null,
    deletingParentCommentTarget: null,
    isDeleting: false,

    // Reporting state
    showReportModal: false,
    reportingComment: null,
    reportReason: 'harassment',
    reportDetails: '',
    submittingReport: false,

    // Mention state
    mentionDropdownOpen: false,
    mentionSuggestions: [],
    mentionQuery: '',
    mentionHighlightedIndex: 0,
    mentionContext: 'main', // 'main' | 'reply' | 'edit'
    mentionActiveCommentId: null,
    mentionDebounceTimer: null,
    cachedMentionUsers: null,
    // Track explicitly selected mention objects: { [userId]: userObject }
    selectedMentionsByContext: {
        main: {},
        reply: {},
        edit: {},
    },

    autoResizeTextarea(el) {
        if (!el) return;
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 128) + 'px';
    },

    // Comment Reaction Types Definition
    commentReactionTypes: [
        { key: 'love', emoji: '❤️', label: 'Love', purpose: 'Strong appreciation or support' },
        { key: 'like', emoji: '👍', label: 'Like', purpose: 'General appreciation or acknowledgment' },
        { key: 'haha', emoji: '😂', label: 'Haha', purpose: 'Humor or amusement' },
        { key: 'wow', emoji: '😮', label: 'Wow', purpose: 'Surprise or excitement' },
        { key: 'sad', emoji: '😢', label: 'Sad', purpose: 'Sympathy or sadness' },
        { key: 'angry', emoji: '😡', label: 'Angry', purpose: 'Frustration or disapproval' },
    ],

    getCommentReactionDisplay(comment) {
        if (!comment.user_reaction) {
            return { emoji: '❤️', label: 'React' };
        }
        const match = this.commentReactionTypes.find(r => r.key === comment.user_reaction);
        return {
            emoji: match ? match.emoji : '❤️',
            label: match ? match.label : 'React'
        };
    },

    async toggleCommentReaction(comment, reactionType) {
        const prevUserReaction = comment.user_reaction;
        const prevTotalReactions = comment.total_reactions || 0;
        const prevCounts = { ...(comment.reaction_counts || {}) };

        // Optimistic UI update
        if (comment.user_reaction === reactionType) {
            // Unreact
            comment.user_reaction = null;
            comment.total_reactions = Math.max(0, prevTotalReactions - 1);
            if (prevCounts[reactionType]) {
                prevCounts[reactionType] = Math.max(0, prevCounts[reactionType] - 1);
            }
        } else {
            // New reaction or changed reaction
            if (comment.user_reaction && prevCounts[comment.user_reaction]) {
                prevCounts[comment.user_reaction] = Math.max(0, prevCounts[comment.user_reaction] - 1);
            } else {
                comment.total_reactions = prevTotalReactions + 1;
            }
            comment.user_reaction = reactionType;
            prevCounts[reactionType] = (prevCounts[reactionType] || 0) + 1;
        }
        comment.reaction_counts = prevCounts;

        try {
            const url = `${this.routes.commentReactionToggleBase}/${comment.id}/reactions`;
            const res = await window.axios.post(url, { reaction_type: reactionType });
            if (res.data.success) {
                comment.user_reaction = res.data.user_reaction;
                comment.total_reactions = res.data.total_reactions;
                comment.reaction_counts = res.data.reaction_counts;
                comment.reactors = res.data.reactors || [];
            }
        } catch (e) {
            console.error('Failed to toggle comment reaction:', e);
            // Rollback optimistic state
            comment.user_reaction = prevUserReaction;
            comment.total_reactions = prevTotalReactions;
            comment.reaction_counts = prevCounts;
            alert(e.response?.data?.message || 'Failed to submit reaction.');
        }
    },

    init() {
        this.fetchEngagement();
        this.fetchComments();
        this.prefetchMentionUsers();

        this.$nextTick(() => {
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }
        });
    },

    get topReactions() {
        return Object.values(this.breakdown)
            .filter(r => r.count > 0)
            .sort((a, b) => b.count - a.count)
            .slice(0, 3);
    },

    async fetchEngagement() {
        try {
            const res = await window.axios.get(this.routes.engagement);
            if (res.data.success) {
                this.userReaction = res.data.user_reaction;
                this.totalReactions = res.data.total_reactions;
                this.breakdown = res.data.breakdown;
                this.recentReactors = res.data.recent_reactors || [];
                this.totalComments = res.data.comments_count || 0;
            }
        } catch (e) {
            console.error('Failed to load announcement engagement:', e);
        }
    },

    async toggleReaction(type) {
        this.pickerOpen = false;
        try {
            const res = await window.axios.post(this.routes.reactionsToggle, { reaction_type: type });
            if (res.data.success) {
                this.userReaction = res.data.user_reaction;
                this.totalReactions = res.data.total_reactions;
                this.breakdown = res.data.breakdown;
            }
        } catch (e) {
            console.error('Failed to toggle reaction:', e);
        }
    },

    async fetchComments() {
        this.loadingComments = true;
        try {
            const res = await window.axios.get(this.routes.commentsIndex);
            if (res.data.success) {
                this.comments = res.data.data;
                this.totalComments = res.data.total;
            }
        } catch (e) {
            console.error('Failed to load comments:', e);
        } finally {
            this.loadingComments = false;
            this.$nextTick(() => {
                if (window.lucide && typeof window.lucide.createIcons === 'function') {
                    window.lucide.createIcons();
                }
            });
        }
    },

    focusMainCommentBox() {
        if (this.$refs.mainCommentInput) {
            this.$refs.mainCommentInput.focus();
            this.$refs.mainCommentInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    },

    buildPayloadContent(rawText, context = 'main') {
        let content = rawText;
        const selected = this.selectedMentionsByContext[context] || {};
        const mentionedIds = [];

        // For each user selected in this context, convert @Name to structured format if present in text
        for (const [userId, user] of Object.entries(selected)) {
            const escapedName = user.name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const regex = new RegExp(`@${escapedName}\\b`, 'g');
            if (regex.test(content)) {
                content = content.replace(regex, `@[${user.name}](user:${user.id})`);
                mentionedIds.push(userId);
            }
        }

        return { content, mentioned_user_ids: mentionedIds };
    },

    async submitMainComment() {
        const text = this.newCommentText.trim();
        if (!text) return;

        const payload = this.buildPayloadContent(text, 'main');

        this.submitting = true;
        try {
            const res = await window.axios.post(this.routes.commentsStore, {
                content: payload.content,
                mentioned_user_ids: payload.mentioned_user_ids,
            });

            if (res.data.success) {
                this.comments.unshift(res.data.comment);
                this.newCommentText = '';
                this.selectedMentionsByContext.main = {};
                this.totalComments++;
                this.$nextTick(() => {
                    if (window.lucide && typeof window.lucide.createIcons === 'function') {
                        window.lucide.createIcons();
                    }
                });
            }
        } catch (e) {
            alert(e.response?.data?.message || 'Failed to post comment.');
        } finally {
            this.submitting = false;
        }
    },

    toggleThread(commentId) {
        this.expandedThreads[commentId] = !this.expandedThreads[commentId];
        this.$nextTick(() => {
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }
        });
    },

    openReplyBox(comment, targetReply = null) {
        this.activeReplyParentId = comment.id;
        this.expandedThreads[comment.id] = true; // Auto-expand thread when replying

        if (targetReply && targetReply.author) {
            this.replyText = `@${targetReply.author.name} `;
            if (!this.selectedMentionsByContext.reply) {
                this.selectedMentionsByContext.reply = {};
            }
            this.selectedMentionsByContext.reply[targetReply.author.id] = targetReply.author;
        } else {
            this.replyText = '';
        }

        this.$nextTick(() => {
            const input = this.$refs['replyInput_' + comment.id];
            if (input) {
                input.focus();
                input.setSelectionRange(this.replyText.length, this.replyText.length);
                this.autoResizeTextarea(input);
            }
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }
        });
    },

    cancelReply() {
        this.activeReplyParentId = null;
        this.replyText = '';
        this.selectedMentionsByContext.reply = {};
    },

    async submitReply(parentComment) {
        const text = this.replyText.trim();
        if (!text) return;

        const payload = this.buildPayloadContent(text, 'reply');

        this.submittingReply = true;
        try {
            const res = await window.axios.post(this.routes.commentsStore, {
                parent_id: parentComment.id,
                content: payload.content,
                mentioned_user_ids: payload.mentioned_user_ids,
            });

            if (res.data.success) {
                if (!parentComment.replies) parentComment.replies = [];
                parentComment.replies.push(res.data.comment);
                parentComment.replies_count++;
                this.totalComments++;
                this.cancelReply();
                this.expandedThreads[parentComment.id] = true;
                this.$nextTick(() => {
                    if (window.lucide && typeof window.lucide.createIcons === 'function') {
                        window.lucide.createIcons();
                    }
                });
            }
        } catch (e) {
            alert(e.response?.data?.message || 'Failed to post reply.');
        } finally {
            this.submittingReply = false;
        }
    },

    startEditing(comment) {
        this.editingCommentId = comment.id;
        // Strip raw syntax for user editing: @[Name](user:UUID) -> @Name
        this.editCommentText = (comment.content || '').replace(/@\[([^\]]+)\]\(user:[a-zA-Z0-9\-]+\)/g, '@$1');
        this.selectedMentionsByContext.edit = {};
    },

    cancelEditing() {
        this.editingCommentId = null;
        this.editCommentText = '';
        this.selectedMentionsByContext.edit = {};
    },

    openDeleteModal(comment, parentComment = null) {
        this.deletingCommentTarget = comment;
        this.deletingParentCommentTarget = parentComment;
        this.showDeleteModal = true;
    },

    closeDeleteModal() {
        this.showDeleteModal = false;
        this.deletingCommentTarget = null;
        this.deletingParentCommentTarget = null;
        this.isDeleting = false;
    },

    async confirmDelete() {
        const comment = this.deletingCommentTarget;
        if (!comment) return;

        const parentComment = this.deletingParentCommentTarget;
        const isReply = !!comment.parent_id;

        this.isDeleting = true;
        try {
            const res = await window.axios.delete(`/announcements/comments/${comment.id}`);
            if (res.data.success) {
                if (isReply && parentComment) {
                    parentComment.replies = parentComment.replies.filter(r => r.id !== comment.id);
                    parentComment.replies_count = Math.max(0, parentComment.replies_count - 1);
                    this.totalComments = Math.max(0, this.totalComments - 1);
                } else {
                    const deletedCount = 1 + (comment.replies_count || 0);
                    this.comments = this.comments.filter(c => c.id !== comment.id);
                    this.totalComments = Math.max(0, this.totalComments - deletedCount);
                }
                this.closeDeleteModal();
            }
        } catch (e) {
            alert(e.response?.data?.message || 'Failed to delete comment.');
            this.isDeleting = false;
        }
    },

    async saveEdit(comment, parentComment = null) {
        const text = this.editCommentText.trim();
        if (!text) return;

        const payload = this.buildPayloadContent(text, 'edit');

        this.savingEdit = true;
        try {
            const res = await window.axios.put(`/announcements/comments/${comment.id}`, {
                content: payload.content,
                mentioned_user_ids: payload.mentioned_user_ids,
            });

            if (res.data.success) {
                comment.content = res.data.comment.content;
                comment.is_edited = true;
                this.cancelEditing();
            }
        } catch (e) {
            alert(e.response?.data?.message || 'Failed to update comment.');
        } finally {
            this.savingEdit = false;
        }
    },

    openReportModal(comment) {
        this.reportingComment = comment;
        this.reportReason = 'harassment';
        this.reportDetails = '';
        this.showReportModal = true;
    },

    async submitReport() {
        if (!this.reportingComment) return;

        this.submittingReport = true;
        try {
            const res = await window.axios.post(`/announcements/comments/${this.reportingComment.id}/report`, {
                reason: this.reportReason,
                details: this.reportDetails,
            });

            alert(res.data.message || 'Report submitted.');
            this.showReportModal = false;
        } catch (e) {
            alert(e.response?.data?.message || 'Failed to submit report.');
        } finally {
            this.submittingReport = false;
        }
    },

    async moderateComment(comment, action, parentComment = null) {
        try {
            const res = await window.axios.post(`/announcements/comments/${comment.id}/moderate`, {
                action: action,
            });

            if (res.data.success) {
                comment.status = res.data.comment.status;
            }
        } catch (e) {
            alert(e.response?.data?.message || 'Moderation action failed.');
        }
    },

    async prefetchMentionUsers() {
        try {
            const endpoint = this.routes.mentionableUsers || '/announcements/mentionable-users';
            const res = await window.axios.get(endpoint, { params: { q: '' } });
            if (res.data?.users) {
                this.cachedMentionUsers = res.data.users;
            }
        } catch (e) {
            // Silently ignore prefetch failure
        }
    },

    // Mention handling functions
    onInputMention(event, context = 'main', commentId = null) {
        const textarea = event.target;
        const text = textarea.value;
        const cursorPos = textarea.selectionStart;

        // Look at text up to current cursor position
        const textBeforeCursor = text.slice(0, cursorPos);
        const lastAtMatch = textBeforeCursor.match(/@([a-zA-Z0-9_\.\-]*)$/);

        if (lastAtMatch) {
            this.mentionContext = context;
            this.mentionActiveCommentId = commentId;
            this.mentionQuery = lastAtMatch[1];
            this.mentionDropdownOpen = true;

            // If we have cached users, filter locally for immediate feedback while fresh query executes
            if (this.cachedMentionUsers && this.cachedMentionUsers.length > 0) {
                const q = this.mentionQuery.toLowerCase();
                const filtered = this.cachedMentionUsers.filter(u =>
                    (u.name && u.name.toLowerCase().includes(q)) ||
                    (u.handle && u.handle.toLowerCase().includes(q)) ||
                    (u.program && u.program.toLowerCase().includes(q))
                );
                if (filtered.length > 0) {
                    this.mentionSuggestions = filtered;
                    this.mentionHighlightedIndex = 0;
                }
            }

            this.fetchMentionSuggestions(this.mentionQuery);
        } else {
            this.closeMentionDropdown();
        }
    },

    onKeydownMention(event, context = 'main', commentId = null) {
        if (!this.mentionDropdownOpen || this.mentionContext !== context) return false;
        if (commentId && this.mentionActiveCommentId !== commentId) return false;

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            if (this.mentionSuggestions.length > 0) {
                this.mentionHighlightedIndex = (this.mentionHighlightedIndex + 1) % this.mentionSuggestions.length;
            }
            return true;
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            if (this.mentionSuggestions.length > 0) {
                this.mentionHighlightedIndex = (this.mentionHighlightedIndex - 1 + this.mentionSuggestions.length) % this.mentionSuggestions.length;
            }
            return true;
        } else if (event.key === 'Enter' || event.key === 'Tab') {
            if (this.mentionSuggestions.length > 0 && this.mentionSuggestions[this.mentionHighlightedIndex]) {
                event.preventDefault();
                this.selectMention(this.mentionSuggestions[this.mentionHighlightedIndex], context, commentId);
                return true;
            }
        } else if (event.key === 'Escape') {
            event.preventDefault();
            this.closeMentionDropdown();
            return true;
        }

        return false;
    },

    handleComposerKeydown(event, context = 'main', comment = null) {
        // If IME is composing (e.g. CJK/accented input), let it finish
        if (event.isComposing || event.keyCode === 229) return;

        // If mention dropdown is open, pass event to mention handler
        if (this.mentionDropdownOpen && this.mentionContext === context) {
            const handled = this.onKeydownMention(event, context, comment ? comment.id : null);
            if (handled) return;
        }

        // Escape: cancel editing or replying
        if (event.key === 'Escape') {
            event.preventDefault();
            if (context === 'edit') {
                this.cancelEditing();
            } else if (context === 'reply') {
                this.cancelReply();
            }
            return;
        }

        // Enter key: Submit (Enter or Ctrl+Enter / Cmd+Enter)
        if (event.key === 'Enter') {
            // Shift + Enter: Allow natural newline insertion and auto-expand
            if (event.shiftKey) {
                this.$nextTick(() => {
                    this.autoResizeTextarea(event.target);
                });
                return;
            }

            // Plain Enter or Ctrl+Enter: Submit
            event.preventDefault();

            if (context === 'main') {
                if (!this.submitting && this.newCommentText.trim()) {
                    this.submitMainComment();
                }
            } else if (context === 'reply' && comment) {
                if (!this.submittingReply && this.replyText.trim()) {
                    this.submitReply(comment);
                }
            } else if (context === 'edit' && comment) {
                if (!this.savingEdit && this.editCommentText.trim()) {
                    this.saveEdit(comment);
                }
            }
        }
    },

    async fetchMentionSuggestions(query) {
        clearTimeout(this.mentionDebounceTimer);
        this.mentionDebounceTimer = setTimeout(async () => {
            try {
                const endpoint = this.routes.mentionableUsers || '/announcements/mentionable-users';
                const res = await window.axios.get(endpoint, {
                    params: { q: query }
                });
                this.mentionSuggestions = res.data.users || [];
                if (!this.cachedMentionUsers && !query) {
                    this.cachedMentionUsers = this.mentionSuggestions;
                }
                this.mentionHighlightedIndex = 0;
            } catch (err) {
                console.error('Failed to load mention suggestions:', err);
                if (!this.cachedMentionUsers) {
                    this.mentionSuggestions = [];
                }
            }
        }, 120);
    },

    selectMention(user, context = 'main', commentId = null) {
        let textarea = null;
        if (context === 'main') {
            textarea = this.$refs.mainCommentInput;
        } else if (context === 'reply' && commentId) {
            textarea = this.$refs['replyInput_' + commentId];
        } else if (context === 'edit' && commentId) {
            textarea = this.$refs['editInput_' + commentId];
        }

        if (!textarea) return;

        const text = textarea.value;
        const cursorPos = textarea.selectionStart;
        const textBeforeCursor = text.slice(0, cursorPos);
        const textAfterCursor = text.slice(cursorPos);

        const lastAtIndex = textBeforeCursor.lastIndexOf('@');
        if (lastAtIndex === -1) return;

        // Clean, readable mention text: @Name (no ugly UUID or markdown brackets in the composer!)
        const mentionText = `@${user.name} `;
        const newText = textBeforeCursor.slice(0, lastAtIndex) + mentionText + textAfterCursor;

        // Register selected user for this context
        if (!this.selectedMentionsByContext[context]) {
            this.selectedMentionsByContext[context] = {};
        }
        this.selectedMentionsByContext[context][user.id] = user;

        if (context === 'main') {
            this.newCommentText = newText;
        } else if (context === 'reply') {
            this.replyText = newText;
        } else if (context === 'edit') {
            this.editCommentText = newText;
        }

        this.closeMentionDropdown();

        this.$nextTick(() => {
            textarea.focus();
            const newCursorPos = lastAtIndex + mentionText.length;
            textarea.setSelectionRange(newCursorPos, newCursorPos);
            this.autoResizeTextarea(textarea);
        });
    },

    closeMentionDropdown() {
        this.mentionDropdownOpen = false;
        this.mentionSuggestions = [];
        this.mentionQuery = '';
        this.mentionHighlightedIndex = 0;
    },

    renderFormattedContent(rawContent) {
        if (!rawContent) return '';

        // Safely escape HTML to prevent XSS
        const div = document.createElement('div');
        div.textContent = rawContent;
        let escaped = div.innerHTML;

        // Convert @[Name](user:UUID) to styled mention badge
        escaped = escaped.replace(/@\[([^\]]+)\]\(user:([a-zA-Z0-9\-]+)\)/g, (match, name, userId) => {
            return `<span class="inline-flex items-center gap-1 font-semibold text-rose-300 bg-rose-500/15 border border-rose-500/30 px-1.5 py-0.5 rounded-md hover:bg-rose-500/25 transition">@${name}</span>`;
        });

        // Convert standard @username or @handle if any
        escaped = escaped.replace(/(^|[^a-zA-Z0-9_\/])@([a-zA-Z0-9_\.\-]+)/g, (match, prefix, handle) => {
            return `${prefix}<span class="inline-flex items-center font-semibold text-rose-300 bg-rose-500/10 border border-rose-500/20 px-1 py-0.2 rounded hover:bg-rose-500/20 transition">@${handle}</span>`;
        });

        return escaped;
    },
});

