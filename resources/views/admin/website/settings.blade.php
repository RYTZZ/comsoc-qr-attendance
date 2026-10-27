<x-admin-layout title="Website Settings & Homepage">

    <div class="space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-5">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-white font-brand-display">Public Website Management</h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-1">Configure homepage content, hero banners, section visibility, and portal links.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm flex items-center gap-2">
                <i data-lucide="check-circle-2" class="w-4 h-4 shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
                <p class="font-bold mb-1">Please correct the following errors:</p>
                <ul class="list-disc list-inside text-xs space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Management Navigation Tabs -->
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 pb-2 text-xs">
            <a href="{{ route('admin.website.settings') }}" class="px-3 py-1.5 rounded-lg bg-[#560E0F] text-white font-semibold">Homepage & General</a>
            <a href="{{ route('admin.website.profile') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">Organization Profile & Pillars</a>
            <a href="{{ route('admin.website.officers') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">Officers & Leadership</a>
            <a href="{{ route('admin.website.activities') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">Activities & Events Visibility</a>
            <a href="{{ route('admin.website.achievements') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">Achievements</a>
            <a href="{{ route('admin.website.gallery') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">Gallery</a>
            <a href="{{ route('admin.website.partners') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">Partners</a>
            <a href="{{ route('admin.website.faqs') }}" class="px-3 py-1.5 rounded-lg text-slate-300 hover:bg-slate-800">FAQs</a>
        </div>

        <form method="POST" action="{{ route('admin.website.settings.update') }}" enctype="multipart/form-data" class="space-y-8">
            @csrf

            <!-- Section 1: Hero Banner Settings -->
            <div class="card bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-4">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <i data-lucide="layout-template" class="w-4 h-4 text-[#df747a]"></i>
                    <span>Hero Section Configuration</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-xs font-semibold text-slate-300">Hero Headline</label>
                        <input type="text" name="hero_headline" value="{{ old('hero_headline', $settings['hero_headline']) }}" required class="form-input w-full text-sm">
                    </div>

                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-xs font-semibold text-slate-300">Hero Description</label>
                        <textarea name="hero_description" rows="3" required class="form-input w-full text-sm">{{ old('hero_description', $settings['hero_description']) }}</textarea>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-300">Primary CTA Label</label>
                        <input type="text" name="hero_cta_primary_label" value="{{ old('hero_cta_primary_label', $settings['hero_cta_primary_label']) }}" required class="form-input w-full text-sm">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-300">Primary CTA Link</label>
                        <input type="text" name="hero_cta_primary_url" value="{{ old('hero_cta_primary_url', $settings['hero_cta_primary_url']) }}" required class="form-input w-full text-sm">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-300">Secondary CTA Label</label>
                        <input type="text" name="hero_cta_secondary_label" value="{{ old('hero_cta_secondary_label', $settings['hero_cta_secondary_label']) }}" required class="form-input w-full text-sm">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-300">Secondary CTA Link</label>
                        <input type="text" name="hero_cta_secondary_url" value="{{ old('hero_cta_secondary_url', $settings['hero_cta_secondary_url']) }}" required class="form-input w-full text-sm">
                    </div>

                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-xs font-semibold text-slate-300">Hero Photograph / Showcase Banner (Optional)</label>
                        <input type="file" name="hero_image" accept="image/*" class="block w-full text-xs text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-white hover:file:bg-slate-700">
                        @if(!empty($settings['hero_image']))
                            <div class="mt-2 text-xs text-slate-400 flex items-center gap-2">
                                <span>Current:</span>
                                <a href="{{ asset('storage/' . $settings['hero_image']) }}" target="_blank" class="text-[#df747a] underline">View Image</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Section 2: General Organization Identity -->
            <div class="card bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-4">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <i data-lucide="building" class="w-4 h-4 text-[#df747a]"></i>
                    <span>General Identity & Institution</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-300">Organization Full Name</label>
                        <input type="text" name="org_name" value="{{ old('org_name', $settings['org_name']) }}" required class="form-input w-full text-sm">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-300">Short Acronym</label>
                        <input type="text" name="org_short_name" value="{{ old('org_short_name', $settings['org_short_name']) }}" required class="form-input w-full text-sm">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-300">Institution</label>
                        <input type="text" name="org_institution" value="{{ old('org_institution', $settings['org_institution']) }}" required class="form-input w-full text-sm">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-300">Official Tagline</label>
                        <input type="text" name="org_tagline" value="{{ old('org_tagline', $settings['org_tagline']) }}" required class="form-input w-full text-sm">
                    </div>
                </div>
            </div>

            <!-- Section 3: Section Visibility Toggles -->
            <div class="card bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-4">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <i data-lucide="eye" class="w-4 h-4 text-[#df747a]"></i>
                    <span>Homepage Section Visibility Toggles</span>
                </h3>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 text-xs">
                    <label class="flex items-center gap-2 p-2.5 rounded-lg bg-slate-950/60 border border-slate-800 cursor-pointer">
                        <input type="checkbox" name="section_hero_visible" value="1" {{ ($settings['section_hero_visible'] ?? true) ? 'checked' : '' }} class="rounded border-slate-700 bg-slate-900 text-[#df747a]">
                        <span>Hero Section</span>
                    </label>

                    <label class="flex items-center gap-2 p-2.5 rounded-lg bg-slate-950/60 border border-slate-800 cursor-pointer">
                        <input type="checkbox" name="section_about_visible" value="1" {{ ($settings['section_about_visible'] ?? true) ? 'checked' : '' }} class="rounded border-slate-700 bg-slate-900 text-[#df747a]">
                        <span>Who We Are</span>
                    </label>

                    <label class="flex items-center gap-2 p-2.5 rounded-lg bg-slate-950/60 border border-slate-800 cursor-pointer">
                        <input type="checkbox" name="section_pillars_visible" value="1" {{ ($settings['section_pillars_visible'] ?? true) ? 'checked' : '' }} class="rounded border-slate-700 bg-slate-900 text-[#df747a]">
                        <span>5 Pillars</span>
                    </label>

                    <label class="flex items-center gap-2 p-2.5 rounded-lg bg-slate-950/60 border border-slate-800 cursor-pointer">
                        <input type="checkbox" name="section_officers_visible" value="1" {{ ($settings['section_officers_visible'] ?? true) ? 'checked' : '' }} class="rounded border-slate-700 bg-slate-900 text-[#df747a]">
                        <span>Leadership</span>
                    </label>

                    <label class="flex items-center gap-2 p-2.5 rounded-lg bg-slate-950/60 border border-slate-800 cursor-pointer">
                        <input type="checkbox" name="section_activities_visible" value="1" {{ ($settings['section_activities_visible'] ?? true) ? 'checked' : '' }} class="rounded border-slate-700 bg-slate-900 text-[#df747a]">
                        <span>Activities</span>
                    </label>

                    <label class="flex items-center gap-2 p-2.5 rounded-lg bg-slate-950/60 border border-slate-800 cursor-pointer">
                        <input type="checkbox" name="section_achievements_visible" value="1" {{ ($settings['section_achievements_visible'] ?? true) ? 'checked' : '' }} class="rounded border-slate-700 bg-slate-900 text-[#df747a]">
                        <span>Achievements</span>
                    </label>

                    <label class="flex items-center gap-2 p-2.5 rounded-lg bg-slate-950/60 border border-slate-800 cursor-pointer">
                        <input type="checkbox" name="section_gallery_visible" value="1" {{ ($settings['section_gallery_visible'] ?? true) ? 'checked' : '' }} class="rounded border-slate-700 bg-slate-900 text-[#df747a]">
                        <span>Gallery</span>
                    </label>

                    <label class="flex items-center gap-2 p-2.5 rounded-lg bg-slate-950/60 border border-slate-800 cursor-pointer">
                        <input type="checkbox" name="section_partners_visible" value="1" {{ ($settings['section_partners_visible'] ?? true) ? 'checked' : '' }} class="rounded border-slate-700 bg-slate-900 text-[#df747a]">
                        <span>Partners</span>
                    </label>

                    <label class="flex items-center gap-2 p-2.5 rounded-lg bg-slate-950/60 border border-slate-800 cursor-pointer">
                        <input type="checkbox" name="section_hub_cta_visible" value="1" {{ ($settings['section_hub_cta_visible'] ?? true) ? 'checked' : '' }} class="rounded border-slate-700 bg-slate-900 text-[#df747a]">
                        <span>Hub CTA</span>
                    </label>

                    <label class="flex items-center gap-2 p-2.5 rounded-lg bg-slate-950/60 border border-slate-800 cursor-pointer">
                        <input type="checkbox" name="section_faq_visible" value="1" {{ ($settings['section_faq_visible'] ?? true) ? 'checked' : '' }} class="rounded border-slate-700 bg-slate-900 text-[#df747a]">
                        <span>FAQs</span>
                    </label>
                </div>
            </div>

            <!-- Section 4: Platform Integration & CTAs -->
            <div class="card bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-4">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <i data-lucide="terminal" class="w-4 h-4 text-[#df747a]"></i>
                    <span>COMSOC Hub Student Platform Integration</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-xs font-semibold text-slate-300">Hub Section Headline</label>
                        <input type="text" name="hub_headline" value="{{ old('hub_headline', $settings['hub_headline']) }}" required class="form-input w-full text-sm">
                    </div>

                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-xs font-semibold text-slate-300">Hub Section Description</label>
                        <textarea name="hub_description" rows="2" required class="form-input w-full text-sm">{{ old('hub_description', $settings['hub_description']) }}</textarea>
                    </div>

                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-xs font-semibold text-slate-300">Final Call to Action Headline</label>
                        <input type="text" name="final_cta_headline" value="{{ old('final_cta_headline', $settings['final_cta_headline']) }}" required class="form-input w-full text-sm">
                    </div>

                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-xs font-semibold text-slate-300">Final Call to Action Description</label>
                        <textarea name="final_cta_description" rows="2" required class="form-input w-full text-sm">{{ old('final_cta_description', $settings['final_cta_description']) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Section 5: Contact & SEO Settings -->
            <div class="card bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-4">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <i data-lucide="globe" class="w-4 h-4 text-[#df747a]"></i>
                    <span>Contact Details & SEO Meta</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-300">Official Contact Email</label>
                        <input type="email" name="contact_email" value="{{ old('contact_email', $settings['contact_email']) }}" class="form-input w-full text-sm">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-300">Physical Campus Address</label>
                        <input type="text" name="contact_address" value="{{ old('contact_address', $settings['contact_address']) }}" class="form-input w-full text-sm">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-300">Official Facebook Page URL</label>
                        <input type="url" name="contact_facebook" value="{{ old('contact_facebook', $settings['contact_facebook']) }}" class="form-input w-full text-sm">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-300">GitHub Repository URL</label>
                        <input type="url" name="contact_github" value="{{ old('contact_github', $settings['contact_github']) }}" class="form-input w-full text-sm">
                    </div>

                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-xs font-semibold text-slate-300">Meta Title</label>
                        <input type="text" name="meta_title" value="{{ old('meta_title', $settings['meta_title']) }}" required class="form-input w-full text-sm">
                    </div>

                    <div class="space-y-1.5 md:col-span-2">
                        <label class="text-xs font-semibold text-slate-300">Meta Description</label>
                        <textarea name="meta_description" rows="2" required class="form-input w-full text-sm">{{ old('meta_description', $settings['meta_description']) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                <button type="submit" class="btn-primary py-2.5 px-6 text-sm flex items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>Save Website Settings</span>
                </button>
            </div>
        </form>

    </div>

</x-admin-layout>
