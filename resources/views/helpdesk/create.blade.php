<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('helpdesk.index') }}" class="text-xs text-[#df747a] hover:underline flex items-center gap-1 mb-1 font-semibold">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    Back to Help Desk
                </a>
                <h1 class="text-2xl font-extrabold text-[#F8FAFC] font-brand-display tracking-tight">
                    Submit a Support Request or Dispute
                </h1>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-[#0B1120]">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="card bg-[#172033] border border-[#334155] shadow-2xl p-6 sm:p-8">

                <form method="POST" action="{{ route('helpdesk.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Issue Category *</label>
                        <select name="category" required class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-4 py-2.5 text-xs text-white focus:ring-2 focus:ring-[#8F3033] focus:border-transparent">
                            <option value="">Select Category...</option>
                            @foreach(\App\Models\SupportTicket::CATEGORIES as $key => $label)
                            <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('category') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Subject Summary *</label>
                        <input type="text" name="subject" value="{{ old('subject') }}" required placeholder="Brief description of the problem (e.g. Scanned in morning session but missing from ledger)"
                               class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-4 py-2.5 text-xs text-white focus:ring-2 focus:ring-[#8F3033]">
                        @error('subject') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Priority</label>
                            <select name="priority" class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-4 py-2 text-xs text-white">
                                <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low</option>
                                <option value="normal" {{ old('priority', 'normal') === 'normal' ? 'selected' : '' }}>Normal</option>
                                <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High</option>
                                <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Related Event (Optional)</label>
                            <select name="event_id" class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-4 py-2 text-xs text-white">
                                <option value="">None / Not Applicable</option>
                                @foreach($events as $ev)
                                <option value="{{ $ev->id }}" {{ old('event_id') === $ev->id ? 'selected' : '' }}>{{ $ev->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Related EXP or Attendance Dropdown -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Related EXP Transaction (Optional)</label>
                            <select name="xp_transaction_id" class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-4 py-2 text-xs text-white">
                                <option value="">None / Not Applicable</option>
                                @foreach($recentXp as $tx)
                                <option value="{{ $tx->id }}" {{ old('xp_transaction_id') === $tx->id ? 'selected' : '' }}>
                                    {{ $tx->created_at->format('M d') }} - {{ $tx->description }} ({{ $tx->amount }} EXP)
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Related Attendance Session (Optional)</label>
                            <select name="attendance_record_id" class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-4 py-2 text-xs text-white">
                                <option value="">None / Not Applicable</option>
                                @foreach($recentAttendance as $att)
                                <option value="{{ $att->id }}" {{ old('attendance_record_id') === $att->id ? 'selected' : '' }}>
                                    {{ $att->event?->name }} ({{ $att->scanned_at?->format('M d, H:i') }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Detailed Description *</label>
                        <textarea name="description" rows="5" required placeholder="Please provide specific details: time, location or kiosk terminal, photo pass code, error message, or explanation."
                                  class="w-full bg-[#0B1120] border border-[#334155] rounded-xl p-4 text-xs text-white focus:ring-2 focus:ring-[#8F3033]">{{ old('description') }}</textarea>
                        @error('description') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Proof / Attachment (Optional)</label>
                        <input type="file" name="attachment" accept=".png,.jpg,.jpeg,.pdf,.txt"
                               class="block w-full text-xs text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-[#1E293B] file:text-[#df747a] hover:file:bg-[#334155]">
                        <p class="text-[11px] text-[#94A3B8] mt-1">Accepted formats: PNG, JPG, PDF, TXT (Max: 5MB). Files are securely stored in private storage.</p>
                        @error('attachment') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="pt-4 border-t border-[#334155] flex items-center justify-end gap-3">
                        <a href="{{ route('helpdesk.index') }}" class="btn-secondary text-xs py-2 px-4">Cancel</a>
                        <button type="submit" class="btn-primary text-xs py-2 px-6 shadow-lg">Submit Request</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
