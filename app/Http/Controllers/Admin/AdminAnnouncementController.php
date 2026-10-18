<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementReceipt;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Program;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->input('status', 'all');
        $category = $request->input('category', 'all');

        $query = Announcement::with(['poster', 'event'])
            ->withCount('receipts')
            ->latest('created_at');

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'archived') {
            $query->where('is_active', false);
        }

        if ($category !== 'all') {
            $query->where('category', $category);
        }

        $announcements = $query->paginate(15)->withQueryString();
        $events = Event::where('is_published', true)->orderByDesc('event_date')->take(20)->get();
        $programs = Program::where('is_active', true)->get();

        return view('admin.announcements.index', [
            'announcements' => $announcements,
            'status' => $status,
            'category' => $category,
            'priorities' => Announcement::PRIORITIES,
            'categories' => Announcement::CATEGORIES,
            'events' => $events,
            'programs' => $programs,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Normalize target_audience_values: handle comma-separated string, array of strings/nulls, and empty values
        if ($request->has('target_audience_values')) {
            $rawValues = $request->input('target_audience_values');
            $parsedValues = [];

            if (is_string($rawValues)) {
                $parsedValues = array_map('trim', explode(',', $rawValues));
            } elseif (is_array($rawValues)) {
                foreach ($rawValues as $val) {
                    if (is_string($val)) {
                        foreach (explode(',', $val) as $part) {
                            $trimmed = trim($part);
                            if ($trimmed !== '') {
                                $parsedValues[] = $trimmed;
                            }
                        }
                    } elseif (is_numeric($val)) {
                        $parsedValues[] = (string) $val;
                    }
                }
            }

            $request->merge([
                'target_audience_values' => array_values(array_unique(array_filter($parsedValues, fn($v) => $v !== null && $v !== ''))),
            ]);
        } else {
            $request->merge(['target_audience_values' => []]);
        }

        // If audience type is 'all' or empty, default to empty values
        if ($request->input('target_audience_type') === 'all' || empty($request->input('target_audience_type'))) {
            $request->merge([
                'target_audience_type' => 'all',
                'target_audience_values' => [],
            ]);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'priority' => ['required', 'string', 'in:low,normal,high,urgent'],
            'category' => ['required', 'string', 'in:' . implode(',', array_keys(Announcement::CATEGORIES))],
            'target_url' => ['nullable', 'url', 'max:500'],
            'target_audience_type' => ['nullable', 'string', 'in:all,program,year_level,role'],
            'target_audience_values' => ['nullable', 'array'],
            'target_audience_values.*' => ['string'],
            'requires_acknowledgment' => ['nullable', 'boolean'],
            'is_pinned' => ['nullable', 'boolean'],
            'event_id' => ['nullable', 'uuid', 'exists:events,id'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:published_at'],
            'is_active' => ['nullable', 'boolean'],
            'notify_members' => ['nullable', 'boolean'],
        ]);

        $announcement = Announcement::create([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'priority' => $validated['priority'],
            'category' => $validated['category'],
            'target_url' => $validated['target_url'] ?? null,
            'target_audience_type' => $validated['target_audience_type'] ?? 'all',
            'target_audience_values' => $validated['target_audience_values'] ?? [],
            'requires_acknowledgment' => $request->boolean('requires_acknowledgment'),
            'is_pinned' => $request->boolean('is_pinned'),
            'event_id' => $validated['event_id'] ?? null,
            'published_at' => $validated['published_at'] ?? now(),
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => $request->boolean('is_active', true),
            'posted_by' => $request->user()->id,
        ]);

        if ($request->boolean('notify_members')) {
            $users = User::where('role', 'student')->with('student')->get();
            $eligibleUsers = $users->filter(fn($u) => $announcement->isAudienceEligible($u));
            
            NotificationService::broadcast(
                $eligibleUsers,
                'announcement',
                'Announcement: ' . $announcement->title,
                \Illuminate\Support\Str::limit(strip_tags($announcement->body), 150),
                route('announcements.show', $announcement)
            );
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'created_announcement',
            'details' => json_encode([
                'announcement_id' => $announcement->id,
                'title' => $announcement->title,
                'priority' => $announcement->priority,
                'audience_type' => $announcement->target_audience_type,
            ]),
        ]);

        return redirect()->route('admin.announcements.index')->with('success', 'Official Announcement published successfully.');
    }

    public function show(Announcement $announcement): View
    {
        $announcement->load(['poster', 'event']);
        
        // Compute read receipts and acknowledgments
        $receipts = AnnouncementReceipt::where('announcement_id', $announcement->id)
            ->with(['user.student'])
            ->latest('read_at')
            ->paginate(25);

        $readCount = AnnouncementReceipt::where('announcement_id', $announcement->id)->whereNotNull('read_at')->count();
        $acknowledgedCount = AnnouncementReceipt::where('announcement_id', $announcement->id)->whereNotNull('acknowledged_at')->count();
        $totalEligibleMembers = User::where('role', 'student')->count();

        return view('admin.announcements.show', compact('announcement', 'receipts', 'readCount', 'acknowledgedCount', 'totalEligibleMembers'));
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        // Normalize target_audience_values: handle comma-separated string, array of strings/nulls, and empty values
        if ($request->has('target_audience_values')) {
            $rawValues = $request->input('target_audience_values');
            $parsedValues = [];

            if (is_string($rawValues)) {
                $parsedValues = array_map('trim', explode(',', $rawValues));
            } elseif (is_array($rawValues)) {
                foreach ($rawValues as $val) {
                    if (is_string($val)) {
                        foreach (explode(',', $val) as $part) {
                            $trimmed = trim($part);
                            if ($trimmed !== '') {
                                $parsedValues[] = $trimmed;
                            }
                        }
                    } elseif (is_numeric($val)) {
                        $parsedValues[] = (string) $val;
                    }
                }
            }

            $request->merge([
                'target_audience_values' => array_values(array_unique(array_filter($parsedValues, fn($v) => $v !== null && $v !== ''))),
            ]);
        } else {
            $request->merge(['target_audience_values' => []]);
        }

        // If audience type is 'all' or empty, default to empty values
        if ($request->input('target_audience_type') === 'all' || empty($request->input('target_audience_type'))) {
            $request->merge([
                'target_audience_type' => 'all',
                'target_audience_values' => [],
            ]);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'priority' => ['required', 'string', 'in:low,normal,high,urgent'],
            'category' => ['required', 'string', 'in:' . implode(',', array_keys(Announcement::CATEGORIES))],
            'target_url' => ['nullable', 'url', 'max:500'],
            'target_audience_type' => ['required', 'string', 'in:all,program,year_level,role'],
            'target_audience_values' => ['nullable', 'array'],
            'target_audience_values.*' => ['string'],
            'requires_acknowledgment' => ['nullable', 'boolean'],
            'is_pinned' => ['nullable', 'boolean'],
            'event_id' => ['nullable', 'uuid', 'exists:events,id'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:published_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $announcement->update([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'priority' => $validated['priority'],
            'category' => $validated['category'],
            'target_url' => $validated['target_url'] ?? null,
            'target_audience_type' => $validated['target_audience_type'],
            'target_audience_values' => $validated['target_audience_values'] ?? [],
            'requires_acknowledgment' => $request->boolean('requires_acknowledgment'),
            'is_pinned' => $request->boolean('is_pinned'),
            'event_id' => $validated['event_id'] ?? null,
            'published_at' => $validated['published_at'] ?? $announcement->published_at,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'updated_announcement',
            'details' => json_encode([
                'announcement_id' => $announcement->id,
                'title' => $announcement->title,
            ]),
        ]);

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement updated successfully.');
    }

    public function toggleActive(Announcement $announcement): RedirectResponse
    {
        $announcement->update([
            'is_active' => !$announcement->is_active,
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'toggled_announcement_active',
            'details' => json_encode([
                'announcement_id' => $announcement->id,
                'is_active' => $announcement->is_active,
            ]),
        ]);

        $state = $announcement->is_active ? 'activated' : 'archived';
        return redirect()->route('admin.announcements.index')->with('success', "Announcement has been {$state}.");
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $title = $announcement->title;
        $announcement->delete();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'deleted_announcement',
            'details' => json_encode([
                'title' => $title,
            ]),
        ]);

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement deleted successfully.');
    }
}
