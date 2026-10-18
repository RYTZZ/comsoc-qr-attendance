<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\JobListing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminJobOpportunityController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->input('status', 'all');

        $query = JobListing::withCount('bookmarks')->orderBy('created_at', 'desc');

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'archived') {
            $query->where('is_active', false);
        }

        $jobs = $query->paginate(15);

        return view('admin.opportunities.index', [
            'jobs' => $jobs,
            'status' => $status,
            'employmentTypes' => JobListing::EMPLOYMENT_TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'company' => ['required', 'string', 'max:255'],
            'job_type' => ['required', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_remote' => ['nullable', 'boolean'],
            'compensation' => ['nullable', 'string', 'max:100'],
            'application_url' => ['required', 'url', 'max:500'],
            'source_url' => ['nullable', 'url', 'max:500'],
            'deadline_at' => ['nullable', 'date'],
            'required_skills' => ['nullable', 'string', 'max:500'],
            'description' => ['required', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
            'is_verified' => ['nullable', 'boolean'],
        ]);

        $skillsArray = !empty($validated['required_skills'])
            ? array_values(array_filter(array_map('trim', explode(',', $validated['required_skills']))))
            : [];

        $job = JobListing::create([
            'title' => $validated['title'],
            'company' => $validated['company'],
            'job_type' => $validated['job_type'],
            'location' => $validated['location'] ?? null,
            'is_remote' => $request->boolean('is_remote'),
            'compensation' => $validated['compensation'] ?? null,
            'application_url' => $validated['application_url'],
            'source_url' => $validated['source_url'] ?? null,
            'deadline_at' => $validated['deadline_at'] ?? null,
            'required_skills' => $skillsArray,
            'description' => $validated['description'],
            'is_active' => $request->boolean('is_active', true),
            'is_verified' => $request->boolean('is_verified', true),
            'posted_by' => $request->user()->id,
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'created_job_listing',
            'details' => json_encode([
                'job_id' => $job->id,
                'title' => $job->title,
                'company' => $job->company,
            ]),
        ]);

        return redirect()->route('admin.opportunities.index')->with('success', 'External opportunity published successfully.');
    }

    public function update(Request $request, JobListing $opportunity): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'company' => ['required', 'string', 'max:255'],
            'job_type' => ['required', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_remote' => ['nullable', 'boolean'],
            'compensation' => ['nullable', 'string', 'max:100'],
            'application_url' => ['required', 'url', 'max:500'],
            'source_url' => ['nullable', 'url', 'max:500'],
            'deadline_at' => ['nullable', 'date'],
            'required_skills' => ['nullable', 'string', 'max:500'],
            'description' => ['required', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
            'is_verified' => ['nullable', 'boolean'],
        ]);

        $skillsArray = !empty($validated['required_skills'])
            ? array_values(array_filter(array_map('trim', explode(',', $validated['required_skills']))))
            : [];

        $opportunity->update([
            'title' => $validated['title'],
            'company' => $validated['company'],
            'job_type' => $validated['job_type'],
            'location' => $validated['location'] ?? null,
            'is_remote' => $request->boolean('is_remote'),
            'compensation' => $validated['compensation'] ?? null,
            'application_url' => $validated['application_url'],
            'source_url' => $validated['source_url'] ?? null,
            'deadline_at' => $validated['deadline_at'] ?? null,
            'required_skills' => $skillsArray,
            'description' => $validated['description'],
            'is_active' => $request->boolean('is_active'),
            'is_verified' => $request->boolean('is_verified'),
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'updated_job_listing',
            'details' => json_encode([
                'job_id' => $opportunity->id,
                'title' => $opportunity->title,
            ]),
        ]);

        return redirect()->route('admin.opportunities.index')->with('success', 'Opportunity details updated successfully.');
    }

    public function toggleActive(Request $request, JobListing $opportunity): RedirectResponse
    {
        $opportunity->update([
            'is_active' => !$opportunity->is_active,
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'toggled_job_listing_active',
            'details' => json_encode([
                'job_id' => $opportunity->id,
                'is_active' => $opportunity->is_active,
            ]),
        ]);

        $statusStr = $opportunity->is_active ? 'published' : 'archived';
        return redirect()->route('admin.opportunities.index')->with('success', "Listing has been {$statusStr}.");
    }
}
