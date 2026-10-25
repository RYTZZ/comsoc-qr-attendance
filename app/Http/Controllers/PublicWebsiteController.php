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

        // 1. Current published officers (display up to 6 for the homepage grid)
        $officers = OrgOfficer::public()->take(6)->get();
        if ($officers->isEmpty()) {
            $previewExecutive = [
                ['name' => 'Rytzz G. Grieta', 'position' => 'President', 'category' => 'executive', 'program' => null, 'portrait_path' => 'images/RYTZZ PFP.webp'],
                ['name' => 'John Alfred Vargas', 'position' => 'Vice President - Internal Affairs', 'category' => 'executive', 'program' => null],
                ['name' => 'Angel Desiree Cabales', 'position' => 'Vice President - External Affairs', 'category' => 'executive', 'program' => null],
                ['name' => 'Charlene Lizano', 'position' => 'Executive Secretary', 'category' => 'executive', 'program' => null],
                ['name' => 'Mark Janssen Palanca', 'position' => 'Secretary', 'category' => 'executive', 'program' => null],
                ['name' => 'Mharielle Mendieta', 'position' => 'Treasurer', 'category' => 'executive', 'program' => null],
            ];
            $officers = collect(array_map(fn($item) => new OrgOfficer($item), $previewExecutive));
        }

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
     * Officers page: Full leadership showcase across executive, board, liaisons, and program officers.
     */
    public function officers(): View
    {
        $settings = WebsiteSettingsService::all();
        $officers = OrgOfficer::public()->get();

        if ($officers->isEmpty()) {
            $defaultRoster = [
                // 1. COMSOC Executive Officers
                ['name' => 'Rytzz G. Grieta', 'position' => 'President', 'category' => 'executive', 'program' => null, 'portrait_path' => 'images/RYTZZ PFP.webp', 'sort_order' => 1],
                ['name' => 'John Alfred Vargas', 'position' => 'Vice President - Internal Affairs', 'category' => 'executive', 'program' => null, 'sort_order' => 2],
                ['name' => 'Angel Desiree Cabales', 'position' => 'Vice President - External Affairs', 'category' => 'executive', 'program' => null, 'sort_order' => 3],
                ['name' => 'Charlene Lizano', 'position' => 'Executive Secretary', 'category' => 'executive', 'program' => null, 'sort_order' => 4],
                ['name' => 'Mark Janssen Palanca', 'position' => 'Secretary', 'category' => 'executive', 'program' => null, 'sort_order' => 5],
                ['name' => 'Mharielle Mendieta', 'position' => 'Treasurer', 'category' => 'executive', 'program' => null, 'sort_order' => 6],
                ['name' => 'Emmanuel James Mape', 'position' => 'Assistant Treasurer', 'category' => 'executive', 'program' => null, 'sort_order' => 7],
                ['name' => 'Chiara Sentino', 'position' => 'Auditor', 'category' => 'executive', 'program' => null, 'sort_order' => 8],
                ['name' => 'Josh B. Bayoca', 'position' => 'Public Information Officer', 'category' => 'executive', 'program' => null, 'sort_order' => 9],
                ['name' => 'Emmanuel Polo', 'position' => 'Business Manager', 'category' => 'executive', 'program' => null, 'sort_order' => 10],
                ['name' => 'Rain Gludo', 'position' => 'Business Manager', 'category' => 'executive', 'program' => null, 'sort_order' => 11],

                // 2. COMSOC Board of Directors
                ['name' => 'Rhona Mae Borje', 'position' => 'Board of Director', 'category' => 'board', 'program' => 'BSCS', 'sort_order' => 12],
                ['name' => 'Ezekiel Lian Bertos', 'position' => 'Board of Director', 'category' => 'board', 'program' => 'BSCS', 'sort_order' => 13],
                ['name' => 'Alicia Mae Vidallo', 'position' => 'Board of Director', 'category' => 'board', 'program' => 'BSIT', 'sort_order' => 14],
                ['name' => 'Irish Yszha Merca', 'position' => 'Board of Director', 'category' => 'board', 'program' => 'BSIT', 'sort_order' => 15],
                ['name' => 'Hyacinth Gernale', 'position' => 'Board of Director', 'category' => 'board', 'program' => 'BSIS', 'sort_order' => 16],
                ['name' => 'Roxan Montes', 'position' => 'Board of Director', 'category' => 'board', 'program' => 'BSIS', 'sort_order' => 17],
                ['name' => 'Noriyn Padate', 'position' => 'Board of Director', 'category' => 'board', 'program' => 'BTVTED', 'sort_order' => 18],
                ['name' => 'Mariel Angela G. Rodrigo', 'position' => 'Board of Director', 'category' => 'board', 'program' => 'BTVTED', 'sort_order' => 19],

                // 3. Liaisons
                ['name' => 'Jenice Mae G. Gerero', 'position' => 'Liaison', 'category' => 'liaison', 'program' => null, 'sort_order' => 20],
                ['name' => 'Richard Viel Regala', 'position' => 'Liaison', 'category' => 'liaison', 'program' => null, 'sort_order' => 21],

                // 4. BSIT Program Officers
                ['name' => 'Altheo James H. Malate', 'position' => 'Vice President', 'category' => 'bsit', 'program' => 'BSIT', 'sort_order' => 22],
                ['name' => 'Emces Habla Martinez', 'position' => 'Secretary', 'category' => 'bsit', 'program' => 'BSIT', 'sort_order' => 23],
                ['name' => 'Trisha Ann G. Hermogino', 'position' => 'Treasurer', 'category' => 'bsit', 'program' => 'BSIT', 'sort_order' => 24],
                ['name' => 'Aubrey Kyle L. Magano', 'position' => 'Auditor', 'category' => 'bsit', 'program' => 'BSIT', 'sort_order' => 25],
                ['name' => 'Rysa Gobole', 'position' => 'Public Information Officer', 'category' => 'bsit', 'program' => 'BSIT', 'sort_order' => 26],
                ['name' => 'Michelle Balaoro', 'position' => 'Business Manager', 'category' => 'bsit', 'program' => 'BSIT', 'sort_order' => 27],
                ['name' => 'Kent Boribor', 'position' => 'Business Manager', 'category' => 'bsit', 'program' => 'BSIT', 'sort_order' => 28],

                // 5. BSCS Program Officers
                ['name' => 'Kean Jayvee Gito', 'position' => 'Vice President', 'category' => 'bscs', 'program' => 'BSCS', 'sort_order' => 29],
                ['name' => 'Julieta J. Rafanan', 'position' => 'Secretary', 'category' => 'bscs', 'program' => 'BSCS', 'sort_order' => 30],
                ['name' => 'Jiselle Fuedan', 'position' => 'Treasurer', 'category' => 'bscs', 'program' => 'BSCS', 'sort_order' => 31],
                ['name' => 'Mart Sid D. De Venecia', 'position' => 'Auditor', 'category' => 'bscs', 'program' => 'BSCS', 'sort_order' => 32],
                ['name' => 'Dane Josephine L. Fernandez', 'position' => 'Public Information Officer', 'category' => 'bscs', 'program' => 'BSCS', 'sort_order' => 33],
                ['name' => 'Kylie A. Estonanto', 'position' => 'Business Manager', 'category' => 'bscs', 'program' => 'BSCS', 'sort_order' => 34],
                ['name' => 'John Paul B. Carpio', 'position' => 'Business Manager', 'category' => 'bscs', 'program' => 'BSCS', 'sort_order' => 35],

                // 6. BSIS Program Officers
                ['name' => 'Thea Bernice Evangelista', 'position' => 'Vice President', 'category' => 'bsis', 'program' => 'BSIS', 'sort_order' => 36],
                ['name' => 'Awaiting Confirmation', 'position' => 'Secretary', 'category' => 'bsis', 'program' => 'BSIS', 'sort_order' => 37],
                ['name' => 'Awaiting Confirmation', 'position' => 'Treasurer', 'category' => 'bsis', 'program' => 'BSIS', 'sort_order' => 38],
                ['name' => 'Luisa Geocado', 'position' => 'Auditor', 'category' => 'bsis', 'program' => 'BSIS', 'sort_order' => 39],
                ['name' => 'Mico Gordola', 'position' => 'Public Information Officer', 'category' => 'bsis', 'program' => 'BSIS', 'sort_order' => 40],
                ['name' => 'Lance Gadbilao', 'position' => 'Business Manager', 'category' => 'bsis', 'program' => 'BSIS', 'sort_order' => 41],
                ['name' => 'Leandro Gutlay', 'position' => 'Business Manager', 'category' => 'bsis', 'program' => 'BSIS', 'sort_order' => 42],

                // 7. BTVTED Program Officers
                ['name' => 'Kristine Ann Ibañes', 'position' => 'Vice President', 'category' => 'btvted', 'program' => 'BTVTED', 'sort_order' => 43],
                ['name' => 'Razel G. Derilo', 'position' => 'Secretary', 'category' => 'btvted', 'program' => 'BTVTED', 'sort_order' => 44],
                ['name' => 'Prix Garlan', 'position' => 'Treasurer', 'category' => 'btvted', 'program' => 'BTVTED', 'sort_order' => 45],
                ['name' => 'Angelito Andaleon', 'position' => 'Auditor', 'category' => 'btvted', 'program' => 'BTVTED', 'sort_order' => 46],
                ['name' => 'Bernadett B. Barrun', 'position' => 'Public Information Officer', 'category' => 'btvted', 'program' => 'BTVTED', 'sort_order' => 47],
                ['name' => 'Angelica Guela', 'position' => 'Business Manager', 'category' => 'btvted', 'program' => 'BTVTED', 'sort_order' => 48],
                ['name' => 'Jeremy Dogillo', 'position' => 'Business Manager', 'category' => 'btvted', 'program' => 'BTVTED', 'sort_order' => 49],
            ];

            $officers = collect(array_map(function ($item) {
                return new OrgOfficer(array_merge($item, ['is_current' => true, 'is_published' => true]));
            }, $defaultRoster));
        }

        $grouped = [
            'executive' => $officers->where('category', 'executive'),
            'board' => $officers->where('category', 'board'),
            'liaison' => $officers->where('category', 'liaison'),
            'bsit' => $officers->where('category', 'bsit'),
            'bscs' => $officers->where('category', 'bscs'),
            'bsis' => $officers->where('category', 'bsis'),
            'btvted' => $officers->where('category', 'btvted'),
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

    /**
     * Terms of Use page: Official organizational terms and platform guidelines.
     */
    public function termsOfUse(): View
    {
        $settings = WebsiteSettingsService::all();
        return view('public.website.terms', compact('settings'));
    }
}
