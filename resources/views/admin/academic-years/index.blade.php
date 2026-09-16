<x-layouts.admin :title="'Academic Years'">
<div class="page-header">
    <div>
        <h1 class="page-title">Academic Years</h1>
        <p class="text-xs text-slate-400 mt-1">Manage society academic years and toggle the active term.</p>
    </div>
    <a href="{{ route('admin.academic-years.create') }}" class="btn-primary">+ Add Academic Year</a>
</div>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="py-3 px-4">Label / Name</th>
                    <th class="py-3 px-4">Start Date</th>
                    <th class="py-3 px-4">End Date</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($academicYears as $ay)
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="py-3 px-4 font-semibold text-white">{{ $ay->label }}</td>
                        <td class="py-3 px-4 text-slate-300">{{ $ay->start_date ? $ay->start_date->format('M d, Y') : '—' }}</td>
                        <td class="py-3 px-4 text-slate-300">{{ $ay->end_date ? $ay->end_date->format('M d, Y') : '—' }}</td>
                        <td class="py-3 px-4">
                            @if($ay->is_active)
                                <span class="badge badge-active">Active</span>
                            @else
                                <span class="badge badge-inactive">Inactive</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right space-x-2">
                            @if(!$ay->is_active)
                                <form method="POST" action="{{ route('admin.academic-years.activate', $ay) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="btn-secondary btn-sm text-brand-400 border-brand-500/30">Set Active</button>
                                </form>
                            @endif
                            <a href="{{ route('admin.academic-years.edit', $ay) }}" class="btn-secondary btn-sm">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-slate-500">No academic years found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-layouts.admin>
