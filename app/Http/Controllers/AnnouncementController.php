<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementReceipt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $category = $request->input('category', 'all');
        $filter = $request->input('filter', 'all'); // all, unread, acknowledged

        $query = Announcement::active()
            ->with('poster')
            ->orderByDesc('is_pinned')
            ->latest('published_at');

        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        $allAnnouncements = $query->get();

        // Filter by audience eligibility
        $eligible = $allAnnouncements->filter(fn($ann) => $ann->isAudienceEligible($user))->values();

        // Get user receipts
        $receipts = AnnouncementReceipt::where('user_id', $user->id)
            ->whereIn('announcement_id', $eligible->pluck('id'))
            ->get()
            ->keyBy('announcement_id');

        if ($filter === 'unread') {
            $eligible = $eligible->filter(fn($ann) => !isset($receipts[$ann->id]) || is_null($receipts[$ann->id]->read_at));
        } elseif ($filter === 'acknowledged') {
            $eligible = $eligible->filter(fn($ann) => isset($receipts[$ann->id]) && !is_null($receipts[$ann->id]->acknowledged_at));
        }

        // Simple manual paginator for collection
        $page = $request->input('page', 1);
        $perPage = 10;
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $eligible->forPage($page, $perPage),
            $eligible->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('announcements.index', [
            'announcements' => $paginated,
            'selectedCategory' => $category,
            'filter' => $filter,
            'categories' => Announcement::CATEGORIES,
            'receipts' => $receipts,
        ]);
    }

    public function show(Request $request, Announcement $announcement): View
    {
        $user = $request->user();

        if (!$announcement->is_active || ($announcement->expires_at && $announcement->expires_at->isPast())) {
            if (!$user || !$user->isAdmin()) {
                abort(404);
            }
        }

        // Record read receipt safely
        $receipt = null;
        if ($user) {
            $receipt = AnnouncementReceipt::firstOrCreate(
                [
                    'announcement_id' => $announcement->id,
                    'user_id' => $user->id,
                ],
                [
                    'read_at' => now(),
                ]
            );

            if (is_null($receipt->read_at)) {
                $receipt->update(['read_at' => now()]);
            }
        }

        return view('announcements.show', [
            'announcement' => $announcement,
            'receipt' => $receipt,
        ]);
    }

    public function acknowledge(Request $request, Announcement $announcement): RedirectResponse
    {
        $user = $request->user();

        if (!$announcement->requires_acknowledgment) {
            return back()->with('status', 'Announcement does not require acknowledgment.');
        }

        $receipt = AnnouncementReceipt::firstOrCreate(
            [
                'announcement_id' => $announcement->id,
                'user_id' => $user->id,
            ],
            [
                'read_at' => now(),
                'acknowledged_at' => now(),
            ]
        );

        if (is_null($receipt->acknowledged_at)) {
            $receipt->update([
                'read_at' => $receipt->read_at ?? now(),
                'acknowledged_at' => now(),
            ]);
        }

        return back()->with('status', 'Thank you! You have formally acknowledged this official announcement.');
    }
}
