<x-layouts.admin :title="'Kiosks'">
<div class="page-header">
    <div>
        <h1 class="page-title">Attendance & Snack Kiosks</h1>
        <p class="text-xs text-slate-400 mt-1">Configure scanner terminals for attendance checkpoints and snack booths.</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="py-3 px-4">Kiosk Name</th>
                            <th class="py-3 px-4">Assigned Staff</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Launch Terminal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($kiosks as $k)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="py-3 px-4">
                                    <div class="font-semibold text-white">{{ $k->name }}</div>
                                    <div class="text-[11px] text-slate-500 font-mono">ID: {{ $k->kiosk_identifier ?? ('KIOSK-' . $k->id) }}</div>
                                </td>
                                <td class="py-3 px-4 text-slate-300">{{ $k->assignedUser?->name ?? 'Any Staff' }}</td>
                                <td class="py-3 px-4">
                                    @if($k->is_active)
                                        <span class="badge badge-active">Online / Active</span>
                                    @else
                                        <span class="badge badge-inactive">Offline</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right space-x-2">
                                    <a href="{{ route('kiosk.attendance', $k) }}" target="_blank" class="btn-primary btn-sm">Attendance Scan ↗</a>
                                    <a href="{{ route('kiosk.snack', $k) }}" target="_blank" class="btn-secondary btn-sm">Snack Scan ↗</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-slate-500">No kiosks configured yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div>
        <div class="card space-y-4">
            <h2 class="text-sm font-semibold text-white border-b border-slate-800 pb-2">Add New Kiosk Terminal</h2>
            <form method="POST" action="{{ route('admin.kiosks.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label for="name" class="label">Kiosk Name <span class="text-red-400">*</span></label>
                    <input id="name" name="name" type="text" required class="input" placeholder="e.g., Gate 1 Scanner" />
                </div>
                <div>
                    <label for="assigned_user_id" class="label">Assigned Staff Operator</label>
                    <select id="assigned_user_id" name="assigned_user_id" class="input">
                        <option value="">-- Any Staff / Unrestricted --</option>
                        @foreach(\App\Models\User::whereIn('role', ['staff', 'admin', 'super_admin'])->get() as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->role }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="pt-2">
                    <button type="submit" class="w-full btn-primary">Create Kiosk</button>
                </div>
            </form>
        </div>
    </div>
</div>
</x-layouts.admin>
