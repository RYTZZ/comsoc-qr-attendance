<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('projects.index') }}" class="text-xs text-[#df747a] hover:underline flex items-center gap-1 mb-1 font-semibold">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    Back to Projects
                </a>
                <h1 class="text-2xl font-extrabold text-[#F8FAFC] font-brand-display tracking-tight">
                    Post a Project & Recruit Collaborators
                </h1>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-[#0B1120]">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="card bg-[#172033] border border-[#334155] shadow-2xl p-6 sm:p-8">

                <form method="POST" action="{{ route('projects.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Project Title *</label>
                        <input type="text" name="title" value="{{ old('title') }}" required placeholder="Enter project title"
                               class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-4 py-2.5 text-xs text-white focus:ring-2 focus:ring-[#8F3033]">
                        @error('title') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Category *</label>
                            <select name="category" required class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-4 py-2.5 text-xs text-white">
                                @foreach(\App\Models\CollaborationProject::CATEGORIES as $k => $label)
                                <option value="{{ $k }}" {{ old('category') === $k ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Development Stage *</label>
                            <select name="stage" required class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-4 py-2.5 text-xs text-white">
                                @foreach(\App\Models\CollaborationProject::STAGES as $k => $label)
                                <option value="{{ $k }}" {{ old('stage') === $k ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Problem Statement / Idea Pitch *</label>
                        <textarea name="problem_statement" rows="3" required placeholder="Enter problem statement or project pitch"
                                  class="w-full bg-[#0B1120] border border-[#334155] rounded-xl p-3 text-xs text-white">{{ old('problem_statement') }}</textarea>
                        @error('problem_statement') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Full Project Description & Scope *</label>
                        <textarea name="description" rows="5" required placeholder="Enter project scope and technical description"
                                  class="w-full bg-[#0B1120] border border-[#334155] rounded-xl p-3 text-xs text-white">{{ old('description') }}</textarea>
                        @error('description') <p class="text-xs text-rose-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Target Team Size (2-15) *</label>
                            <input type="number" name="team_size_target" value="{{ old('team_size_target', 4) }}" min="2" max="15" required
                                   class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-4 py-2 text-xs text-white">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Expected Time Commitment *</label>
                            <input type="text" name="expected_commitment" value="{{ old('expected_commitment', '3-5 hours / week') }}" required
                                   class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-4 py-2 text-xs text-white">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Skills Needed (Comma-separated)</label>
                            <input type="text" name="skills_needed" value="{{ old('skills_needed') }}" placeholder="Enter skills needed (comma-separated)"
                                   class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-4 py-2 text-xs text-white">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Preferred Roles (Comma-separated)</label>
                            <input type="text" name="preferred_roles" value="{{ old('preferred_roles') }}" placeholder="Enter preferred roles (comma-separated)"
                                   class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-4 py-2 text-xs text-white">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Repository URL (Optional)</label>
                            <input type="url" name="repository_url" value="{{ old('repository_url') }}" placeholder="Enter repository URL"
                                   class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-4 py-2 text-xs text-white">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Demo Link (Optional)</label>
                            <input type="url" name="demo_url" value="{{ old('demo_url') }}" placeholder="Enter demo or live preview URL"
                                   class="w-full bg-[#0B1120] border border-[#334155] rounded-xl px-4 py-2 text-xs text-white">
                        </div>
                    </div>

                    <div class="pt-4 border-t border-[#334155] flex items-center justify-end gap-3">
                        <a href="{{ route('projects.index') }}" class="btn-secondary text-xs py-2 px-4">Cancel</a>
                        <button type="submit" class="btn-primary text-xs py-2 px-6 shadow-lg">Publish Listing</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
