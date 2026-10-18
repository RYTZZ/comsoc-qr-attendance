<?php

namespace App\Http\Controllers;

use App\Models\PointTransaction;
use App\Models\XpTransaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberLedgerController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $student = $user->student;

        $tab = $request->query('tab', 'xp');

        $activeSeason = \App\Models\Season::active() ?: \App\Models\Season::latest('start_date')->first();

        // Authoritative Balances from Ledger & Credit Store
        $spendableExp = $user->getSpendableExp();
        $lifetimeExp = $user->getLifetimeExp();

        // Level based strictly on Lifetime EXP (ordinary redemptions do not decrease level)
        $currentLevel = \App\Models\Level::forXp($lifetimeExp);
        $nextLevel = \App\Models\Level::where('min_xp', '>', $lifetimeExp)->orderBy('min_xp')->first();
        $levelProgress = 100;
        if ($nextLevel) {
            $prevMin = $currentLevel->min_xp;
            $span = max(1, $nextLevel->min_xp - $prevMin);
            $currentInSpan = max(0, $lifetimeExp - $prevMin);
            $levelProgress = min(100, round(($currentInSpan / $span) * 100));
        }

        $allLevels = \App\Models\Level::orderBy('level')->get();

        // Upcoming expiring credits for Member dashboard awareness
        $expiringSoon = \App\Models\ExpCredit::where('user_id', $user->id)
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays(7))
            ->where('remaining_amount', '>', 0)
            ->sum('remaining_amount');

        if ($tab === 'points') {
            $transactions = PointTransaction::where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->paginate(15)
                ->withQueryString();
        } else {
            $transactions = XpTransaction::where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->paginate(15)
                ->withQueryString();
        }

        return view('ledger.index', compact(
            'tab',
            'spendableExp',
            'lifetimeExp',
            'expiringSoon',
            'currentLevel',
            'nextLevel',
            'levelProgress',
            'allLevels',
            'transactions',
            'activeSeason'
        ));
    }
}
