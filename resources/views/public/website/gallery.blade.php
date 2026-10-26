<x-public-website-layout :settings="$settings" :title="'Approved Gallery'">

    <!-- Editorial Header -->
    <section class="py-12 sm:py-16 relative overflow-hidden bg-gradient-to-b from-[#0B1120] to-[#172033]/30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl space-y-4" data-reveal>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#560E0F]/20 border border-[#df747a]/30 text-[#df747a] text-xs font-mono font-semibold uppercase tracking-wider">
                    <i data-lucide="camera" class="w-3.5 h-3.5"></i>
                    <span>Visual Narrative</span>
                </div>
                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-white font-brand-display tracking-tight leading-tight">
                    Approved Organization Gallery
                </h1>
                <p class="text-base sm:text-lg text-slate-300 leading-relaxed max-w-2xl font-normal">
                    A visual archive capturing our community in action—from algorithmic challenges and hands-on hardware builds to university assemblies and peer mentorship sessions.
                </p>
            </div>
        </div>
    </section>

    <!-- Gallery Grid with Lightbox -->
    <section class="pt-8 sm:pt-10 pb-24 sm:pb-32" 
             x-data="{ 
                 lightboxOpen: false, 
                 activeImage: '', 
                 activeTitle: '', 
                 activeCaption: '' 
             }"
             x-init="$watch('lightboxOpen', value => {
                 if (value) {
                     document.body.classList.add('overflow-y-hidden');
                 } else {
                     document.body.classList.remove('overflow-y-hidden');
                 }
             })">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if($gallery->isEmpty())
                <div data-reveal class="py-16 text-center max-w-md mx-auto space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-[#172033]/40 border border-[#334155]/40 flex items-center justify-center mx-auto text-slate-400">
                        <i data-lucide="image-off" class="w-6 h-6"></i>
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-base font-bold text-white font-brand-display">Archive In Preparation</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Approved photographs will be uploaded here shortly.
                        </p>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6" data-reveal-group>
                    @foreach($gallery as $photo)
                    <article data-reveal-item class="group flex flex-col justify-between rounded-2xl bg-[#172033]/30 border border-[#334155]/30 p-4 hover:border-[#df747a]/50 transition-colors">
                        <button type="button"
                                @click="lightboxOpen = true; activeImage = '{{ asset('storage/' . $photo->image_path) }}'; activeTitle = '{{ addslashes($photo->title ?? '') }}'; activeCaption = '{{ addslashes($photo->caption ?? '') }}'"
                                class="aspect-[4/3] rounded-xl relative overflow-hidden bg-[#0B1120] text-left block w-full focus:outline-none focus-visible:ring-2 focus-visible:ring-[#df747a] group cursor-pointer border border-[#334155]/40 group-hover:border-[#df747a]/50 transition-all img-reveal-container"
                                aria-label="View photo: {{ $photo->title ?: 'Gallery photo' }}">
                            <img src="{{ asset('storage/' . $photo->image_path) }}" alt="{{ $photo->alt_text }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <span class="p-2.5 rounded-full bg-[#0B1120]/90 text-white border border-[#334155]/60 shadow-lg">
                                    <i data-lucide="maximize-2" class="w-4 h-4 text-[#df747a]"></i>
                                </span>
                            </div>
                        </button>

                        <div class="pt-3 space-y-1">
                            @if($photo->title)
                                <h2 class="text-sm font-bold text-white font-brand-display group-hover:text-[#df747a] transition-colors truncate">
                                    {{ $photo->title }}
                                </h2>
                            @endif
                            @if($photo->caption)
                                <p class="text-xs text-slate-300 line-clamp-2 leading-relaxed">
                                    {{ $photo->caption }}
                                </p>
                            @endif
                            @if($photo->event)
                                <div class="pt-2 flex items-center gap-1.5 text-[11px] text-[#df747a] font-mono">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 shrink-0"></i>
                                    <span class="truncate">{{ $photo->event->name }}</span>
                                </div>
                            @endif
                        </div>
                    </article>
                    @endforeach
                </div>

                @if($gallery->hasPages())
                <div class="pt-8">
                    {{ $gallery->links() }}
                </div>
                @endif
            @endif

        </div>

        <!-- Accessible Lightbox Modal Dialog (teleported/fixed with z-[100] and backdrop) -->
        <div x-show="lightboxOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @keydown.escape.window="lightboxOpen = false"
             class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 bg-black/90 backdrop-blur-md overflow-y-auto"
             style="display: none;"
             role="dialog"
             aria-modal="true"
             aria-label="Image Preview Lightbox">
            <div class="relative max-w-4xl w-full bg-[#0B1120] border border-[#334155] rounded-2xl overflow-hidden shadow-2xl my-auto"
                 @click.away="lightboxOpen = false">
                <div class="flex items-center justify-between p-4 border-b border-[#334155]/80 bg-[#172033]/60">
                    <h3 class="text-sm font-bold text-white truncate pr-4 font-brand-display" x-text="activeTitle || 'Photo Preview'"></h3>
                    <button type="button"
                            @click="lightboxOpen = false"
                            class="min-w-[40px] min-h-[40px] p-2 rounded-xl text-slate-300 hover:text-white hover:bg-[#172033] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#df747a] flex items-center justify-center transition-colors"
                            aria-label="Close photo preview">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <div class="max-h-[75vh] overflow-hidden flex items-center justify-center bg-black">
                    <img :src="activeImage" :alt="activeTitle || 'Gallery preview'" class="max-h-[70vh] w-auto max-w-full object-contain">
                </div>
                <div x-show="activeCaption" class="p-4 bg-[#0B1120] border-t border-[#334155]/80">
                    <p class="text-xs text-slate-300 leading-relaxed" x-text="activeCaption"></p>
                </div>
            </div>
        </div>
    </section>


</x-public-website-layout>
