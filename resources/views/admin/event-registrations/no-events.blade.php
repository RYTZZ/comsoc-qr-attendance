<x-layouts.admin title="Guest Registrations">
<div class="page-header">
    <div>
        <h1 class="page-title">Guest Registrations</h1>
        <p class="text-xs text-slate-400 mt-0.5">Manage non-student participant registrations for events.</p>
    </div>
</div>

<div class="card p-12 text-center">
    <svg class="w-12 h-12 text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
    </svg>
    <h2 class="text-slate-300 font-semibold text-base mb-1">No Events Found</h2>
    <p class="text-slate-500 text-sm">There are no events in the system yet. Create an event first to manage registrations.</p>
    @can('super_admin')
    <a href="{{ route('admin.events.create') }}" class="btn-primary btn-sm mt-6 inline-flex">Create Event</a>
    @endcan
</div>
</x-layouts.admin>
