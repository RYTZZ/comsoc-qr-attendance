<x-layouts.admin :title="'Registration Details — ' . $registration->full_name">
<div class="page-header">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.event-registrations.index', $registration->event) }}" class="btn-secondary btn-sm">← Registrations</a>
            <div>
                <h1 class="page-title">{{ $registration->full_name }}</h1>
                <p class="text-xs text-slate-400 mt-0.5">Registration record for {{ $registration->event->name }}</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if($registration->status === 'pending')
                <form method="POST" action="{{ route('admin.event-registrations.approve', $registration) }}" class="inline">
                    @csrf
                    <button type="submit" class="btn-primary btn-sm">Approve & Issue QR</button>
                </form>
                <form method="POST" action="{{ route('admin.event-registrations.reject', $registration) }}" class="inline">
                    @csrf
                    <input type="hidden" name="reason" value="Administrative decision">
                    <button type="button"
                            data-confirm="Reject this participant registration? Their status will be marked as rejected."
                            data-confirm-title="Reject Registration"
                            data-confirm-type="danger"
                            data-confirm-btn="Reject Registration"
                            class="btn-secondary btn-sm text-red-400 border-red-500/20">Reject</button>
                </form>
            @elseif($registration->status === 'approved')
                <a href="{{ route('admin.event-registrations.qr', $registration) }}" class="btn-primary btn-sm">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Download Physical QR Pass
                </a>
            @endif
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="card p-6">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-4 pb-2 border-b border-slate-800">
                Personal Information
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div>
                    <dt class="text-slate-500">Full Name</dt>
                    <dd class="text-white font-medium text-sm mt-0.5">{{ $registration->full_name }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Email Address</dt>
                    <dd class="text-slate-200 font-mono mt-0.5">{{ $registration->email }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">School / University</dt>
                    <dd class="text-slate-200 mt-0.5">{{ $registration->organization ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Mobile Number</dt>
                    <dd class="text-slate-200 mt-0.5">{{ $registration->phone ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="card p-6">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-4 pb-2 border-b border-slate-800">
                Participant Information
            </h2>
            <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                <div>
                    <dt class="text-slate-500">Program / Course</dt>
                    <dd class="text-white font-medium mt-0.5">{{ $registration->program ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Year Level</dt>
                    <dd class="text-slate-200 mt-0.5">{{ $registration->year_level ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">T-Shirt Size</dt>
                    <dd class="text-brand-400 font-bold text-sm mt-0.5">{{ $registration->tshirt_size ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="card p-6">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-4 pb-2 border-b border-slate-800">
                Food & Dietary Information
            </h2>
            <div class="space-y-3 text-xs">
                <div>
                    <span class="text-slate-500 block">Food Restrictions</span>
                    <span class="text-white font-medium mt-0.5 inline-block">{{ $registration->food_restrictions ?? 'None' }}</span>
                </div>
                @if($registration->food_restriction_details)
                    <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                        <span class="text-slate-500 block mb-1">Details / Notes</span>
                        <p class="text-slate-300">{{ $registration->food_restriction_details }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="card p-6">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-4 pb-2 border-b border-slate-800">
                Registration Status
            </h2>
            <div class="space-y-3 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Current Status</span>
                    @if($registration->status === 'approved')
                        <span class="badge badge-active">Approved</span>
                    @elseif($registration->status === 'rejected')
                        <span class="badge bg-red-500/10 text-red-400 border-red-500/20">Rejected</span>
                    @else
                        <span class="badge bg-yellow-500/10 text-yellow-400 border-yellow-500/20">Pending</span>
                    @endif
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Accuracy Confirmation</span>
                    @if($registration->confirmed)
                        <span class="text-emerald-400 font-medium flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            Confirmed
                        </span>
                    @else
                        <span class="text-slate-500">Not Confirmed</span>
                    @endif
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Registered On</span>
                    <span class="text-slate-200">{{ $registration->created_at->format('M d, Y h:i A') }}</span>
                </div>

                @if($registration->reviewedByUser)
                    <div class="flex items-center justify-between pt-2 border-t border-slate-800">
                        <span class="text-slate-500">Reviewed By</span>
                        <span class="text-slate-200">{{ $registration->reviewedByUser->name }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Reviewed On</span>
                        <span class="text-slate-200">{{ $registration->reviewed_at?->format('M d, Y h:i A') }}</span>
                    </div>
                @endif

                @if($registration->rejection_reason)
                    <div class="p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-300">
                        <span class="block text-[11px] text-red-400 font-semibold mb-0.5">Rejection Reason</span>
                        {{ $registration->rejection_reason }}
                    </div>
                @endif
            </div>
        </div>

        <div class="card p-6">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-4 pb-2 border-b border-slate-800">
                Event Information
            </h2>
            <div class="space-y-2 text-xs">
                <p class="text-white font-semibold">{{ $registration->event->name }}</p>
                <p class="text-slate-400 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    {{ $registration->event->event_date?->format('F j, Y') ?? 'TBA' }}
                </p>
                @if($registration->event->location)
                    <p class="text-slate-400 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        {{ $registration->event->location }}
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
</x-layouts.admin>
