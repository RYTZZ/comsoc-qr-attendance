<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\MissionSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberMissionController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();

        $tab = $request->query('tab', 'available');
        $category = $request->query('category');
        $search = $request->query('search');

        $userSubmissions = MissionSubmission::where('user_id', $user->id)
            ->get()
            ->keyBy('mission_id');

        $query = Mission::where('status', 'published');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereLike('title', "%{$search}%")
                  ->orWhereLike('description', "%{$search}%")
                  ->orWhereLike('objectives', "%{$search}%");
            });
        }

        if ($category) {
            $query->where('category', $category);
        }

        $allMissions = $query->orderBy('due_date', 'asc')->get();

        if ($tab === 'in_progress') {
            $missions = $allMissions->filter(function ($m) use ($userSubmissions) {
                return isset($userSubmissions[$m->id]) && in_array($userSubmissions[$m->id]->status, ['pending', 'in_progress']);
            });
        } elseif ($tab === 'completed') {
            $missions = $allMissions->filter(function ($m) use ($userSubmissions) {
                return isset($userSubmissions[$m->id]) && $userSubmissions[$m->id]->status === 'approved';
            });
        } elseif ($tab === 'rejected') {
            $missions = $allMissions->filter(function ($m) use ($userSubmissions) {
                return isset($userSubmissions[$m->id]) && $userSubmissions[$m->id]->status === 'rejected';
            });
        } else {
            $missions = $allMissions;
        }

        $categories = Mission::where('status', 'published')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values();

        return view('missions.index', compact(
            'missions',
            'userSubmissions',
            'tab',
            'categories',
            'category',
            'search'
        ));
    }

    public function show(Mission $mission): View
    {
        abort_unless($mission->status === 'published', 404);

        $user = auth()->user();
        $submission = MissionSubmission::where('mission_id', $mission->id)
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        return view('missions.show', compact('mission', 'submission'));
    }

    public function submit(Request $request, Mission $mission): RedirectResponse
    {
        abort_unless($mission->status === 'published', 404);

        $user = auth()->user();

        $existing = MissionSubmission::where('mission_id', $mission->id)
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        if ($existing && $existing->status === 'approved') {
            return back()->with('error', 'You have already completed this mission.');
        }

        if ($existing && $existing->status === 'pending') {
            return back()->with('error', 'Your submission is already under review.');
        }

        if ($existing && $existing->status === 'rejected' && !$mission->allow_resubmission) {
            return back()->with('error', 'Resubmissions are not permitted for this mission.');
        }

        $rules = [
            'submission_text' => ['nullable', 'string', 'max:5000'],
            'proof_url' => ['nullable', 'url', 'max:500'],
            'proof_file' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,zip', 'max:10240'],
        ];

        if ($mission->submission_type === 'link') {
            $rules['proof_url'] = ['required', 'url', 'max:500'];
        } elseif ($mission->submission_type === 'file') {
            $rules['proof_file'] = ['required', 'file', 'mimes:pdf,png,jpg,jpeg,zip', 'max:10240'];
        } else {
            $rules['submission_text'] = ['required', 'string', 'max:5000'];
        }

        $validated = $request->validate($rules);

        $filePath = null;
        if ($request->hasFile('proof_file')) {
            $filePath = $request->file('proof_file')->store(MissionSubmission::PROOF_DIRECTORY, MissionSubmission::PROOF_DISK);
        }

        try {
            MissionSubmission::create([
                'mission_id' => $mission->id,
                'user_id' => $user->id,
                'submission_text' => $validated['submission_text'] ?? null,
                'proof_url' => $validated['proof_url'] ?? null,
                'file_path' => $filePath,
                'status' => 'pending',
            ]);
        } catch (\Throwable $e) {
            if ($filePath) {
                MissionSubmission::deleteProofFileIfUnreferenced($filePath);
            }
            \Illuminate\Support\Facades\Log::error('Mission submission failed', ['mission_id' => $mission->id, 'exception' => get_class($e)]);
            return back()->with('error', 'We could not save your submission. Please try again.')->withInput();
        }

        return redirect()->route('missions.show', $mission)->with('success', 'Your mission response has been submitted for review!');
    }
}
