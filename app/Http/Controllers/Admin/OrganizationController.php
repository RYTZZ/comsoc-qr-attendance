<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function index(Request $request): View
    {
        $query = Organization::withCount('eventRegistrations');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $organizations = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.organizations.index', compact('organizations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:organizations,name'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $organization = Organization::create([
            'name' => trim($validated['name']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditLogger::log('organization.created', $organization, [], ['name' => $organization->name]);

        return redirect()->route('admin.organizations.index')->with('success', 'School / University added successfully.');
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:organizations,name,' . $organization->id],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $oldName = $organization->name;
        $organization->update([
            'name' => trim($validated['name']),
            'is_active' => $request->boolean('is_active'),
        ]);

        AuditLogger::log('organization.updated', $organization, ['name' => $oldName], ['name' => $organization->name]);

        return redirect()->route('admin.organizations.index')->with('success', 'School / University updated successfully.');
    }

    public function toggleActive(Organization $organization): RedirectResponse
    {
        $organization->update([
            'is_active' => !$organization->is_active,
        ]);

        $status = $organization->is_active ? 'activated' : 'deactivated';
        AuditLogger::log("organization.{$status}", $organization);

        return redirect()->route('admin.organizations.index')->with('success', "School / University {$status} successfully.");
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        if ($organization->eventRegistrations()->exists()) {
            return back()->with('error', 'Cannot delete this institution because it is associated with existing registrations. You can deactivate it instead.');
        }

        $name = $organization->name;
        $organization->delete();

        AuditLogger::log('organization.deleted', $organization, ['name' => $name]);

        return redirect()->route('admin.organizations.index')->with('success', 'School / University deleted successfully.');
    }
}
