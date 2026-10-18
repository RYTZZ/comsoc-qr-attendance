<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\ExpCredit;
use App\Models\Level;
use App\Models\PointTransaction;
use App\Models\Season;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StudentExpPointsController extends Controller
{
    public const REASONS = [
        'event_attendance' => 'Event Attendance',
        'event_participation' => 'Event Participation',
        'volunteering' => 'Volunteering & Community Service',
        'winning_challenge' => 'Winning a Challenge / Hackathon',
        'merit_achievement' => 'Academic / Project Merit Achievement',
        'administrative_correction' => 'Administrative Correction',
    ];

    public function index(Request $request): View
    {
        $actor = $request->user();
        if (!$actor->isSuperAdmin()) {
            abort(403, 'Unauthorized: Manual EXP & Points management is exclusively restricted to Super Administrators.');
        }

        $search = trim($request->input('search', ''));
        $selectedUserId = $request->input('user_id');

        $studentsQuery = User::whereIn('role', ['member', 'student'])
            ->with(['student', 'seasonStats']);

        if (!empty($search)) {
            $studentsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('student_number', 'like', "%{$search}%")
                  ->orWhere('member_id', 'like', "%{$search}%")
                  ->orWhereHas('student', function ($sq) use ($search) {
                      $sq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('student_id', 'like', "%{$search}%");
                  });
            });
        }

        $students = $studentsQuery->orderBy('name')->paginate(12)->withQueryString();

        $selectedStudent = null;
        $recentTransactions = collect();

        if ($selectedUserId) {
            $selectedStudent = User::with(['student', 'seasonStats'])->find($selectedUserId);
            if ($selectedStudent) {
                // Fetch unified transaction history
                $recentTransactions = XpTransaction::where('user_id', $selectedStudent->id)
                    ->with('creator')
                    ->latest()
                    ->take(25)
                    ->get();
            }
        } elseif ($students->isNotEmpty() && empty($search)) {
            $selectedStudent = $students->first();
            $recentTransactions = XpTransaction::where('user_id', $selectedStudent->id)
                ->with('creator')
                ->latest()
                ->take(25)
                ->get();
        }

        $events = Event::orderByDesc('event_date')->get();

        return view('admin.students.exp-points', [
            'students' => $students,
            'selectedStudent' => $selectedStudent,
            'recentTransactions' => $recentTransactions,
            'search' => $search,
            'events' => $events,
            'reasons' => self::REASONS,
        ]);
    }

    public function adjust(Request $request): RedirectResponse
    {
        $actor = $request->user();
        if (!$actor->isSuperAdmin()) {
            abort(403, 'Unauthorized: Only Super Administrators can distribute manual EXP and points.');
        }

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'award_type' => ['required', 'in:award,deduct'],
            'exp_amount' => ['required', 'integer', 'min:0', 'max:50000'],
            'points_amount' => ['required', 'integer', 'min:0', 'max:50000'],
            'reason_category' => ['required', 'string', 'in:' . implode(',', array_keys(self::REASONS))],
            'custom_reason' => ['nullable', 'string', 'max:500'],
            'event_id' => ['nullable', 'exists:events,id'],
            'idempotency_key' => ['required', 'string', 'max:100'],
        ]);

        $targetUser = User::findOrFail($validated['user_id']);

        if ($targetUser->id === $actor->id) {
            return back()->with('error', 'Conflict of interest: You cannot award or deduct your own EXP or points balance.');
        }

        // Idempotency check: prevent duplicate transactions caused by repeated submissions or retries
        $existingTx = XpTransaction::where('idempotency_key', $validated['idempotency_key'])->first();
        if ($existingTx) {
            return back()->with('error', 'Duplicate transaction detected. This adjustment was already processed.');
        }

        $isAward = $validated['award_type'] === 'award';
        $expDelta = $isAward ? (int)$validated['exp_amount'] : -(int)$validated['exp_amount'];
        $pointsDelta = $isAward ? (int)$validated['points_amount'] : -(int)$validated['points_amount'];

        if ($expDelta === 0 && $pointsDelta === 0) {
            return back()->with('error', 'Please enter a non-zero amount for EXP, raffle/redeem points, or both.');
        }

        // Validate negative balances are prohibited
        $currentLifetimeExp = $targetUser->getLifetimeExp();
        $currentSpendablePoints = $targetUser->getSpendableExp();

        if ($expDelta < 0 && ($currentLifetimeExp + $expDelta) < 0) {
            return back()->with('error', "Adjustment rejected: EXP deduction exceeds the student's current lifetime EXP ({$currentLifetimeExp} EXP).");
        }

        if ($pointsDelta < 0 && ($currentSpendablePoints + $pointsDelta) < 0) {
            return back()->with('error', "Adjustment rejected: Points deduction exceeds the student's available points balance ({$currentSpendablePoints} pts).");
        }

        $reasonText = self::REASONS[$validated['reason_category']] ?? $validated['reason_category'];
        if (!empty($validated['custom_reason'])) {
            $reasonText .= ': ' . trim($validated['custom_reason']);
        }

        $associatedEvent = !empty($validated['event_id']) ? Event::find($validated['event_id']) : null;
        if ($associatedEvent) {
            $reasonText .= " [Event: {$associatedEvent->name}]";
        }

        DB::transaction(function () use (
            $targetUser,
            $actor,
            $expDelta,
            $pointsDelta,
            $reasonText,
            $validated,
            $associatedEvent
        ) {
            $activeSeason = Season::active();

            // 1. If EXP delta > 0, create atomic ExpCredit; if < 0, deduct from active credits
            $credit = null;
            if ($expDelta > 0) {
                $credit = ExpCredit::create([
                    'user_id' => $targetUser->id,
                    'initial_amount' => $expDelta,
                    'remaining_amount' => $expDelta,
                    'expires_at' => null,
                    'source_type' => 'super_admin_manual',
                    'source_id' => $associatedEvent?->id,
                    'status' => 'active',
                    'created_by' => $actor->id,
                ]);
            } elseif ($expDelta < 0) {
                $toDeduct = abs($expDelta);
                $credits = ExpCredit::where('user_id', $targetUser->id)
                    ->where('status', 'active')
                    ->where('remaining_amount', '>', 0)
                    ->orderBy('created_at', 'desc')
                    ->get();

                foreach ($credits as $c) {
                    if ($toDeduct <= 0) break;
                    $deduct = min($c->remaining_amount, $toDeduct);
                    $c->remaining_amount -= $deduct;
                    if ($c->remaining_amount === 0) {
                        $c->status = 'exhausted';
                    }
                    $c->save();
                    $toDeduct -= $deduct;
                }
            }

            // 2. Authoritative ledger entry for EXP & Points
            $tx = XpTransaction::create([
                'user_id' => $targetUser->id,
                'season_id' => $activeSeason?->id,
                'exp_credit_id' => $credit?->id,
                'amount' => $expDelta,
                'spendable_delta' => $pointsDelta,
                'lifetime_delta' => $expDelta,
                'activity_type' => 'manual_distribution',
                'source_type' => $associatedEvent ? Event::class : 'super_admin_adjustment',
                'source_id' => $associatedEvent?->id,
                'description' => $reasonText,
                'idempotency_key' => $validated['idempotency_key'],
                'created_by' => $actor->id,
            ]);

            // 3. Mirror to point_transactions ledger
            if ($pointsDelta !== 0) {
                PointTransaction::create([
                    'user_id' => $targetUser->id,
                    'season_id' => $activeSeason?->id,
                    'amount' => $pointsDelta,
                    'transaction_type' => $pointsDelta > 0 ? 'super_admin_award' : 'super_admin_deduct',
                    'source_type' => $associatedEvent ? Event::class : 'super_admin_adjustment',
                    'source_id' => $associatedEvent?->id,
                    'description' => $reasonText,
                    'idempotency_key' => $validated['idempotency_key'] . '_pts',
                    'created_by' => $actor->id,
                ]);
            }

            // 4. Update member season stats if active
            if ($activeSeason) {
                $stat = \App\Models\MemberSeasonStat::firstOrCreate(
                    ['user_id' => $targetUser->id, 'season_id' => $activeSeason->id],
                    ['season_xp' => 0, 'redeemable_points' => 0, 'current_level' => 1]
                );

                if ($expDelta !== 0) {
                    $stat->increment('season_xp', $expDelta);
                }
                if ($pointsDelta !== 0) {
                    $stat->increment('redeemable_points', $pointsDelta);
                }
            }

            // 5. Audit Log
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'super_admin_exp_points_distribution',
                'details' => json_encode([
                    'target_user_id' => $targetUser->id,
                    'target_user_name' => $targetUser->name,
                    'exp_delta' => $expDelta,
                    'points_delta' => $pointsDelta,
                    'reason' => $reasonText,
                    'event_id' => $associatedEvent?->id,
                    'event_name' => $associatedEvent?->name,
                    'idempotency_key' => $validated['idempotency_key'],
                ]),
            ]);

            // 6. Notify student
            $expStr = ($expDelta >= 0 ? '+' : '') . $expDelta . ' EXP';
            $ptsStr = ($pointsDelta >= 0 ? '+' : '') . $pointsDelta . ' Points';
            NotificationService::send(
                $targetUser,
                'balance_adjusted',
                "Official Balance Adjustment ({$expStr}, {$ptsStr})",
                "Super Admin updated your account balance: {$expStr}, {$ptsStr}. Reason: {$reasonText}",
                route('ledger.index')
            );
        });

        $expLabel = ($expDelta >= 0 ? '+' : '') . $expDelta . ' EXP';
        $ptsLabel = ($pointsDelta >= 0 ? '+' : '') . $pointsDelta . ' Points';

        return redirect()->route('admin.students.exp-points.index', ['user_id' => $targetUser->id])
            ->with('success', "Successfully applied {$expLabel} and {$ptsLabel} to {$targetUser->name}.");
    }
}
