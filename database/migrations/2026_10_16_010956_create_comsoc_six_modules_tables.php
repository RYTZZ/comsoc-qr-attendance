<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Support Tickets (Help Desk & Dispute Center)
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ticket_number')->unique();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('category', 60); // attendance, qr_scan, exp_dispute, reward, event_reg, account, technical, other
            $table->string('subject', 255);
            $table->text('description');
            $table->string('status', 30)->default('open'); // open, in_review, awaiting_member, resolved, closed
            $table->string('priority', 20)->default('normal'); // low, normal, high, urgent
            
            // Related entities
            $table->foreignUuid('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->foreignUuid('xp_transaction_id')->nullable()->constrained('xp_transactions')->nullOnDelete();
            $table->foreignUuid('reward_redemption_id')->nullable()->constrained('reward_redemptions')->nullOnDelete();
            $table->foreignUuid('attendance_record_id')->nullable()->constrained('attendance_records')->nullOnDelete();
            
            // Staff assignment and escalation
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_escalated')->default(false);
            $table->string('escalation_reason')->nullable();
            
            // Resolution
            $table->string('resolution_code', 50)->nullable();
            $table->text('resolution_summary')->nullable();
            $table->foreignUuid('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'priority']);
            $table->index(['category']);
            $table->index('assigned_to');
        });

        // 2. Support Ticket Messages / Internal Notes
        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->boolean('is_internal_note')->default(false);
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->timestamps();

            $table->index(['support_ticket_id', 'is_internal_note']);
        });

        // 3. Support Ticket Status History (Audit trail)
        Schema::create('support_ticket_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action'); // created, status_changed, assigned, escalated, resolved, closed, reopened, correction_linked
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('support_ticket_id');
        });

        // 4. Update announcements table for audience targeting and required acknowledgments
        Schema::table('announcements', function (Blueprint $table) {
            $table->string('target_audience_type', 30)->default('all')->after('target_url'); // all, program, year_level, role
            $table->json('target_audience_values')->nullable()->after('target_audience_type'); // e.g. ["BSIT", "BSIS"] or ["1", "2"]
            $table->boolean('requires_acknowledgment')->default(false)->after('target_audience_values');
            $table->boolean('is_pinned')->default(false)->after('requires_acknowledgment');
            $table->foreignUuid('event_id')->nullable()->after('is_pinned')->constrained('events')->nullOnDelete();
        });

        // 5. Announcement Read Receipts and Acknowledgments
        Schema::create('announcement_receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('announcement_id')->constrained('announcements')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->unique(['announcement_id', 'user_id']);
            $table->index(['user_id', 'read_at']);
        });

        // 6. Project Collaboration Listings (Team Finder)
        Schema::create('collaboration_projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('problem_statement');
            $table->text('description');
            $table->string('category', 60); // web_dev, mobile, ui_ux, cybersecurity, networking, data_analytics, ai, iot, other
            $table->json('skills_needed')->nullable();
            $table->json('preferred_roles')->nullable();
            $table->unsignedSmallInteger('team_size_target')->default(4);
            $table->string('stage', 40)->default('idea'); // idea, prototype, mvp, active_development, completed
            $table->string('expected_commitment', 60)->default('flexible'); // 2-4 hrs/wk, 5-10 hrs/wk, full-time
            $table->string('repository_url')->nullable();
            $table->string('demo_url')->nullable();
            $table->timestamp('application_deadline')->nullable();
            $table->string('status', 30)->default('recruiting'); // recruiting, in_progress, completed, archived, moderated
            $table->boolean('is_recruiting')->default(true);
            $table->text('moderation_notes')->nullable();
            $table->foreignUuid('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'category']);
            $table->index('owner_id');
        });

        // 7. Project Applications
        Schema::create('project_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('collaboration_projects')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('preferred_role', 80);
            $table->text('motivation');
            $table->text('skills_summary')->nullable();
            $table->string('status', 30)->default('pending'); // pending, under_review, accepted, declined, withdrawn
            $table->text('feedback')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->foreignUuid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
            $table->index(['project_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        // 8. Project Members (Accepted collaborators)
        Schema::create('project_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('collaboration_projects')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 80)->default('Contributor');
            $table->timestamp('joined_at')->useCurrent();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
            $table->index(['project_id', 'is_active']);
        });

        // 9. Project Milestones & Tasks
        Schema::create('project_milestones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('collaboration_projects')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status', 30)->default('pending'); // pending, in_progress, completed
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });

        // 10. Volunteer Opportunities Board
        Schema::create('volunteer_opportunities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description');
            $table->foreignUuid('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->string('category', 60); // ushering, tech_support, registration, multimedia, logistics, workshop, community_service
            $table->json('required_skills')->nullable();
            $table->text('responsibilities');
            $table->string('location_type', 40)->default('onsite'); // onsite, hybrid, remote
            $table->string('location_details')->nullable();
            $table->datetime('starts_at');
            $table->datetime('ends_at');
            $table->datetime('application_deadline')->nullable();
            $table->unsignedSmallInteger('capacity')->default(10);
            $table->decimal('expected_hours', 5, 2)->default(3.00);
            $table->text('eligibility_requirements')->nullable();
            $table->foreignUuid('coordinator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('open'); // open, filled, in_progress, completed, cancelled
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('exp_reward_per_hour')->default(50); // Default EXP awarded per approved service hour
            $table->timestamps();

            $table->index(['status', 'category']);
            $table->index('event_id');
        });

        // 11. Volunteer Applications
        Schema::create('volunteer_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('volunteer_opportunity_id')->constrained('volunteer_opportunities')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('motivation_statement');
            $table->text('relevant_skills')->nullable();
            $table->string('assigned_shift', 100)->nullable();
            $table->string('status', 30)->default('applied'); // applied, under_review, accepted, declined, withdrawn, completed
            $table->text('notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Service Hours & EXP Verification
            $table->decimal('actual_hours_submitted', 5, 2)->default(0.00);
            $table->decimal('approved_service_hours', 5, 2)->default(0.00);
            $table->text('completion_evidence')->nullable();
            $table->text('hours_approval_reason')->nullable();
            $table->foreignUuid('hours_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hours_approved_at')->nullable();
            $table->unsignedInteger('awarded_exp')->default(0);
            $table->foreignUuid('xp_transaction_id')->nullable()->constrained('xp_transactions')->nullOnDelete();

            $table->timestamps();

            $table->unique(['volunteer_opportunity_id', 'user_id']);
            $table->index(['volunteer_opportunity_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        // 12. Event Feedback Surveys (for Event Analytics & Impact Reports Module 3)
        Schema::create('event_feedback', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->unsignedTinyInteger('organization_rating')->default(5); // 1-5
            $table->unsignedTinyInteger('content_rating')->default(5); // 1-5
            $table->text('positive_feedback')->nullable();
            $table->text('suggestions')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
            $table->index('event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_feedback');
        Schema::dropIfExists('volunteer_applications');
        Schema::dropIfExists('volunteer_opportunities');
        Schema::dropIfExists('project_milestones');
        Schema::dropIfExists('project_members');
        Schema::dropIfExists('project_applications');
        Schema::dropIfExists('collaboration_projects');
        Schema::dropIfExists('announcement_receipts');

        if (Schema::hasColumn('announcements', 'target_audience_type')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->dropForeign(['event_id']);
                $table->dropColumn([
                    'target_audience_type',
                    'target_audience_values',
                    'requires_acknowledgment',
                    'is_pinned',
                    'event_id'
                ]);
            });
        }

        Schema::dropIfExists('support_ticket_histories');
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
    }
};
