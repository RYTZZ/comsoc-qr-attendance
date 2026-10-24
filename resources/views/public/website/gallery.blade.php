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

    <section class="py-12 sm:py-20 border-b border-[#334155]/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if($gallery->isEmpty())
                <div class="card bg-[#172033] border border-[#334155] p-12 text-center rounded-2xl text-slate-400 text-sm max-w-xl mx-auto">
                    Approved photographs will be uploaded here shortly.
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    @foreach($gallery as $photo)
                    <div class="card bg-[#172033] border border-[#334155] rounded-2xl overflow-hidden group flex flex-col justify-between hover:border-[#df747a] transition-all">
                        <div class="aspect-video relative overflow-hidden bg-[#0B1120]">
                            <img src="{{ asset('storage/' . $photo->image_path) }}" alt="{{ $photo->alt_text }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                        </div>

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
    </section>

</x-public-website-layout>
