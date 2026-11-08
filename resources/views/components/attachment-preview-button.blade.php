@props(['message', 'ticket'])

@php
    $name = $message->attachment_name ?: 'Attachment';
    $ext = strtolower(pathinfo((string) $message->attachment_path, PATHINFO_EXTENSION));
    $previewable = array_key_exists($ext, \App\Http\Controllers\MemberSupportTicketController::PREVIEW_TYPES);
    $isPdf = $ext === 'pdf';
    $disk = \Illuminate\Support\Facades\Storage::disk('local');
    $size = ($message->attachment_path && $disk->exists($message->attachment_path)) ? $disk->size($message->attachment_path) : null;
    $sizeLabel = $size === null ? 'Unknown' : ($size >= 1048576 ? number_format($size / 1048576, 2) . ' MB' : number_format($size / 1024, 1) . ' KB');
    $url = route('helpdesk.attachment.preview', [$ticket, $message]);
@endphp

<div x-data="{
        open: false, loading: false, error: null, src: null, zoom: 1,
        async show() {
            this.open = true; this.zoom = 1; this.error = null;
            @if(!$previewable) this.error = 'Preview is unavailable for this file type.'; return; @endif
            if (this.src) return;
            this.loading = true;
            try {
                const res = await fetch(@js($url), { credentials: 'same-origin', headers: { 'Accept': '*/*' } });
                if (res.status === 404) throw new Error('This file is missing or no longer available.');
                if (res.status === 415) throw new Error('Preview is unavailable for this file type.');
                if (!res.ok) throw new Error('Unable to load the preview.');
                this.src = URL.createObjectURL(await res.blob());
            } catch (e) { this.error = e.message; }
            this.loading = false;
        },
        close() { this.open = false; }
    }"
    @keydown.escape.window="close()"
    class="inline-block">
    <button type="button" @click="show()"
            class="inline-flex items-center gap-1.5 text-xs text-[#df747a] hover:underline font-semibold bg-slate-950 px-3 py-1.5 rounded-lg border border-slate-800">
        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
        <span>Preview {{ $name }}</span>
    </button>

    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
         role="dialog" aria-modal="true" aria-label="Attachment preview">
        <div @click.away="close()" class="card bg-[#172033] border border-slate-700 rounded-2xl w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden text-left">
            <div class="flex items-start justify-between gap-3 p-4 border-b border-slate-800">
                <div class="min-w-0">
                    <h3 class="text-sm font-bold text-white truncate">{{ $name }}</h3>
                    <p class="text-[11px] text-slate-400">{{ $ext ? strtoupper($ext) : 'Unknown' }} &bull; {{ $sizeLabel }}</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    @unless($isPdf)
                    <template x-if="src">
                        <div class="flex items-center gap-1">
                            <button type="button" @click="zoom = Math.max(0.5, zoom - 0.25)" class="btn-secondary text-xs px-2 py-1" aria-label="Zoom out">&minus;</button>
                            <button type="button" @click="zoom = Math.min(4, zoom + 0.25)" class="btn-secondary text-xs px-2 py-1" aria-label="Zoom in">+</button>
                        </div>
                    </template>
                    @endunless
                    <button type="button" @click="close()" class="btn-secondary text-xs px-3 py-1">Close</button>
                </div>
            </div>

            <div class="flex-1 min-h-[50vh] overflow-auto bg-[#0B1120] flex items-center justify-center p-3">
                <p x-show="loading" class="text-xs text-slate-400">Loading preview&hellip;</p>
                <p x-show="error" x-text="error" class="text-sm text-amber-300 text-center px-4"></p>
                @if($previewable && $isPdf)
                    <iframe x-show="src && !error" :src="src" title="PDF preview" class="w-full h-[70vh] rounded-lg border border-slate-800 bg-white"></iframe>
                @elseif($previewable)
                    <img x-show="src && !error" :src="src" alt="{{ $name }}"
                         class="max-w-full max-h-[70vh] object-contain transition-transform origin-center"
                         :style="`transform: scale(${zoom})`">
                @endif
            </div>
        </div>
    </div>
</div>

