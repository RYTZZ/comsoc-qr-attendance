<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\OrgAchievement;
use App\Models\OrgFaq;
use App\Models\OrgGalleryItem;
use App\Models\OrgOfficer;
use App\Models\OrgPartner;
use App\Services\WebsiteSettingsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicWebsiteController extends Controller
{
    /**
     * Homepage: Showcase identity, pillars, leadership, activities, achievements, gallery, partners, and FAQs.
     */
    public function index(): View
    {
        $settings = WebsiteSettingsService::all();

        // 1. Current published officers (display up to 8 for the homepage grid)
        $officers = OrgOfficer::public()->take(8)->get();

        // 2. Featured public activities (using existing events system with public flag)
        $activities = Event::publicShowcase()
            ->orderBy('event_date', 'desc')
            ->take(6)
            ->get();

        // 3. Verified achievements & milestones
        $achievements = OrgAchievement::public()->take(6)->get();

        // 4. Approved gallery items
        $gallery = OrgGalleryItem::public()->take(8)->get();

        // 5. Approved partners
        $partners = OrgPartner::public()->get();
        if ($partners->isEmpty()) {
            $defaultList = [
                [
                    'name' => 'CICT',
                    'logo_path' => 'images/CICT.png',
                    'description' => 'College of Information and Communications Technology — Academic department partner nurturing computing excellence and technical innovation.',
                    'collaboration_type' => 'Academic Collaborator',
                    'website_url' => null,
                    'sort_order' => 1,
                ],
                [
                    'name' => 'FMGTECH',
                    'logo_path' => 'images/FMGTECH.jpg',
                    'description' => 'FMGTECH — A technology-focused development group established under the Computing Society.',
                    'collaboration_type' => 'Organization under COMSOC',
                    'website_url' => null,
                    'sort_order' => 2,
                ],
                [
                    'name' => 'DICT',
                    'logo_path' => 'images/DICT.png',
                    'description' => 'Department of Information and Communications Technology — Spearheading national digital capabilities, cybersecurity, and ICT industry readiness.',
                    'collaboration_type' => 'Government Collaborator',
                    'website_url' => 'https://dict.gov.ph',
                    'sort_order' => 3,
                ],
                [
                    'name' => 'DEVCON LEGAZPI',
                    'logo_path' => 'images/DEVCON LEGAZPI LOGO.png',
                    'description' => 'Developer Connect (DEVCON) Legazpi Chapter — Driving developer meetups, hackathons, and grassroots tech engagement across the Bicol Region.',
                    'collaboration_type' => 'Community Collaborator',
                    'website_url' => 'https://devcon.ph',
                    'sort_order' => 4,
                ],
            ];

            $partners = collect(array_map(function ($item) {
                return new OrgPartner(array_merge($item, ['is_published' => true]));
            }, $defaultList));
        }

        // 6. FAQs
        $faqs = OrgFaq::public()->take(6)->get();

        return view('public.website.index', compact(
            'settings',
            'officers',
            'activities',
            'achievements',
            'gallery',
            'partners',
            'faqs'
        ));
    }

    /**
     * About page: Organization background, mission, vision, and pillars.
     */
    public function about(): View
    {
        $settings = WebsiteSettingsService::all();
        $president = OrgOfficer::public()
            ->where('position', 'like', '%President%')
            ->where('position', 'not like', '%Vice%')
            ->first();

        return view('public.website.about', compact('settings', 'president'));
    }

    /**
     * Officers page: Full leadership showcase across executive, board, and adviser roles.
     */
    public function officers(): View
    {
        $settings = WebsiteSettingsService::all();
        $officers = OrgOfficer::public()->get();

        $grouped = [
            'executive' => $officers->where('category', 'executive'),
            'board' => $officers->where('category', 'board'),
            'adviser' => $officers->where('category', 'adviser'),
        ];

        return view('public.website.officers', compact('settings', 'grouped', 'officers'));
    }

    /**
     * Activities page: Public activities, workshops, competitions, and projects.
     */
    public function activities(Request $request): View
    {
        $settings = WebsiteSettingsService::all();
        $filter = $request->query('status', 'all');

        $query = Event::publicShowcase()->orderByDesc('event_date');

        if ($filter === 'upcoming') {
            $query->where(function ($q) {
                $q->where('event_date', '>=', now()->toDateString())
                  ->orWhere('starts_at', '>=', now());
            });
        } elseif ($filter === 'completed') {
            $query->where(function ($q) {
                $q->where('status', 'completed')
                  ->orWhere('event_date', '<', now()->toDateString());
            });
        }

        $activities = $query->paginate(9)->withQueryString();

        return view('public.website.activities', compact('settings', 'activities', 'filter'));
    }

    /**
     * Individual activity details.
     */
    public function activityDetails(string $slug): View
    {
        $settings = WebsiteSettingsService::all();

        // Match by public_slug, id (uuid), or generated Str::slug(name)
        $event = Event::publicShowcase()
            ->where(function ($q) use ($slug) {
                $q->where('public_slug', $slug)
                  ->orWhere('id', $slug);
            })
            ->first();

        if (!$event) {
            // Check all public events if name slug matches
            $candidates = Event::publicShowcase()->get();
            $event = $candidates->first(fn($e) => $e->resolved_slug === $slug);
        }

        abort_unless($event, 404, 'Public activity not found.');

        // Fetch related gallery items if any
        $gallery = OrgGalleryItem::public()->where('event_id', $event->id)->get();

        return view('public.website.activity-detail', compact('settings', 'event', 'gallery'));
    }

    /**
     * Achievements page: Verified accomplishments, competition results, and milestones.
     */
    public function achievements(): View
    {
        $settings = WebsiteSettingsService::all();
        $achievements = OrgAchievement::public()->paginate(12);

        return view('public.website.achievements', compact('settings', 'achievements'));
    }

    /**
     * Gallery page: Approved organization photographs.
     */
    public function gallery(): View
    {
        $settings = WebsiteSettingsService::all();
        $gallery = OrgGalleryItem::public()->with('event')->paginate(16);

        return view('public.website.gallery', compact('settings', 'gallery'));
    }

    /**
     * Partners page: Approved partnerships and collaborators.
     */
    public function partners(): View
    {
        $settings = WebsiteSettingsService::all();
        $partners = OrgPartner::public()->get();
        if ($partners->isEmpty()) {
            $defaultList = [
                [
                    'name' => 'CICT',
                    'logo_path' => 'images/CICT.png',
                    'description' => 'College of Information and Communications Technology — Academic department partner nurturing computing excellence and technical innovation.',
                    'collaboration_type' => 'Academic Collaborator',
                    'website_url' => null,
                    'sort_order' => 1,
                ],
                [
                    'name' => 'FMGTECH',
                    'logo_path' => 'images/FMGTECH.jpg',
                    'description' => 'FMGTECH — A technology-focused development group established under the Computing Society.',
                    'collaboration_type' => 'Organization under COMSOC',
                    'website_url' => null,
                    'sort_order' => 2,
                ],
                [
                    'name' => 'DICT',
                    'logo_path' => 'images/DICT.png',
                    'description' => 'Department of Information and Communications Technology — Spearheading national digital capabilities, cybersecurity, and ICT industry readiness.',
                    'collaboration_type' => 'Government Collaborator',
                    'website_url' => 'https://dict.gov.ph',
                    'sort_order' => 3,
                ],
                [
                    'name' => 'DEVCON LEGAZPI',
                    'logo_path' => 'images/DEVCON LEGAZPI LOGO.png',
                    'description' => 'Developer Connect (DEVCON) Legazpi Chapter — Driving developer meetups, hackathons, and grassroots tech engagement across the Bicol Region.',
                    'collaboration_type' => 'Community Collaborator',
                    'website_url' => 'https://devcon.ph',
                    'sort_order' => 4,
                ],
            ];

            $partners = collect(array_map(function ($item) {
                return new OrgPartner(array_merge($item, ['is_published' => true]));
            }, $defaultList));
        }

        return view('public.website.partners', compact('settings', 'partners'));
    }
}
