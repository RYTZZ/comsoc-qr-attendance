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

    init() {
        this.fetchEngagement();
        this.fetchComments();

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
});

