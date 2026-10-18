<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('volunteers.index') }}" class="text-xs text-[#df747a] hover:underline flex items-center gap-1 mb-1 font-semibold">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    Back to Volunteer Board
                </a>
                <h1 class="text-2xl font-extrabold text-[#F8FAFC] font-brand-display tracking-tight">
                    Publish Volunteer Opportunity
                </h1>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-[#0B1120]">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="card bg-[#172033] border border-[#334155] shadow-2xl p-6 sm:p-8">

                <form method="POST" action="{{ route('volunteers.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Opportunity Title *</label>
                        <input type="text" name="title" value="{{ old('title') }}" required placeholder="e.g. Annual IT Symposium Registration Desk Officer"
                               class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-4 py-2.5 text-xs text-white focus:ring-2 focus:ring-[#8F3033]">
                        @error('title') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Category *</label>
                            <select name="category" required class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-4 py-2.5 text-xs text-white">
                                @foreach(\App\Models\VolunteerOpportunity::CATEGORIES as $k => $label)
                                <option value="{{ $k }}" {{ old('category') === $k ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Associated Event (Optional)</label>
                            <select name="event_id" class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-4 py-2.5 text-xs text-white">
                                <option value="">None / General Organization</option>
                                @foreach($events as $ev)
                                <option value="{{ $ev->id }}" {{ old('event_id') === $ev->id ? 'selected' : '' }}>{{ $ev->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">General Description *</label>
                        <textarea name="description" rows="3" required placeholder="Overview of the activity and significance to the organization..."
                                  class="w-full bg-[#0B1120] border border-[#334155] rounded-xl p-3 text-xs text-white">{{ old('description') }}</textarea>
                        @error('description') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Key Responsibilities & Tasks *</label>
                        <textarea name="responsibilities" rows="4" required placeholder="List duties: e.g. manage check-in kiosks, verify student tickets, usher VIP speakers..."
                                  class="w-full bg-[#0B1120] border border-[#334155] rounded-xl p-3 text-xs text-white">{{ old('responsibilities') }}</textarea>
                        @error('responsibilities') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Location Type *</label>
                            <select name="location_type" required class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-4 py-2 text-xs text-white">
                                <option value="onsite">Onsite (Campus)</option>
                                <option value="hybrid">Hybrid</option>
                                <option value="remote">Remote / Online</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Location Details</label>
                            <input type="text" name="location_details" value="{{ old('location_details') }}" placeholder="e.g. Bulan Campus Auditorium"
                                   class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-4 py-2 text-xs text-white">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Start Time *</label>
                            <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" required
                                   class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-4 py-2 text-xs text-white">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">End Time *</label>
                            <input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}" required
                                   class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-4 py-2 text-xs text-white">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Capacity (Slots) *</label>
                            <input type="number" name="capacity" value="{{ old('capacity', 5) }}" min="1" max="100" required
                                   class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-4 py-2 text-xs text-white">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Expected Hours *</label>
                            <input type="number" step="0.5" name="expected_hours" value="{{ old('expected_hours', 4) }}" min="0.5" max="40" required
                                   class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-4 py-2 text-xs text-white">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">EXP per Hour *</label>
                            <input type="number" name="exp_reward_per_hour" value="{{ old('exp_reward_per_hour', 50) }}" min="0" max="500" required
                                   class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-4 py-2 text-xs text-white">
                        </div>
                    </div>

                    <div class="pt-4 border-t border-[#334155] flex items-center justify-end gap-3">
                        <a href="{{ route('volunteers.index') }}" class="btn-secondary text-xs py-2 px-4">Cancel</a>
                        <button type="submit" class="btn-primary text-xs py-2 px-6 shadow-lg">Publish Volunteer Slot</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
