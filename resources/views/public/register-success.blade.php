<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registration Submitted — {{ $event->name }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#0f1117] text-slate-100 min-h-screen flex flex-col font-sans antialiased selection:bg-[#7A1618] selection:text-white relative overflow-x-hidden">
    <x-public-auth-bg />

    <div class="relative overflow-hidden flex-1 flex flex-col items-center justify-center p-4 py-10 z-10">
        <div class="w-full max-w-lg relative z-10 text-center">
            <div class="w-16 h-16 mx-auto mb-3 flex items-center justify-center p-1.5 rounded-2xl bg-[#171a23]/90 border border-slate-800 shadow-xl enter-logo backdrop-blur-sm">
                <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society Logo" class="w-full h-full object-contain">
            </div>

            <div class="bg-[#171a23]/95 border border-slate-800 backdrop-blur-md rounded-2xl shadow-2xl p-6 sm:p-8 text-left enter-card">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h1 class="font-brand-display text-2xl font-bold text-white">Registration Submitted!</h1>
                    <p class="text-slate-300 text-xs sm:text-sm mt-1">
                        Thank you for registering. Your details have been submitted for administrator review.
                    </p>
                </div>

                @if($registration)
                    <div class="bg-slate-950/80 border border-slate-800 rounded-xl p-4 sm:p-5 space-y-3 mb-6 text-xs">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-800/80">
                            <span class="text-slate-400">Registration Status</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20 capitalize">
                                {{ $registration->status }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                            <div>
                                <span class="block text-[11px] text-slate-500">Event Name</span>
                                <span class="font-medium text-white">{{ $event->name }}</span>
                            </div>

                            <div>
                                <span class="block text-[11px] text-slate-500">Participant Name</span>
                                <span class="font-medium text-white">{{ $registration->full_name }}</span>
                            </div>

                            <div>
                                <span class="block text-[11px] text-slate-500">Email Address</span>
                                <span class="font-mono text-slate-300">{{ $registration->email }}</span>
                            </div>

                            <div>
                                <span class="block text-[11px] text-slate-500">School / University</span>
                                <span class="text-slate-300">{{ $registration->organization ?? '—' }}</span>
                            </div>

                            <div>
                                <span class="block text-[11px] text-slate-500">Program / Course</span>
                                <span class="text-slate-300">{{ $registration->program ?? '—' }}</span>
                            </div>

                            <div>
                                <span class="block text-[11px] text-slate-500">Year Level</span>
                                <span class="text-slate-300">{{ $registration->year_level ?? '—' }}</span>
                            </div>

                            <div>
                                <span class="block text-[11px] text-slate-500">T-Shirt Size</span>
                                <span class="font-semibold text-brand-400">{{ $registration->tshirt_size ?? '—' }}</span>
                            </div>

                            <div>
                                <span class="block text-[11px] text-slate-500">Food Restrictions</span>
                                <span class="text-slate-300">
                                    {{ $registration->food_restrictions }}
                                    @if($registration->food_restriction_details)
                                        <span class="text-slate-400">({{ $registration->food_restriction_details }})</span>
                                    @endif
                                </span>
                            </div>

                            <div class="sm:col-span-2">
                                <span class="block text-[11px] text-slate-500">Registration Date</span>
                                <span class="text-slate-300">{{ $registration->created_at->format('F j, Y, g:i A') }}</span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-slate-950/80 border border-slate-800 rounded-xl p-4 text-xs text-slate-400 space-y-2 mb-6">
                        <p><strong class="text-slate-200">Event:</strong> {{ $event->name }}</p>
                        <p><strong class="text-slate-200">Date:</strong> {{ $event->event_date ? $event->event_date->format('F j, Y') : 'TBA' }}</p>
                        <p><strong class="text-slate-200">Status:</strong> Pending Administrator Review</p>
                    </div>
                @endif

                <div class="p-3 bg-brand-500/10 border border-brand-500/20 rounded-xl text-xs text-brand-300 flex items-start gap-2.5 mb-6">
                    <svg class="w-4 h-4 shrink-0 mt-0.5 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>
                        Our event administrators will review your registration. Physical event QR cards and check-in badges will be provided by the ComSoc team.
                    </span>
                </div>

                <a href="{{ route('public.register') }}" class="inline-block text-center w-full py-2.5 px-4 rounded-xl font-medium text-sm text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 transition">
                    Done
                </a>
            </div>
        </div>
    </div>
</body>
</html>
