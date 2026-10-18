<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Raffle;
use App\Models\RaffleWinner;
use App\Services\AuditLogger;
use App\Services\RaffleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RaffleController extends Controller
{
    public function __construct(
        protected RaffleService $raffleService
    ) {}

    public function index(Request $request): View
    {
        $raffles = Raffle::with(['event', 'reward', 'creator', 'drawer', 'winners.user', 'entries'])
            ->when($request->event_id, fn($q) => $q->where('event_id', $request->event_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $events = Event::orderByDesc('event_date')->get();
        $rewards = \App\Models\Reward::where('is_active', true)->where('is_archived', false)->get();

        return view('admin.raffles.index', compact('raffles', 'events', 'rewards'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'event_id' => 'required|exists:events,id',
            'reward_id' => 'nullable|exists:rewards,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'prize_name' => 'required|string|max:255',
            'points_bonus' => 'required|integer|min:0|max:100000',
            'winner_count' => 'required|integer|min:1|max:50',
            'entry_type' => 'required|in:attendance,points_purchase,tickets',
            'points_per_entry' => 'nullable|integer|min:0|max:50000',
            'max_entries_per_user' => 'required|integer|min:1|max:50',
        ]);

        $event = Event::findOrFail($data['event_id']);
        $raffle = $this->raffleService->createRaffle($event, $data, auth()->user());

        // Pre-populate entries if attendance mode
        if ($raffle->entry_type === 'attendance') {
            $this->raffleService->syncEntriesFromAttendance($raffle);
        }

        AuditLogger::log('raffle.created', $raffle, [], $raffle->toArray(), 'Created event raffle');

        return back()->with('success', "Raffle '{$raffle->title}' created successfully.");
    }

    public function show(Raffle $raffle): View
    {
        $raffle->load(['event', 'creator', 'drawer', 'entries.user', 'entries.attendanceRecord', 'winners.user', 'winners.claimedByStaff']);

        return view('admin.raffles.show', compact('raffle'));
    }

    public function draw(Raffle $raffle): RedirectResponse
    {
        try {
            $winners = $this->raffleService->executeDraw($raffle, auth()->user());

            AuditLogger::log('raffle.drawn', $raffle, ['status' => 'pending'], [
                'status' => 'drawn',
                'winner_count' => count($winners),
                'drawn_by' => auth()->id(),
            ], 'Executed random raffle draw among verified attendees');

            return back()->with('success', 'Raffle draw completed successfully! Winners have been recorded.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function claim(Request $request, RaffleWinner $winner): RedirectResponse
    {
        $request->validate([
            'notes' => 'nullable|string|max:255',
        ]);

        $this->raffleService->claimPrize($winner, auth()->user(), $request->input('notes'));

        AuditLogger::log('raffle.prize_claimed', $winner, ['is_claimed' => false], [
            'is_claimed' => true,
            'claimed_by' => auth()->id(),
        ], 'Raffle prize claimed by student');

        return back()->with('success', 'Prize marked as claimed.');
    }
}
