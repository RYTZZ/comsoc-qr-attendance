<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="ComSoc QR Attendance — Computing Society attendance and membership management system.">
    <title>{{ isset($title) ? $title . ' — ' : '' }}ComSoc QR Attendance</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Michroma&family=Sora:wght@600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-[#0f1117] text-slate-100 antialiased font-sans" x-data="{ sidebarOpen: false }">

<div class="flex h-full">
    <div x-show="sidebarOpen"
         x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false"
         class="fixed inset-0 bg-black/60 z-30 lg:hidden"></div>

    <aside class="fixed inset-y-0 left-0 z-40 w-64 bg-slate-900 border-r border-slate-800 flex flex-col transform transition-transform duration-200 ease-in-out lg:translate-x-0"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

        <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-800">
            <div class="w-10 h-10 rounded-xl bg-slate-950 border border-slate-800 p-1 flex items-center justify-center flex-shrink-0 shadow-md">
                <img src="{{ asset('images/COMSOC.png') }}" alt="Computing Society Logo" class="w-full h-full object-contain">
            </div>
            <div>
                <p class="font-bold text-white text-sm leading-tight tracking-tight">Computing Society</p>
                <p class="text-[11px] text-brand-400 font-medium">QR Attendance</p>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-0.5 scrollbar-none">

            <a href="{{ route('admin.dashboard') }}"
               class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i data-lucide="layout-dashboard" class="sidebar-link-icon"></i>
                Dashboard
            </a>

            <div class="pt-4 pb-1">
                <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Membership & Students</p>
            </div>

            @if(auth()->user()->isSuperAdmin())
            <a href="{{ route('admin.academic-years.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.academic-years.*') ? 'active' : '' }}">
                <i data-lucide="calendar" class="sidebar-link-icon"></i>
                Academic Years
            </a>
            <a href="{{ route('admin.masterlist.upload') }}"
               class="sidebar-link {{ request()->routeIs('admin.masterlist.*') ? 'active' : '' }}">
                <i data-lucide="file-spreadsheet" class="sidebar-link-icon"></i>
                Masterlist
            </a>
            @endif

            <a href="{{ route('admin.students.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.students.*') ? 'active' : '' }}">
                <i data-lucide="users" class="sidebar-link-icon"></i>
                Students
            </a>

            <a href="{{ route('admin.memberships.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.memberships.*') ? 'active' : '' }}">
                <i data-lucide="award" class="sidebar-link-icon"></i>
                Memberships
            </a>

            <a href="{{ route('admin.qr-codes.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.qr-codes.*') ? 'active' : '' }}">
                <i data-lucide="qr-code" class="sidebar-link-icon"></i>
                QR Codes
            </a>

            <a href="{{ route('admin.cards.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.cards.*') ? 'active' : '' }}">
                <i data-lucide="credit-card" class="sidebar-link-icon"></i>
                Cards
            </a>

            <div class="pt-4 pb-1">
                <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Events & Attendance</p>
            </div>

            <a href="{{ route('admin.events.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.events.*') && !request()->routeIs('admin.event-registrations.*') ? 'active' : '' }}">
                <i data-lucide="calendar-check" class="sidebar-link-icon"></i>
                Events
            </a>

            <a href="{{ route('admin.event-registrations.all') }}"
               class="sidebar-link {{ request()->routeIs('admin.event-registrations.*') ? 'active' : '' }}">
                <i data-lucide="user-plus" class="sidebar-link-icon"></i>
                Registrations
            </a>

            <a href="{{ route('admin.attendance.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.attendance.*') ? 'active' : '' }}">
                <i data-lucide="check-circle-2" class="sidebar-link-icon"></i>
                Attendance
            </a>

            <a href="{{ route('admin.kiosks.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.kiosks.*') ? 'active' : '' }}">
                <i data-lucide="monitor" class="sidebar-link-icon"></i>
                Kiosks
            </a>

            <div class="pt-4 pb-1">
                <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Operations & Admin</p>
            </div>

            <a href="{{ route('admin.organizations.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.organizations.*') ? 'active' : '' }}">
                <i data-lucide="building-2" class="sidebar-link-icon"></i>
                Schools / Univs
            </a>

            <a href="{{ route('admin.programs.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.programs.*') ? 'active' : '' }}">
                <i data-lucide="graduation-cap" class="sidebar-link-icon"></i>
                Programs / Courses
            </a>

            <a href="{{ route('admin.incidents.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.incidents.*') ? 'active' : '' }}">
                <i data-lucide="alert-triangle" class="sidebar-link-icon"></i>
                Incidents
                @php $openIncidents = \App\Models\Incident::where('status','open')->count(); @endphp
                @if($openIncidents > 0)
                <span class="ml-auto badge-open text-xs px-1.5 py-0.5">{{ $openIncidents }}</span>
                @endif
            </a>

            <a href="{{ route('admin.reports.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                <i data-lucide="bar-chart-3" class="sidebar-link-icon"></i>
                Reports
            </a>

            @if(auth()->user()->isSuperAdmin())
            <a href="{{ route('admin.audit-logs.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}">
                <i data-lucide="history" class="sidebar-link-icon"></i>
                Audit Logs
            </a>

            <a href="{{ route('admin.users.index') }}"
               class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i data-lucide="shield" class="sidebar-link-icon"></i>
                Accounts
            </a>
            @endif
        </nav>

        <div class="px-3 py-4 border-t border-slate-800">
            <div class="flex items-center gap-3 px-3 py-2 rounded-lg bg-slate-950/60 border border-slate-800/80">
                <div class="w-8 h-8 rounded-full bg-[#7A1618] flex items-center justify-center flex-shrink-0 text-white">
                    <i data-lucide="user-round" class="w-4 h-4"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-slate-400 truncate capitalize">{{ str_replace('_', ' ', auth()->user()->role) }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button type="submit" class="sidebar-link w-full text-red-400 hover:text-red-300 hover:bg-red-950/30">
                    <i data-lucide="log-out" class="sidebar-link-icon"></i>
                    Sign out
                </button>
            </form>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-h-screen lg:pl-64 w-full min-w-0">
        <header class="sticky top-0 z-20 bg-[#171a23]/95 backdrop-blur border-b border-slate-800 flex items-center justify-between px-3 sm:px-6 h-14 shrink-0 shadow-sm">
            <div class="flex items-center gap-2 sm:gap-3">
                <button @click="sidebarOpen = true" class="lg:hidden p-2 -ml-1 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors focus:outline-none focus:ring-2 focus:ring-[#7A1618]" aria-label="Open menu">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <div class="lg:hidden flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-[#7A1618] flex items-center justify-center shadow">
                        <i data-lucide="user-round" class="w-4 h-4 text-white"></i>
                    </div>
                    <span class="font-bold text-white text-xs sm:text-sm truncate">ComSoc QR</span>
                </div>
            </div>

            <div class="flex items-center gap-2 sm:gap-3 ml-auto">
                @if($activeYear = \App\Models\AcademicYear::active())
                <span class="badge-active text-[10px] sm:text-xs px-2.5 py-1 truncate max-w-[140px] sm:max-w-none">{{ $activeYear->label }}</span>
                @endif
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 p-1 text-slate-400 hover:text-white transition-colors" title="My Profile">
                    <div class="w-8 h-8 rounded-full bg-[#7A1618] flex items-center justify-center shadow text-white hover:ring-2 hover:ring-[#7A1618]/50 transition-all">
                        <i data-lucide="user-round" class="w-4 h-4"></i>
                    </div>
                </a>
            </div>
        </header>

        <main class="flex-1 px-3 sm:px-6 py-4 sm:py-6 w-full max-w-full overflow-x-hidden">
            @if(session('success'))
            <div class="alert-success mb-4 animate-fade-in">
                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ session('success') }}
            </div>
            @endif

            @if(session('error'))
            <div class="alert-error mb-4 animate-fade-in">
                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                {{ session('error') }}
            </div>
            @endif

            @if($errors->any())
            <div class="alert-error mb-4 animate-fade-in">
                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <ul class="space-y-0.5">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{ $slot }}
        </main>
    </div>
</div>

<x-confirmation-dialog />
<x-toast-stack />

</body>
</html>
