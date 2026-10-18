<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Season;
use App\Models\SeasonLeaderboardSnapshot;
use App\Models\SeasonReset;
use App\Services\SeasonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeasonManagementController extends Controller
{
    public function __construct(private SeasonService $seasonService) {}

    public function index(): View
    {
        $seasons = Season::withCount(['memberStats'])->orderByDesc('start_date')->get();
        $activeSeason = Season::active();
        $recentResets = SeasonReset::with(['closingSeason', 'nextSeason', 'executor'])->latest()->take(5)->get();

        return view('admin.seasons.index', [
            'seasons' => $seasons,
            'activeSeason' => $activeSeason,
            'recentResets' => $recentResets,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'academic_year_label' => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $isActive = $request->boolean('is_active');

        if ($isActive) {
            Season::where('is_active', true)->update(['is_active' => false]);
        }

        $season = Season::create([
            'name' => $validated['name'],
            'academic_year_label' => $validated['academic_year_label'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'is_active' => $isActive,
            'status' => $isActive ? 'active' : 'scheduled',
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'created_academic_season',
            'details' => json_encode([
                'season_id' => $season->id,
                'name' => $season->name,
                'is_active' => $isActive,
            ]),
        ]);

        return redirect()->route('admin.seasons.index')->with('success', "Season '{$season->name}' created.");
    }

    public function update(Request $request, Season $season): RedirectResponse
    {
        $actor = auth()->user();
        if (!$actor->isSuperAdmin() && !$actor->isComsocAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_active' => ['nullable', 'boolean'],
            'acknowledge_rankings_warning' => ['nullable', 'boolean'],
        ]);

        $startDateChanged = $season->start_date !== $validated['start_date'];
        $endDateChanged = $season->end_date !== $validated['end_date'];

        // Check if rankings/EXP already exist for the affected period
        if (($startDateChanged || $endDateChanged) && !$request->boolean('acknowledge_rankings_warning')) {
            $existingTxCount = \App\Models\XpTransaction::whereBetween('created_at', [
                min($season->start_date, $validated['start_date']) . ' 00:00:00',
                max($season->end_date, $validated['end_date']) . ' 23:59:59'
            ])->count();

            if ($existingTxCount > 0) {
                return back()->with('warning_needs_acknowledgement', [
                    'message' => "Warning: There are {$existingTxCount} existing EXP records within the affected date range. Modifying semester dates will alter active semester rankings. Please confirm you wish to proceed.",
                    'data' => $validated,
                ]);
            }
        }

        $oldValues = $season->only(['name', 'start_date', 'end_date', 'is_active']);

        $isActive = $request->boolean('is_active');
        if ($isActive && !$season->is_active) {
            Season::where('is_active', true)->update(['is_active' => false]);
        }

        $season->update([
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'is_active' => $isActive,
        ]);

        \App\Services\AuditLogger::log('semester.dates_updated', $season, $oldValues, [
            'name' => $season->name,
            'start_date' => $season->start_date,
            'end_date' => $season->end_date,
            'is_active' => $season->is_active,
            'updated_by' => $actor->name,
        ]);

        return redirect()->route('admin.seasons.index')->with('success', "Semester '{$season->name}' dates updated successfully and logged.");
    }

    public function previewReset(Season $season): View
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $preview = $this->seasonService->previewSeasonClose($season);
        $availableNextSeasons = Season::where('id', '!=', $season->id)
            ->where('is_active', false)
            ->orderBy('start_date')
            ->get();

        return view('admin.seasons.preview-reset', [
            'season' => $season,
            'preview' => $preview,
            'availableNextSeasons' => $availableNextSeasons,
        ]);
    }

    public function executeReset(Request $request, Season $season): RedirectResponse
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $request->validate([
            'next_season_id' => ['nullable', 'exists:seasons,id'],
            'confirm_name' => ['required', 'string'],
        ]);

        if (trim($request->input('confirm_name')) !== trim($season->name)) {
            return back()->with('error', "Confirmation failed: You must type '{$season->name}' exactly to proceed with the season reset.");
        }

        $nextSeason = $request->filled('next_season_id') ? Season::find($request->input('next_season_id')) : null;

        $reset = $this->seasonService->executeSeasonReset(
            closingSeason: $season,
            nextSeason: $nextSeason,
            executor: auth()->user()
        );

        return redirect()->route('admin.seasons.index')->with('success', "Season reset completed. Recorded final standing for {$reset->members_affected_count} members.");
    }

    public function showSnapshots(Season $season): View
    {
        $snapshots = SeasonLeaderboardSnapshot::with(['user.student'])
            ->where('season_id', $season->id)
            ->orderBy('final_rank')
            ->paginate(25);

        return view('admin.seasons.snapshots', [
            'season' => $season,
            'snapshots' => $snapshots,
        ]);
    }

    public function semesterReport(Request $request): View
    {
        $seasonId = $request->input('season_id');
        $reportData = $this->seasonService->getSemesterReportData($seasonId);

        return view('admin.seasons.semester-report', $reportData);
    }
}
