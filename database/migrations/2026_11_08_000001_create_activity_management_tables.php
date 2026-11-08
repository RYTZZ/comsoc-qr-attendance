<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Clean up any partial tables from an interrupted MySQL migration run
        $this->down();

        // 1. Dynamic Registration Forms (Super Admin Exclusive)
        Schema::create('registration_forms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('intended_categories')->nullable();
            $table->string('status', 30)->default('draft'); // draft, published, archived
            $table->unsignedInteger('version')->default(1);
            $table->json('published_snapshot')->nullable();
            $table->boolean('is_template')->default(false);
            $table->foreignUuid('parent_form_id')->nullable()->constrained('registration_forms', 'id', 'reg_forms_parent_fk')->nullOnDelete();
            $table->foreignUuid('created_by')->constrained('users', 'id', 'reg_forms_created_by_fk')->cascadeOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users', 'id', 'reg_forms_updated_by_fk')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'is_template'], 'reg_forms_status_tpl_idx');
        });

        // 2. Dynamic Registration Form Fields (Questions)
        Schema::create('registration_form_fields', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('registration_form_id')->constrained('registration_forms', 'id', 'reg_form_fields_form_fk')->cascadeOnDelete();
            $table->string('field_key', 100);
            $table->string('label');
            $table->text('help_text')->nullable();
            $table->string('placeholder')->nullable();
            $table->string('question_type', 40); // short_answer, paragraph, dropdown, multiple_choice, checkboxes, number, date, file_upload, yes_no
            $table->boolean('is_required')->default(false);
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('validation_rules')->nullable(); // accepted_file_types, max_file_size_kb, min, max
            $table->timestamps();

            $table->index(['registration_form_id', 'sort_order'], 'reg_form_fields_sort_idx');
        });

        // 3. Dynamic Registration Form Options (for dropdown, multiple_choice, checkboxes)
        Schema::create('registration_form_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('registration_form_field_id')->constrained('registration_form_fields', 'id', 'reg_form_opts_field_fk')->cascadeOnDelete();
            $table->string('label');
            $table->string('value');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->index(['registration_form_field_id', 'sort_order'], 'reg_form_opts_field_sort_idx');
        });

        // 4. Parent Activities / Events (Level 1)
        Schema::create('activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignUuid('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('category', 80)->default('General COMSOC Activity');
            $table->string('banner_path')->nullable();
            $table->string('venue')->nullable();
            $table->string('organizer_name')->nullable();
            $table->text('general_requirements')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->dateTime('registration_opens_at')->nullable();
            $table->dateTime('registration_closes_at')->nullable();
            $table->string('status', 30)->default('draft'); // draft, published, registration_open, registration_closed, ongoing, completed, archived

            // Multi-session & Registration Mode Configuration
            $table->boolean('is_multi_session')->default(false);
            $table->string('registration_mode', 20)->default('individual'); // individual, team, both
            $table->unsignedInteger('min_team_size')->default(1);
            $table->unsignedInteger('max_team_size')->default(5);
            $table->boolean('require_all_team_invites_accepted')->default(true);

            // Student Session Selection Rules
            $table->boolean('allow_multiple_sessions')->default(true);
            $table->unsignedInteger('max_sessions_per_student')->nullable();
            $table->boolean('require_session_selection')->default(true);
            $table->boolean('allow_session_change')->default(false);
            $table->boolean('require_separate_session_approval')->default(false);
            $table->boolean('require_parent_registration')->default(true);
            $table->boolean('allow_independent_session_registration')->default(false);
            $table->string('conflict_policy', 20)->default('block'); // block, warn, ignore

            // Overall Registration & Eligibility Rules
            $table->unsignedInteger('overall_capacity')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->boolean('waitlist_enabled')->default(false);
            $table->boolean('allow_submission_editing')->default(false);
            $table->boolean('allow_cancellation')->default(true);
            $table->boolean('allow_walk_in')->default(false);
            $table->json('eligible_programs')->nullable();
            $table->json('eligible_year_levels')->nullable();

            // Assigned Parent Registration Form & Version Snapshot
            $table->foreignUuid('registration_form_id')->nullable()->constrained('registration_forms')->nullOnDelete();
            $table->unsignedInteger('form_version')->nullable();
            $table->json('form_snapshot')->nullable();

            // Parent Completion & Rewards Configuration
            $table->string('completion_mode', 40)->default('any_session'); // any_session, all_selected_sessions, minimum_sessions, manual
            $table->unsignedInteger('min_sessions_for_completion')->default(1);
            $table->boolean('award_parent_rewards')->default(false);
            $table->unsignedInteger('parent_exp_reward')->default(0);
            $table->unsignedInteger('parent_points_reward')->default(0);

            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'category']);
            $table->index(['starts_at', 'ends_at']);
        });

        // 5. Activity Organizer Assignments
        Schema::create('activity_organizers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['activity_id', 'user_id']);
        });

        // 6. Activity Sessions / Components (Level 2)
        Schema::create('activity_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('track_or_category', 100)->nullable();
            $table->string('venue')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->dateTime('registration_opens_at')->nullable();
            $table->dateTime('registration_closes_at')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->boolean('waitlist_enabled')->default(false);
            $table->boolean('is_registration_open')->default(true);
            $table->boolean('requires_approval')->default(false);
            $table->boolean('allow_walk_in')->default(false);

            // Session-Specific Registration Form
            $table->foreignUuid('registration_form_id')->nullable()->constrained('registration_forms')->nullOnDelete();
            $table->unsignedInteger('form_version')->nullable();
            $table->json('form_snapshot')->nullable();

            // Attendance & Completion Requirements
            $table->boolean('attendance_enabled')->default(true);
            $table->boolean('qr_attendance_enabled')->default(true);
            $table->boolean('require_check_out')->default(true);
            $table->unsignedInteger('late_threshold_minutes')->nullable();
            $table->unsignedInteger('min_attendance_minutes')->nullable();
            $table->unsignedInteger('min_attendance_percentage')->nullable();
            $table->boolean('require_organizer_verification')->default(false);
            $table->boolean('require_output_submission')->default(false);

            // Session EXP & Points Rewards
            $table->unsignedInteger('exp_reward')->default(100);
            $table->unsignedInteger('points_reward')->default(10);

            $table->string('status', 30)->default('open'); // scheduled, open, ongoing, completed, cancelled
            $table->unsignedInteger('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['activity_id', 'sort_order']);
            $table->index(['starts_at', 'ends_at']);
        });

        // 7. Activity Teams (for Hackathons, IDEATHON, Team Competitions)
        Schema::create('activity_teams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->foreignUuid('captain_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('project_title')->nullable();
            $table->text('project_description')->nullable();
            $table->string('track')->nullable();
            $table->string('status', 30)->default('forming'); // forming, ready, submitted, approved, rejected, cancelled
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->unique(['activity_id', 'name']);
            $table->index(['activity_id', 'captain_user_id']);
        });

        // 8. Activity Team Members & Invitations
        Schema::create('activity_team_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('activity_team_id')->constrained('activity_teams')->cascadeOnDelete();
            $table->foreignUuid('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 20)->default('member'); // captain, member
            $table->string('invitation_status', 20)->default('pending'); // pending, accepted, rejected, removed
            $table->foreignUuid('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['activity_team_id', 'user_id']);
            $table->index(['activity_id', 'user_id', 'invitation_status'], 'act_team_member_lookup_idx');
        });

        // 9. Parent Activity Registrations
        Schema::create('activity_registrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('activity_team_id')->nullable()->constrained('activity_teams')->nullOnDelete();
            $table->string('registration_type', 30)->default('individual'); // individual, team, independent_session, walk_in
            $table->foreignUuid('registration_form_id')->nullable()->constrained('registration_forms')->nullOnDelete();
            $table->unsignedInteger('form_version')->nullable();
            $table->json('form_snapshot')->nullable();
            $table->string('ticket_code', 36)->unique();
            $table->string('status', 30)->default('pending'); // pending, approved, rejected, waitlisted, cancelled, withdrawn
            $table->string('completion_status', 30)->default('registered'); // not_started, registered, attended, completed, incomplete, absent
            $table->unsignedInteger('waitlist_position')->nullable();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->boolean('parent_rewards_awarded')->default(false);
            $table->unsignedInteger('parent_exp_awarded')->default(0);
            $table->unsignedInteger('parent_points_awarded')->default(0);
            $table->timestamp('parent_rewarded_at')->nullable();
            $table->timestamps();

            $table->unique(['activity_id', 'user_id']);
            $table->index(['activity_id', 'status']);
        });

        // 10. Session-Level Registrations & Independent Completion
        Schema::create('activity_session_registrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('activity_id')->constrained('activities', 'id', 'act_sess_reg_act_fk')->cascadeOnDelete();
            $table->foreignUuid('activity_session_id')->constrained('activity_sessions', 'id', 'act_sess_reg_sess_fk')->cascadeOnDelete();
            $table->foreignUuid('activity_registration_id')->constrained('activity_registrations', 'id', 'act_sess_reg_parent_fk')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users', 'id', 'act_sess_reg_user_fk')->cascadeOnDelete();
            $table->foreignUuid('activity_team_id')->nullable()->constrained('activity_teams', 'id', 'act_sess_reg_team_fk')->nullOnDelete();
            $table->foreignUuid('registration_form_id')->nullable()->constrained('registration_forms', 'id', 'act_sess_reg_form_fk')->nullOnDelete();
            $table->unsignedInteger('form_version')->nullable();
            $table->json('form_snapshot')->nullable();
            $table->string('status', 30)->default('pending'); // pending, approved, rejected, waitlisted, cancelled, withdrawn
            $table->string('attendance_status', 30)->default('not_recorded'); // not_recorded, checked_in, checked_out, present, late, absent
            $table->string('completion_status', 30)->default('registered'); // not_started, registered, attended, completed, incomplete, absent
            $table->unsignedInteger('waitlist_position')->nullable();

            // Completion Verification & Output
            $table->boolean('organizer_verified')->default(false);
            $table->foreignUuid('organizer_verified_by')->nullable()->constrained('users', 'id', 'act_sess_reg_ver_by_fk')->nullOnDelete();
            $table->timestamp('organizer_verified_at')->nullable();
            $table->boolean('output_submitted')->default(false);
            $table->text('output_notes')->nullable();

            // Review & Approval
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users', 'id', 'act_sess_reg_rev_by_fk')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Session Rewards Tracking
            $table->boolean('rewards_awarded')->default(false);
            $table->unsignedInteger('exp_awarded')->default(0);
            $table->unsignedInteger('points_awarded')->default(0);
            $table->timestamp('rewarded_at')->nullable();
            $table->timestamps();

            $table->unique(['activity_session_id', 'user_id'], 'unique_session_user_reg');
            $table->index(['activity_session_id', 'status'], 'act_sess_reg_status_idx');
            $table->index(['activity_session_id', 'completion_status'], 'act_sess_reg_completion_idx');
        });

        // 11. Activity Registration Dynamic Answers
        Schema::create('activity_registration_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('activity_registration_id')->constrained('activity_registrations', 'id', 'act_reg_ans_reg_fk')->cascadeOnDelete();
            $table->foreignUuid('activity_session_registration_id')->nullable()->constrained('activity_session_registrations', 'id', 'act_reg_ans_sess_reg_fk')->cascadeOnDelete();
            $table->foreignUuid('registration_form_field_id')->nullable()->constrained('registration_form_fields', 'id', 'act_reg_ans_field_fk')->nullOnDelete();
            $table->string('field_key', 100);
            $table->string('field_label');
            $table->string('question_type', 40);
            $table->text('answer_text')->nullable();
            $table->json('answer_json')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_original_name')->nullable();
            $table->string('file_mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();

            $table->index(['activity_registration_id', 'field_key'], 'act_reg_ans_key_idx');
        });

        // 12. Session-Specific QR Attendance Records
        Schema::create('activity_attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('activity_id')->constrained('activities', 'id', 'act_att_act_fk')->cascadeOnDelete();
            $table->foreignUuid('activity_session_id')->constrained('activity_sessions', 'id', 'act_att_sess_fk')->cascadeOnDelete();
            $table->foreignUuid('activity_session_registration_id')->nullable()->constrained('activity_session_registrations', 'id', 'act_att_sess_reg_fk')->nullOnDelete();
            $table->foreignUuid('user_id')->constrained('users', 'id', 'act_att_user_fk')->cascadeOnDelete();
            $table->string('qr_token_hash', 64)->nullable();
            $table->timestamp('check_in_at')->nullable();
            $table->timestamp('check_out_at')->nullable();
            $table->string('check_in_status', 20)->nullable(); // present, late
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->foreignUuid('checked_in_by')->nullable()->constrained('users', 'id', 'act_att_in_by_fk')->nullOnDelete();
            $table->foreignUuid('checked_out_by')->nullable()->constrained('users', 'id', 'act_att_out_by_fk')->nullOnDelete();
            $table->boolean('is_walk_in')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['activity_session_id', 'user_id'], 'unique_activity_session_attendance');
            $table->index(['activity_id', 'user_id'], 'act_att_act_user_idx');
        });

        // 13. Activity Reward Audit & Ledger Link
        Schema::create('activity_rewards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('activity_id')->constrained('activities', 'id', 'act_rew_act_fk')->cascadeOnDelete();
            $table->foreignUuid('activity_session_id')->nullable()->constrained('activity_sessions', 'id', 'act_rew_sess_fk')->cascadeOnDelete();
            $table->foreignUuid('activity_registration_id')->nullable()->constrained('activity_registrations', 'id', 'act_rew_reg_fk')->nullOnDelete();
            $table->foreignUuid('activity_session_registration_id')->nullable()->constrained('activity_session_registrations', 'id', 'act_rew_sess_reg_fk')->nullOnDelete();
            $table->foreignUuid('user_id')->constrained('users', 'id', 'act_rew_user_fk')->cascadeOnDelete();
            $table->string('reward_scope', 20)->default('session'); // session, parent
            $table->unsignedInteger('exp_amount')->default(0);
            $table->unsignedInteger('points_amount')->default(0);
            $table->foreignUuid('xp_transaction_id')->nullable()->constrained('xp_transactions', 'id', 'act_rew_xp_tx_fk')->nullOnDelete();
            $table->foreignUuid('point_transaction_id')->nullable()->constrained('point_transactions', 'id', 'act_rew_pt_tx_fk')->nullOnDelete();
            $table->string('idempotency_key')->unique();
            $table->foreignUuid('awarded_by')->nullable()->constrained('users', 'id', 'act_rew_by_fk')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'activity_id'], 'act_rew_user_act_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_rewards');
        Schema::dropIfExists('activity_attendances');
        Schema::dropIfExists('activity_registration_answers');
        Schema::dropIfExists('activity_session_registrations');
        Schema::dropIfExists('activity_registrations');
        Schema::dropIfExists('activity_team_members');
        Schema::dropIfExists('activity_teams');
        Schema::dropIfExists('activity_sessions');
        Schema::dropIfExists('activity_organizers');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('registration_form_options');
        Schema::dropIfExists('registration_form_fields');
        Schema::dropIfExists('registration_forms');
    }
};

