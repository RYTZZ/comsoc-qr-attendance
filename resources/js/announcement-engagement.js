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

    autoResizeTextarea(el) {
        if (!el) return;
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 128) + 'px';
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

    async submitMainComment() {
        const text = this.newCommentText.trim();
        if (!text) return;

        this.submitting = true;
        try {
            const res = await window.axios.post(this.routes.commentsStore, {
                content: text,
            });

            if (res.data.success) {
                this.comments.unshift(res.data.comment);
                this.newCommentText = '';
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

    openReplyBox(comment) {
        this.activeReplyParentId = comment.id;
        this.replyText = '';
        this.expandedThreads[comment.id] = true; // Auto-expand thread when replying
        this.$nextTick(() => {
            const input = this.$refs['replyInput_' + comment.id];
            if (input) input.focus();
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }
        });
    },

    cancelReply() {
        this.activeReplyParentId = null;
        this.replyText = '';
    },

    async submitReply(parentComment) {
        const text = this.replyText.trim();
        if (!text) return;

        this.submittingReply = true;
        try {
            const res = await window.axios.post(this.routes.commentsStore, {
                parent_id: parentComment.id,
                content: text,
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
        this.editCommentText = comment.content;
    },

    cancelEditing() {
        this.editingCommentId = null;
        this.editCommentText = '';
    },

    async saveEdit(comment, parentComment = null) {
        const text = this.editCommentText.trim();
        if (!text) return;

        try {
            const res = await window.axios.put(`/announcements/comments/${comment.id}`, {
                content: text,
            });

            if (res.data.success) {
                comment.content = res.data.comment.content;
                comment.is_edited = true;
                this.cancelEditing();
            }
        } catch (e) {
            alert(e.response?.data?.message || 'Failed to update comment.');
        }
    },

    async deleteComment(comment, parentComment = null) {
        const isReply = !!comment.parent_id;
        const confirmMsg = isReply
            ? 'Are you sure you want to delete this reply?'
            : 'Are you sure you want to delete this comment? Its replies will also be removed.';

        if (!confirm(confirmMsg)) return;

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
            }
        } catch (e) {
            alert(e.response?.data?.message || 'Failed to delete comment.');
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

            // If we have cached users and query is short, show immediately while fetching fresh
            if (this.cachedMentionUsers && this.cachedMentionUsers.length > 0) {
                const q = this.mentionQuery.toLowerCase();
                this.mentionSuggestions = this.cachedMentionUsers.filter(u =>
                    u.name.toLowerCase().includes(q) || u.handle.toLowerCase().includes(q)
                );
                this.mentionHighlightedIndex = 0;
            }

            this.fetchMentionSuggestions(this.mentionQuery);
        } else {
            this.closeMentionDropdown();
        }
    },

    onKeydownMention(event, context = 'main', commentId = null) {
        if (!this.mentionDropdownOpen || this.mentionContext !== context) return;
        if (commentId && this.mentionActiveCommentId !== commentId) return;

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            if (this.mentionSuggestions.length > 0) {
                this.mentionHighlightedIndex = (this.mentionHighlightedIndex + 1) % this.mentionSuggestions.length;
            }
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            if (this.mentionSuggestions.length > 0) {
                this.mentionHighlightedIndex = (this.mentionHighlightedIndex - 1 + this.mentionSuggestions.length) % this.mentionSuggestions.length;
            }
        } else if (event.key === 'Enter' || event.key === 'Tab') {
            if (this.mentionSuggestions.length > 0 && this.mentionSuggestions[this.mentionHighlightedIndex]) {
                event.preventDefault();
                this.selectMention(this.mentionSuggestions[this.mentionHighlightedIndex], context, commentId);
            }
        } else if (event.key === 'Escape') {
            this.closeMentionDropdown();
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

        // Mention format: @[Name](user:UUID)
        const mentionText = `@[${user.name}](user:${user.id}) `;
        const newText = textBeforeCursor.slice(0, lastAtIndex) + mentionText + textAfterCursor;

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

