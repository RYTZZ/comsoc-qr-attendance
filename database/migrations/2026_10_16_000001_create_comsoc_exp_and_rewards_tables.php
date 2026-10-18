<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Extend Users table for Member / Organization roster & EXP preferences
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'student_number')) {
                $table->string('student_number')->nullable()->unique()->after('username');
            }
            if (!Schema::hasColumn('users', 'nickname')) {
                $table->string('nickname')->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'preferred_email')) {
                $table->string('preferred_email')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'is_eligible')) {
                $table->boolean('is_eligible')->default(true)->after('is_active');
            }
            if (!Schema::hasColumn('users', 'can_collect_rewards')) {
                $table->boolean('can_collect_rewards')->default(false)->after('role');
            }
        });

        // 2. Event Organizer Assignments (Many-to-Many between Events & Users)
        if (!Schema::hasTable('event_organizer_assignments')) {
            Schema::create('event_organizer_assignments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
                $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignUuid('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['event_id', 'user_id']);
            });
        }

        // 3. Dynamic QR validation tokens (for rolling 30-second single-use validation)
        if (!Schema::hasTable('dynamic_qr_tokens')) {
            Schema::create('dynamic_qr_tokens', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('token_hash', 64)->unique();
                $table->timestamp('expires_at');
                $table->boolean('is_consumed')->default(false);
                $table->timestamp('consumed_at')->nullable();
                $table->foreignUuid('consumed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['user_id', 'expires_at']);
            });
        }

        // 4. EXP Credits table (Authoritative backing for FEFO Spendable EXP & Expirations)
        if (!Schema::hasTable('exp_credits')) {
            Schema::create('exp_credits', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
                $table->integer('initial_amount');
                $table->integer('remaining_amount');
                $table->timestamp('expires_at')->nullable(); // null means non-expiring
                $table->string('source_type'); // 'attendance', 'mission', 'task', 'contribution', 'adjustment'
                $table->string('source_id')->nullable();
                $table->string('status')->default('active'); // active, exhausted, expired, reversed
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['user_id', 'status', 'expires_at']);
                $table->index(['source_type', 'source_id']);
            });
        }

        // 5. EXP Allocations table (Tracks exactly which credits were consumed by which redemptions for FEFO)
        if (!Schema::hasTable('exp_allocations')) {
            Schema::create('exp_allocations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('reward_redemption_id')->constrained('reward_redemptions')->cascadeOnDelete();
                $table->foreignUuid('exp_credit_id')->constrained('exp_credits')->cascadeOnDelete();
                $table->integer('amount_allocated');
                $table->timestamps();
                $table->index(['reward_redemption_id', 'exp_credit_id']);
            });
        }

        // 6. Update EXP Transactions table for 2-step approvals, reversals, and lifetime vs spendable separation
        Schema::table('xp_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('xp_transactions', 'spendable_delta')) {
                $table->integer('spendable_delta')->default(0)->after('amount');
            }
            if (!Schema::hasColumn('xp_transactions', 'lifetime_delta')) {
                $table->integer('lifetime_delta')->default(0)->after('spendable_delta');
            }
            if (!Schema::hasColumn('xp_transactions', 'exp_credit_id')) {
                $table->foreignUuid('exp_credit_id')->nullable()->after('user_id')->constrained('exp_credits')->nullOnDelete();
            }
            if (!Schema::hasColumn('xp_transactions', 'is_reversal')) {
                $table->boolean('is_reversal')->default(false)->after('activity_type');
            }
            if (!Schema::hasColumn('xp_transactions', 'reversal_reason')) {
                $table->text('reversal_reason')->nullable()->after('is_reversal');
            }
            if (!Schema::hasColumn('xp_transactions', 'second_approved_by')) {
                $table->foreignUuid('second_approved_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('xp_transactions', 'second_approved_at')) {
                $table->timestamp('second_approved_at')->nullable()->after('second_approved_by');
            }
        });

        // 7. Update Reward Redemptions for unique claim code, collection staff & FEFO tracking
        Schema::table('reward_redemptions', function (Blueprint $table) {
            if (!Schema::hasColumn('reward_redemptions', 'claim_code')) {
                $table->string('claim_code', 32)->nullable()->unique()->after('points_spent');
            }
            if (!Schema::hasColumn('reward_redemptions', 'collected_by')) {
                $table->foreignUuid('collected_by')->nullable()->after('fulfilled_by')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('reward_redemptions', 'collected_at')) {
                $table->timestamp('collected_at')->nullable()->after('collected_by');
            }
        });

        // 8. Update Attendance Records for EXP earned & duration validation
        Schema::table('attendance_records', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_records', 'calculated_exp')) {
                $table->integer('calculated_exp')->default(0)->after('status');
            }
            if (!Schema::hasColumn('attendance_records', 'user_id')) {
                $table->foreignUuid('user_id')->nullable()->after('participant_id')->constrained('users')->cascadeOnDelete();
            }
        });

        // 9. Update Events table for EXP formulas & expiration policy
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'exp_formula_type')) {
                $table->string('exp_formula_type')->default('fixed')->after('reward_points'); // fixed, duration_based
            }
            if (!Schema::hasColumn('events', 'exp_base_amount')) {
                $table->integer('exp_base_amount')->default(50)->after('exp_formula_type');
            }
            if (!Schema::hasColumn('events', 'exp_duration_rate')) {
                $table->integer('exp_duration_rate')->default(10)->after('exp_base_amount'); // EXP per hour
            }
            if (!Schema::hasColumn('events', 'late_penalty_percent')) {
                $table->integer('late_penalty_percent')->default(20)->after('exp_duration_rate'); // % deducted if late
            }
            if (!Schema::hasColumn('events', 'exp_expiration_days')) {
                $table->integer('exp_expiration_days')->nullable()->after('late_penalty_percent'); // null = non-expiring
            }
        });

        // 10. Email Notifications and Delivery Log Table
        if (!Schema::hasTable('email_delivery_logs')) {
            Schema::create('email_delivery_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('notification_type'); // 'redemption_claim', 'collection_confirmed', 'expiry_7d', 'expiry_1d', etc.
                $table->string('recipient_email');
                $table->string('subject');
                $table->string('status')->default('sent'); // 'sent', 'failed'
                $table->text('error_message')->nullable();
                $table->string('reference_id')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'notification_type', 'created_at']);
            });
        }

        // 11. User Notification Preferences Table
        if (!Schema::hasTable('user_notification_preferences')) {
            Schema::create('user_notification_preferences', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
                $table->boolean('notify_expiration_7d')->default(true);
                $table->boolean('notify_expiration_1d')->default(true);
                $table->boolean('notify_redemption')->default(true);
                $table->boolean('notify_achievements')->default(true);
                $table->boolean('notify_events')->default(true);
                $table->timestamps();
                $table->unique('user_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_preferences');
        Schema::dropIfExists('email_delivery_logs');
        Schema::dropIfExists('exp_allocations');
        Schema::dropIfExists('exp_credits');
        Schema::dropIfExists('dynamic_qr_tokens');
        Schema::dropIfExists('event_organizer_assignments');
    }
};
