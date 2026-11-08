<x-layouts.admin :title="'Preview Form — ' . $form->title">
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.registration-forms.edit', $form) }}" class="text-xs text-slate-400 hover:text-white inline-flex items-center gap-1 mb-1">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                Back to Form Builder
            </a>
            <h1 class="text-2xl font-bold text-white font-heading">{{ $form->title }} (Preview · v{{ $form->version }})</h1>
            @if($form->description)
                <p class="text-xs text-slate-400 mt-1">{{ $form->description }}</p>
            @endif
        </div>
        <span class="px-3 py-1 rounded-full bg-amber-500/20 border border-amber-500/40 text-amber-300 text-xs font-bold">
            Super Admin Live Preview
        </span>
    </div>

    <div class="card bg-[#172033] border border-slate-800 p-6 space-y-6">
        <div class="p-4 rounded-xl bg-[#0B1120] border border-slate-800 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
            <div>
                <span class="text-[10px] text-slate-500 uppercase block">Authenticated Student</span>
                <span class="font-bold text-white">{{ auth()->user()->name }}</span>
            </div>
            <div>
                <span class="text-[10px] text-slate-500 uppercase block">Member ID</span>
                <span class="font-mono text-slate-300">{{ auth()->user()->member_id }}</span>
            </div>
            <div>
                <span class="text-[10px] text-slate-500 uppercase block">Program</span>
                <span class="text-slate-300">{{ auth()->user()->getProfileProgram() ?? 'BSCS (Auto-linked)' }}</span>
            </div>
            <div>
                <span class="text-[10px] text-slate-500 uppercase block">Year Level</span>
                <span class="text-slate-300">{{ auth()->user()->getProfileYearLevel() ?? '3rd Year (Auto-linked)' }}</span>
            </div>
        </div>

        <x-dynamic-form-fields :schema="$schema" namePrefix="preview_answers" />

        <div class="pt-4 border-t border-slate-800 flex justify-end">
            <button type="button" disabled class="btn-primary text-xs opacity-60 cursor-not-allowed">
                Submit Registration (Preview Mode)
            </button>
        </div>
    </div>
</div>
</x-layouts.admin>

