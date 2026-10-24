<x-public-website-layout :settings="$settings" :title="'Approved Gallery'">

    <section class="py-12 sm:py-20 border-b border-[#334155]/60 bg-[#0B1120]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto text-center space-y-4">
                <span class="text-xs font-bold text-[#df747a] tracking-widest uppercase font-brand-accent">Visual Archive</span>
                <h1 class="text-3xl sm:text-5xl font-extrabold text-white font-brand-display tracking-tight">
                    Approved Organization Gallery
                </h1>
                <p class="text-base text-slate-300 leading-relaxed">
                    A visual record of student assemblies, coding bootcamps, community days, and academic conferences.
                </p>
            </div>
        </div>
    </section>

    <section class="py-12 sm:py-20 border-b border-[#334155]/60" x-data="{ lightboxOpen: false, activeImage: '', activeTitle: '', activeCaption: '' }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if($gallery->isEmpty())
                <div class="card bg-[#172033] border border-[#334155] p-12 text-center rounded-2xl text-slate-400 text-sm max-w-xl mx-auto">
                    Approved photographs will be uploaded here shortly.
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    @foreach($gallery as $photo)
                    <div class="card bg-[#172033] border border-[#334155] rounded-2xl overflow-hidden group flex flex-col justify-between hover:border-[#df747a] transition-all">
                        <button type="button"
                                @click="lightboxOpen = true; activeImage = '{{ asset('storage/' . $photo->image_path) }}'; activeTitle = '{{ addslashes($photo->title ?? '') }}'; activeCaption = '{{ addslashes($photo->caption ?? '') }}'"
                                class="aspect-video relative overflow-hidden bg-[#0B1120] text-left block w-full focus:outline-none focus-visible:ring-2 focus-visible:ring-[#df747a] group cursor-pointer"
                                aria-label="View photo: {{ $photo->title ?: 'Gallery photo' }}">
                            <img src="{{ asset('storage/' . $photo->image_path) }}" alt="{{ $photo->alt_text }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <span class="p-2 rounded-xl bg-[#0B1120]/80 text-white border border-[#334155]">
                                    <i data-lucide="maximize-2" class="w-4 h-4"></i>
                                </span>
                            </div>
                        </button>

                        <div class="p-4 space-y-2">
                            @if($photo->title)
                                <h4 class="text-sm font-bold text-white font-brand-display">{{ $photo->title }}</h4>
                            @endif
                            @if($photo->caption)
                                <p class="text-xs text-slate-300 line-clamp-2 leading-relaxed">{{ $photo->caption }}</p>
                            @endif
                            @if($photo->event)
                                <div class="pt-2 border-t border-[#334155]/60 flex items-center gap-1.5 text-[11px] text-[#df747a]">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                                    <span class="truncate">{{ $photo->event->name }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="pt-8">
                    {{ $gallery->links() }}
                </div>
            @endif

        </div>

        <!-- Lightbox Modal Dialog -->
        <div x-show="lightboxOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @keydown.escape.window="lightboxOpen = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/90 backdrop-blur-md"
             style="display: none;"
             role="dialog"
             aria-modal="true"
             aria-label="Image Preview Lightbox">
            <div class="relative max-w-4xl w-full bg-[#172033] border border-[#334155] rounded-2xl overflow-hidden shadow-2xl"
                 @click.away="lightboxOpen = false">
                <div class="flex items-center justify-between p-4 border-b border-[#334155]/80 bg-[#0B1120]">
                    <h3 class="text-sm font-bold text-white truncate pr-4" x-text="activeTitle || 'Photo Preview'"></h3>
                    <button type="button"
                            @click="lightboxOpen = false"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-[#172033] focus:outline-none focus:ring-2 focus:ring-[#df747a]"
                            aria-label="Close dialog">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                <div class="max-h-[75vh] overflow-hidden flex items-center justify-center bg-black">
                    <img :src="activeImage" :alt="activeTitle || 'Gallery preview'" class="max-h-[70vh] w-auto max-w-full object-contain">
                </div>
                <div x-show="activeCaption" class="p-4 bg-[#0B1120] border-t border-[#334155]/80">
                    <p class="text-xs text-slate-300" x-text="activeCaption"></p>
                </div>
            </div>
        </div>
    </section>

</x-public-website-layout>
