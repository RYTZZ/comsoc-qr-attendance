<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class WebsiteSettingsService
{
    public const CACHE_KEY = 'comsoc_website_settings';
    public const CACHE_TTL = 3600; // 1 hour

    /**
     * Default public website content configuration.
     */
    public const DEFAULTS = [
        // Organization Profile
        'org_name' => 'Computing Society (COMSOC)',
        'org_short_name' => 'COMSOC',
        'org_institution' => 'Sorsogon State University – Bulan Campus',
        'org_tagline' => 'Innovating Today for a Better Community.',
        'org_intro' => 'The official student organization representing computing students at Sorsogon State University – Bulan Campus.',
        'org_background' => 'The Computing Society (COMSOC) serves as the vanguard academic and professional student organization for computing disciplines at Sorsogon State University – Bulan Campus. Through technical workshops, collaborative software initiatives, community outreaches, and hackathons, COMSOC empowers future technologists to excel in academia and industry.',
        'org_mission' => 'To cultivate a vibrant, inclusive environment for computing students through technical innovation, hands-on collaboration, and academic excellence that serves both the campus and the wider community.',
        'org_vision' => 'To be a premier student-led computing society recognized for producing skilled, ethical, and community-driven technology leaders and innovators in the Bicol region.',

        // Hero Section
        'hero_headline' => 'Building Skills. Creating Opportunities. Connecting Communities.',
        'hero_description' => 'COMSOC brings computing students together to develop their skills, turn ideas into meaningful projects, build connections, and create experiences that strengthen our campus community.',
        'hero_cta_primary_label' => 'Discover COMSOC',
        'hero_cta_primary_url' => '/about',
        'hero_cta_secondary_label' => 'Access COMSOC Hub',
        'hero_cta_secondary_url' => '/login',
        'hero_image' => null,

        // Pillars
        'pillars' => [
            [
                'id' => 'skills',
                'title' => 'Skills',
                'description' => 'Honing technical aptitude, software development mastery, and digital literacy through hands-on learning and peer code labs.',
                'icon' => 'code-2',
            ],
            [
                'id' => 'creation',
                'title' => 'Creation',
                'description' => 'Turning concepts into tangible, deployable real-world software, systems, and open-source contributions.',
                'icon' => 'sparkles',
            ],
            [
                'id' => 'connection',
                'title' => 'Connection',
                'description' => 'Bridging students with industry professionals, alumni networks, developer circles, and technology partners.',
                'icon' => 'network',
            ],
            [
                'id' => 'experience',
                'title' => 'Experience',
                'description' => 'Immersing members in hackathons, team governance, organizational operations, and agile project delivery.',
                'icon' => 'compass',
            ],
            [
                'id' => 'community',
                'title' => 'Community',
                'description' => 'Fostering an inclusive, supportive peer ecosystem that gives back through digital transformation and outreach.',
                'icon' => 'users',
            ],
        ],

        // Section Visibility Toggles
        'section_hero_visible' => true,
        'section_about_visible' => true,
        'section_pillars_visible' => true,
        'section_officers_visible' => true,
        'section_activities_visible' => true,
        'section_achievements_visible' => true,
        'section_gallery_visible' => true,
        'section_partners_visible' => true,
        'section_hub_cta_visible' => true,
        'section_faq_visible' => true,

        // Platform Integration Section
        'hub_headline' => 'Your COMSOC Experience Starts Here.',
        'hub_description' => 'Access your student profile, participate in activities, track eligible attendance, view your EXP, and discover available rewards through COMSOC Hub.',
        'hub_login_url' => '/login',
        'hub_register_url' => '/signup',

        // Final CTA
        'final_cta_headline' => 'Be Part of the Community. Build What Comes Next.',
        'final_cta_description' => 'Whether you are a freshman writing your first line of code or an aspiring developer preparing for industry, COMSOC welcomes you.',

        // Public Contact & Socials (Verified only)
        'contact_email' => 'computingsocietysorsubc@gmail.com',
        'contact_address' => 'Zone 8, Bulan, Sorsogon, Philippines 4706',
        'contact_facebook' => 'https://facebook.com/COMSOCSorSUBulan',
        'contact_github' => 'https://github.com/RYTZZ/comsoc-qr-attendance',

        // SEO
        'meta_title' => 'Computing Society (COMSOC) — SorSU Bulan Campus',
        'meta_description' => 'Official website of the Computing Society Organization (COMSOC) of Sorsogon State University – Bulan Campus. Discover our leadership, activities, pillars, and COMSOC Hub.',
        'meta_og_image' => null,
    ];

    /**
     * Retrieve all website settings with defaults merged.
     */
    public static function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $raw = Setting::get('website_config', []);
            if (!is_array($raw)) {
                $raw = [];
            }
            return array_merge(self::DEFAULTS, $raw);
        });
    }

    /**
     * Retrieve a specific website setting.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();
        return $all[$key] ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    /**
     * Save updated website settings and invalidate cache.
     */
    public static function setMany(array $values): void
    {
        $current = Setting::get('website_config', []);
        if (!is_array($current)) {
            $current = [];
        }

        $merged = array_merge($current, $values);
        Setting::set('website_config', $merged, 'json');

        self::clearCache();
    }

    /**
     * Clear cached website settings.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
