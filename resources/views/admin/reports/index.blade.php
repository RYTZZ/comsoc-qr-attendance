<x-layouts.admin :title="'System Reports'">
<div x-data="{
    openRangeModal: false,
    reportType: 'attendance',
    dateFrom: '',
    dateTo: '',
    statusFilter: ''
}">
<div class="page-header">
    <div>
        <h1 class="page-title">Reports & Data Exports</h1>
        <p class="text-xs text-slate-400 mt-1">Export official Excel, CSV, and PDF summaries for society archives.</p>
    </div>
    <button type="button" @click="openRangeModal = true" class="btn-primary text-xs flex items-center gap-1.5">
        <i data-lucide="sliders" class="w-3.5 h-3.5"></i>
        Custom Range Export
    </button>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div class="card space-y-3 hover:border-slate-700 transition">
        <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center">
            <i data-lucide="file-spreadsheet" class="w-5 h-5"></i>
        </div>
        <h2 class="text-sm font-semibold text-white">Membership Master Report</h2>
        <p class="text-xs text-slate-400 leading-relaxed">Official list of all registered students with active/inactive society membership status.</p>
        <div class="pt-2 flex gap-2">
            <a href="{{ route('admin.reports.membership', ['format' => 'csv']) }}" class="btn-secondary btn-sm">Export CSV</a>
            <a href="{{ route('admin.reports.membership', ['format' => 'pdf']) }}" class="btn-primary btn-sm">Export PDF</a>
        </div>
    </div>

    <div class="card space-y-3 hover:border-slate-700 transition">
        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center">
            <i data-lucide="calendar-check" class="w-5 h-5"></i>
        </div>
        <h2 class="text-sm font-semibold text-white">Event Attendance Logs</h2>
        <p class="text-xs text-slate-400 leading-relaxed">Granular attendance records with IN/OUT timestamps, late flags, and guest records.</p>
        <div class="pt-2 flex gap-2">
            <a href="{{ route('admin.reports.attendance', ['format' => 'csv']) }}" class="btn-secondary btn-sm">Export CSV</a>
            <button type="button" @click="reportType = 'attendance'; openRangeModal = true" class="btn-secondary btn-sm text-brand-400">Custom Range</button>
        </div>
    </div>

    <div class="card space-y-3 hover:border-slate-700 transition">
        <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center">
            <i data-lucide="coffee" class="w-5 h-5"></i>
        </div>
        <h2 class="text-sm font-semibold text-white">Snack Distribution Log</h2>
        <p class="text-xs text-slate-400 leading-relaxed">Inventory decrement audits, claimant student numbers, and snack session breakdown.</p>
        <div class="pt-2 flex gap-2">
            <a href="{{ route('admin.reports.snacks', ['format' => 'csv']) }}" class="btn-secondary btn-sm">Export CSV</a>
        </div>
    </div>

    <div class="card space-y-3 hover:border-slate-700 transition">
        <div class="w-10 h-10 rounded-xl bg-red-500/10 text-red-400 flex items-center justify-center">
            <i data-lucide="alert-triangle" class="w-5 h-5"></i>
        </div>
        <h2 class="text-sm font-semibold text-white">Incident Summary</h2>
        <p class="text-xs text-slate-400 leading-relaxed">Official incident log with severity ratings, staff notes, and resolution actions.</p>
        <div class="pt-2 flex gap-2">
            <a href="{{ route('admin.reports.incidents', ['format' => 'csv']) }}" class="btn-secondary btn-sm">Export CSV</a>
        </div>
    </div>

    <div class="card space-y-3 hover:border-slate-700 transition">
        <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center">
            <i data-lucide="credit-card" class="w-5 h-5"></i>
        </div>
        <h2 class="text-sm font-semibold text-white">QR & Physical Cards</h2>
        <p class="text-xs text-slate-400 leading-relaxed">Inventory and issuance metrics for printed PVC ID cards and digital tokens.</p>
        <div class="pt-2 flex gap-2">
            <a href="{{ route('admin.reports.qr-card', ['format' => 'csv']) }}" class="btn-secondary btn-sm">Export CSV</a>
        </div>
    </div>
</div>

<!-- Custom Date Range & Filter Modal -->
<div x-show="openRangeModal"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
     x-cloak>
    <div class="card max-w-md w-full border-slate-700 bg-[#171a23] shadow-2xl space-y-4 p-5"
         @click.away="openRangeModal = false">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-brand-500/10 border border-brand-500/20 text-brand-400 flex items-center justify-center">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white">Custom Date Range Export</h3>
                    <p class="text-[11px] text-slate-400">Filter official dataset by date and status</p>
                </div>
            </div>
            <button type="button" @click="openRangeModal = false" class="text-slate-400 hover:text-white">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="GET" :action="reportType === 'attendance' ? '{{ route('admin.reports.attendance') }}' : '{{ route('admin.reports.membership') }}'" class="space-y-3">
            <input type="hidden" name="format" value="csv">
            <div>
                <label class="label text-xs">Report Target</label>
                <select x-model="reportType" class="input text-xs w-full">
                    <option value="attendance">Event Attendance Logs</option>
                    <option value="membership">Membership Master Report</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="label text-xs">Date From</label>
                    <input type="date" name="date_from" x-model="dateFrom" class="input text-xs w-full">
                </div>
                <div>
                    <label class="label text-xs">Date To</label>
                    <input type="date" name="date_to" x-model="dateTo" class="input text-xs w-full">
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                <button type="button" @click="openRangeModal = false" class="btn-secondary btn-sm text-xs">Cancel</button>
                <button type="submit" class="btn-primary btn-sm text-xs">Export CSV</button>
            </div>
        </form>
    </div>
</div>
</div>
</x-layouts.admin>
