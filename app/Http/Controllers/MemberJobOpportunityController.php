<?php

namespace App\Http\Controllers;

use App\Models\JobBookmark;
use App\Models\JobListing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberJobOpportunityController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $tab = $request->input('tab', 'all');
        $search = $request->input('search');
        $jobType = $request->input('job_type');
        $workplace = $request->input('workplace');

        $query = JobListing::where('is_active', true);

        if ($tab === 'bookmarked') {
            $bookmarkedIds = JobBookmark::where('user_id', $user->id)->pluck('job_listing_id');
            $query->whereIn('id', $bookmarkedIds);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereLike('title', "%{$search}%")
                    ->orWhereLike('company', "%{$search}%")
                    ->orWhereLike('description', "%{$search}%");
            });
        }

        if ($jobType && $jobType !== 'all') {
            $query->where('job_type', $jobType);
        }

        if ($workplace === 'remote') {
            $query->where('is_remote', true);
        } elseif ($workplace === 'onsite') {
            $query->where('is_remote', false);
        }

        $jobs = $query->orderBy('created_at', 'desc')->paginate(12);

        $myBookmarkIds = JobBookmark::where('user_id', $user->id)->pluck('job_listing_id')->all();

        return view('opportunities.index', [
            'jobs' => $jobs,
            'tab' => $tab,
            'search' => $search,
            'selectedJobType' => $jobType,
            'selectedWorkplace' => $workplace,
            'myBookmarkIds' => $myBookmarkIds,
            'employmentTypes' => JobListing::EMPLOYMENT_TYPES,
        ]);
    }

    public function show(Request $request, JobListing $opportunity): View
    {
        if (!$opportunity->is_active && !in_array($request->user()->role, ['admin', 'super_admin'])) {
            abort(404);
        }

        $isBookmarked = JobBookmark::where('user_id', $request->user()->id)
            ->where('job_listing_id', $opportunity->id)
            ->exists();

        return view('opportunities.show', [
            'job' => $opportunity,
            'isBookmarked' => $isBookmarked,
        ]);
    }

    public function toggleBookmark(Request $request, JobListing $opportunity): RedirectResponse
    {
        $user = $request->user();

        $existing = JobBookmark::where('user_id', $user->id)
            ->where('job_listing_id', $opportunity->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $message = 'Opportunity removed from your saved bookmarks.';
        } else {
            JobBookmark::create([
                'user_id' => $user->id,
                'job_listing_id' => $opportunity->id,
            ]);
            $message = 'Opportunity saved to your bookmarks.';
        }

        return back()->with('success', $message);
    }
}
