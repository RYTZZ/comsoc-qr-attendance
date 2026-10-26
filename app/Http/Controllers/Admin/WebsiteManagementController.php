<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\OrgAchievement;
use App\Models\OrgFaq;
use App\Models\OrgGalleryItem;
use App\Models\OrgOfficer;
use App\Models\OrgPartner;
use App\Models\Program;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\WebsiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WebsiteManagementController extends Controller
{
    /**
     * Settings Overview & Homepage Configuration.
     */
    public function settings(): View
    {
        $settings = WebsiteSettingsService::all();
        return view('admin.website.settings', compact('settings'));
    }

    /**
     * Update Homepage and Global Settings.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'org_name' => ['required', 'string', 'max:255'],
            'org_short_name' => ['required', 'string', 'max:50'],
            'org_institution' => ['required', 'string', 'max:255'],
            'org_tagline' => ['required', 'string', 'max:255'],
            'hero_headline' => ['required', 'string', 'max:255'],
            'hero_description' => ['required', 'string', 'max:1000'],
            'hero_cta_primary_label' => ['required', 'string', 'max:100'],
            'hero_cta_primary_url' => ['required', 'string', 'max:255'],
            'hero_cta_secondary_label' => ['required', 'string', 'max:100'],
            'hero_cta_secondary_url' => ['required', 'string', 'max:255'],
            'hero_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'hub_headline' => ['required', 'string', 'max:255'],
            'hub_description' => ['required', 'string', 'max:1000'],
            'final_cta_headline' => ['required', 'string', 'max:255'],
            'final_cta_description' => ['required', 'string', 'max:1000'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_address' => ['nullable', 'string', 'max:255'],
            'contact_facebook' => ['nullable', 'url', 'max:255'],
            'contact_github' => ['nullable', 'url', 'max:255'],
            'meta_title' => ['required', 'string', 'max:255'],
            'meta_description' => ['required', 'string', 'max:500'],
        ]);

        $updates = [
            'org_name' => $validated['org_name'],
            'org_short_name' => $validated['org_short_name'],
            'org_institution' => $validated['org_institution'],
            'org_tagline' => $validated['org_tagline'],
            'hero_headline' => $validated['hero_headline'],
            'hero_description' => $validated['hero_description'],
            'hero_cta_primary_label' => $validated['hero_cta_primary_label'],
            'hero_cta_primary_url' => $validated['hero_cta_primary_url'],
            'hero_cta_secondary_label' => $validated['hero_cta_secondary_label'],
            'hero_cta_secondary_url' => $validated['hero_cta_secondary_url'],
            'hub_headline' => $validated['hub_headline'],
            'hub_description' => $validated['hub_description'],
            'final_cta_headline' => $validated['final_cta_headline'],
            'final_cta_description' => $validated['final_cta_description'],
            'contact_email' => $validated['contact_email'] ?? null,
            'contact_address' => $validated['contact_address'] ?? null,
            'contact_facebook' => $validated['contact_facebook'] ?? null,
            'contact_github' => $validated['contact_github'] ?? null,
            'meta_title' => $validated['meta_title'],
            'meta_description' => $validated['meta_description'],
            'section_hero_visible' => $request->boolean('section_hero_visible', true),
            'section_about_visible' => $request->boolean('section_about_visible', true),
            'section_pillars_visible' => $request->boolean('section_pillars_visible', true),
            'section_officers_visible' => $request->boolean('section_officers_visible', true),
            'section_activities_visible' => $request->boolean('section_activities_visible', true),
            'section_achievements_visible' => $request->boolean('section_achievements_visible', true),
            'section_gallery_visible' => $request->boolean('section_gallery_visible', true),
            'section_partners_visible' => $request->boolean('section_partners_visible', true),
            'section_hub_cta_visible' => $request->boolean('section_hub_cta_visible', true),
            'section_faq_visible' => $request->boolean('section_faq_visible', true),
        ];

        if ($request->hasFile('hero_image')) {
            $updates['hero_image'] = $request->file('hero_image')->store('website', 'public');
        }

        WebsiteSettingsService::setMany($updates);
        AuditLogger::log('website.settings_updated', null, [], $updates);

        return back()->with('success', 'Website settings updated successfully.');
    }

    /**
     * Organization Profile (Mission, Vision, Pillars).
     */
    public function profile(): View
    {
        $settings = WebsiteSettingsService::all();
        return view('admin.website.profile', compact('settings'));
    }

    /**
     * Update Organization Profile & Pillars.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'org_intro' => ['required', 'string', 'max:1000'],
            'org_background' => ['required', 'string', 'max:3000'],
            'org_mission' => ['required', 'string', 'max:1500'],
            'org_vision' => ['required', 'string', 'max:1500'],
            'pillars' => ['required', 'array'],
            'pillars.*.id' => ['required', 'string'],
            'pillars.*.title' => ['required', 'string', 'max:100'],
            'pillars.*.description' => ['required', 'string', 'max:500'],
            'pillars.*.icon' => ['required', 'string', 'max:50'],
            'president_message_visible' => ['nullable', 'boolean'],
            'president_message_eyebrow' => ['nullable', 'string', 'max:100'],
            'president_message_headline' => ['nullable', 'string', 'max:255'],
            'president_message_quote' => ['nullable', 'string', 'max:1000'],
            'president_message_body' => ['nullable', 'string', 'max:5000'],
            'president_name' => ['nullable', 'string', 'max:255'],
            'president_title' => ['nullable', 'string', 'max:255'],
            'president_term' => ['nullable', 'string', 'max:100'],
            'president_portrait' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:3072'],
        ]);

        $paragraphs = array_values(array_filter(array_map('trim', preg_split("/\r\n|\n|\r/", $validated['president_message_body'] ?? ''))));

        $updates = [
            'org_intro' => $validated['org_intro'],
            'org_background' => $validated['org_background'],
            'org_mission' => $validated['org_mission'],
            'org_vision' => $validated['org_vision'],
            'pillars' => $validated['pillars'],
            'president_message_visible' => $request->boolean('president_message_visible', true),
            'president_message_eyebrow' => $validated['president_message_eyebrow'] ?? 'A Message From The President',
            'president_message_headline' => $validated['president_message_headline'] ?? 'A Computing Society That Does More',
            'president_message_quote' => $validated['president_message_quote'] ?? '',
            'president_message_paragraphs' => !empty($paragraphs) ? $paragraphs : (WebsiteSettingsService::DEFAULTS['president_message_paragraphs'] ?? []),
            'president_name' => $validated['president_name'] ?? 'John Mark M. Esperida',
            'president_title' => $validated['president_title'] ?? 'President, Computing Society',
            'president_term' => $validated['president_term'] ?? 'Academic Year 2025–2026',
        ];

        if ($request->hasFile('president_portrait')) {
            $updates['president_portrait'] = $request->file('president_portrait')->store('website', 'public');
        }

        WebsiteSettingsService::setMany($updates);

        AuditLogger::log('website.profile_updated', null, [], $updates);

        return back()->with('success', 'Organization profile, pillars, and President’s Message updated successfully.');
    }

    // ==========================================
    // OFFICERS MANAGEMENT
    // ==========================================

    public function officers(): View
    {
        $officers = OrgOfficer::orderBy('sort_order')->orderBy('name')->paginate(20);
        $users = User::whereIn('role', ['president', 'executive_secretary', 'treasurer', 'auditor', 'event_staff', 'staff', 'super_admin'])
            ->orWhereNotNull('officer_position')
            ->orderBy('name')
            ->get();
        $programs = Program::where('is_active', true)->get();

        return view('admin.website.officers', compact('officers', 'users', 'programs'));
    }

    public function storeOfficer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:150'],
            'program' => ['nullable', 'string', 'max:150'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'social_link' => ['nullable', 'url', 'max:500'],
            'category' => ['required', 'string', 'in:executive,board,liaison,bsit,bscs,bsis,btvted,adviser'],
            'sort_order' => ['nullable', 'integer'],
            'portrait' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:3072'],
            'is_current' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $portraitPath = null;
        if ($request->hasFile('portrait')) {
            $portraitPath = $request->file('portrait')->store('officers', 'public');
        }

        $officer = OrgOfficer::create([
            'user_id' => $validated['user_id'] ?? null,
            'name' => $validated['name'],
            'position' => $validated['position'],
            'program' => $validated['program'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'social_link' => $validated['social_link'] ?? null,
            'category' => $validated['category'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'portrait_path' => $portraitPath,
            'is_current' => $request->boolean('is_current', true),
            'is_published' => $request->boolean('is_published', true),
        ]);

        AuditLogger::log('website.officer_created', $officer);

        return back()->with('success', "Officer {$officer->name} added successfully.");
    }

    public function updateOfficer(Request $request, OrgOfficer $officer): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:150'],
            'program' => ['nullable', 'string', 'max:150'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'social_link' => ['nullable', 'url', 'max:500'],
            'category' => ['required', 'string', 'in:executive,board,liaison,bsit,bscs,bsis,btvted,adviser'],
            'sort_order' => ['nullable', 'integer'],
            'portrait' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:3072'],
            'is_current' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('portrait')) {
            if ($officer->portrait_path) {
                Storage::disk('public')->delete($officer->portrait_path);
            }
            $officer->portrait_path = $request->file('portrait')->store('officers', 'public');
        }

        $officer->update([
            'user_id' => $validated['user_id'] ?? null,
            'name' => $validated['name'],
            'position' => $validated['position'],
            'program' => $validated['program'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'social_link' => $validated['social_link'] ?? null,
            'category' => $validated['category'],
            'sort_order' => $validated['sort_order'] ?? $officer->sort_order,
            'is_current' => $request->boolean('is_current'),
            'is_published' => $request->boolean('is_published'),
        ]);

        AuditLogger::log('website.officer_updated', $officer);

        return back()->with('success', "Officer {$officer->name} updated successfully.");
    }

    public function destroyOfficer(OrgOfficer $officer): RedirectResponse
    {
        if ($officer->portrait_path) {
            Storage::disk('public')->delete($officer->portrait_path);
        }
        $name = $officer->name;
        $officer->delete();

        AuditLogger::log('website.officer_deleted');

        return back()->with('success', "Officer {$name} removed.");
    }

    // ==========================================
    // ACTIVITIES & EVENT PUBLIC VISIBILITY
    // ==========================================

    public function activities(): View
    {
        $events = Event::with('academicYear')
            ->orderByDesc('event_date')
            ->paginate(15);

        return view('admin.website.activities', compact('events'));
    }

    public function toggleEventPublic(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'is_public' => ['required', 'boolean'],
            'public_slug' => ['nullable', 'string', 'max:255'],
        ]);

        $slug = $validated['public_slug'] ? Str::slug($validated['public_slug']) : Str::slug($event->name);

        $event->update([
            'is_public' => $request->boolean('is_public'),
            'public_slug' => $slug,
        ]);

        AuditLogger::log('website.event_visibility_toggled', $event);

        return back()->with('success', "Event '{$event->name}' public visibility updated.");
    }

    // ==========================================
    // ACHIEVEMENTS MANAGEMENT
    // ==========================================

    public function achievements(): View
    {
        $achievements = OrgAchievement::orderBy('sort_order')->orderByDesc('created_at')->paginate(20);
        return view('admin.website.achievements', compact('achievements'));
    }

    public function storeAchievement(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'date_or_year' => ['nullable', 'string', 'max:100'],
            'award_details' => ['nullable', 'string', 'max:255'],
            'verification_reference' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:3072'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('achievements', 'public');
        }

        $achievement = OrgAchievement::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'date_or_year' => $validated['date_or_year'] ?? null,
            'award_details' => $validated['award_details'] ?? null,
            'verification_reference' => $validated['verification_reference'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'image_path' => $imagePath,
            'is_published' => $request->boolean('is_published', true),
        ]);

        AuditLogger::log('website.achievement_created', $achievement);

        return back()->with('success', 'Achievement added successfully.');
    }

    public function updateAchievement(Request $request, OrgAchievement $achievement): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'date_or_year' => ['nullable', 'string', 'max:100'],
            'award_details' => ['nullable', 'string', 'max:255'],
            'verification_reference' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:3072'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('image')) {
            if ($achievement->image_path) {
                Storage::disk('public')->delete($achievement->image_path);
            }
            $achievement->image_path = $request->file('image')->store('achievements', 'public');
        }

        $achievement->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'date_or_year' => $validated['date_or_year'] ?? null,
            'award_details' => $validated['award_details'] ?? null,
            'verification_reference' => $validated['verification_reference'] ?? null,
            'sort_order' => $validated['sort_order'] ?? $achievement->sort_order,
            'is_published' => $request->boolean('is_published'),
        ]);

        AuditLogger::log('website.achievement_updated', $achievement);

        return back()->with('success', 'Achievement updated successfully.');
    }

    public function destroyAchievement(OrgAchievement $achievement): RedirectResponse
    {
        if ($achievement->image_path) {
            Storage::disk('public')->delete($achievement->image_path);
        }
        $achievement->delete();
        AuditLogger::log('website.achievement_deleted');

        return back()->with('success', 'Achievement deleted.');
    }

    // ==========================================
    // GALLERY MANAGEMENT
    // ==========================================

    public function gallery(): View
    {
        $gallery = OrgGalleryItem::with('event')->orderBy('sort_order')->orderByDesc('created_at')->paginate(24);
        $events = Event::orderByDesc('event_date')->take(50)->get();

        return view('admin.website.gallery', compact('gallery', 'events'));
    }

    public function storeGalleryItem(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'title' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:500'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'event_id' => ['nullable', 'exists:events,id'],
            'sort_order' => ['nullable', 'integer'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $imagePath = $request->file('image')->store('gallery', 'public');

        $item = OrgGalleryItem::create([
            'image_path' => $imagePath,
            'title' => $validated['title'] ?? null,
            'caption' => $validated['caption'] ?? null,
            'alt_text' => $validated['alt_text'] ?? $validated['title'] ?? 'COMSOC Activity Photograph',
            'event_id' => $validated['event_id'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published', true),
        ]);

        AuditLogger::log('website.gallery_item_created', $item);

        return back()->with('success', 'Gallery photo uploaded successfully.');
    }

    public function destroyGalleryItem(OrgGalleryItem $item): RedirectResponse
    {
        if ($item->image_path) {
            Storage::disk('public')->delete($item->image_path);
        }
        $item->delete();
        AuditLogger::log('website.gallery_item_deleted');

        return back()->with('success', 'Gallery item deleted.');
    }

    // ==========================================
    // PARTNERS MANAGEMENT
    // ==========================================

    public function partners(): View
    {
        $partners = OrgPartner::orderBy('sort_order')->orderBy('name')->paginate(20);
        return view('admin.website.partners', compact('partners'));
    }

    public function storePartner(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'collaboration_type' => ['nullable', 'string', 'max:150'],
            'website_url' => ['nullable', 'url', 'max:500'],
            'sort_order' => ['nullable', 'integer'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('partners', 'public');
        }

        $partner = OrgPartner::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'collaboration_type' => $validated['collaboration_type'] ?? null,
            'website_url' => $validated['website_url'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'logo_path' => $logoPath,
            'is_published' => $request->boolean('is_published', true),
        ]);

        AuditLogger::log('website.partner_created', $partner);

        return back()->with('success', "Partner '{$partner->name}' added successfully.");
    }

    public function updatePartner(Request $request, OrgPartner $partner): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'collaboration_type' => ['nullable', 'string', 'max:150'],
            'website_url' => ['nullable', 'url', 'max:500'],
            'sort_order' => ['nullable', 'integer'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('logo')) {
            if ($partner->logo_path) {
                Storage::disk('public')->delete($partner->logo_path);
            }
            $partner->logo_path = $request->file('logo')->store('partners', 'public');
        }

        $partner->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'collaboration_type' => $validated['collaboration_type'] ?? null,
            'website_url' => $validated['website_url'] ?? null,
            'sort_order' => $validated['sort_order'] ?? $partner->sort_order,
            'is_published' => $request->boolean('is_published'),
        ]);

        AuditLogger::log('website.partner_updated', $partner);

        return back()->with('success', "Partner '{$partner->name}' updated.");
    }

    public function destroyPartner(OrgPartner $partner): RedirectResponse
    {
        if ($partner->logo_path) {
            Storage::disk('public')->delete($partner->logo_path);
        }
        $partner->delete();
        AuditLogger::log('website.partner_deleted');

        return back()->with('success', 'Partner removed.');
    }

    // ==========================================
    // FAQS MANAGEMENT
    // ==========================================

    public function faqs(): View
    {
        $faqs = OrgFaq::orderBy('sort_order')->orderBy('created_at')->paginate(20);
        return view('admin.website.faqs', compact('faqs'));
    }

    public function storeFaq(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['required', 'string', 'max:3000'],
            'category' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $faq = OrgFaq::create([
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'category' => $validated['category'] ?? 'General',
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published', true),
        ]);

        AuditLogger::log('website.faq_created', $faq);

        return back()->with('success', 'FAQ question added successfully.');
    }

    public function updateFaq(Request $request, OrgFaq $faq): RedirectResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['required', 'string', 'max:3000'],
            'category' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $faq->update([
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'category' => $validated['category'] ?? 'General',
            'sort_order' => $validated['sort_order'] ?? $faq->sort_order,
            'is_published' => $request->boolean('is_published'),
        ]);

        AuditLogger::log('website.faq_updated', $faq);

        return back()->with('success', 'FAQ question updated successfully.');
    }

    public function destroyFaq(OrgFaq $faq): RedirectResponse
    {
        $faq->delete();
        AuditLogger::log('website.faq_deleted');

        return back()->with('success', 'FAQ question deleted.');
    }
}
