<?php

namespace App\View\Components\Layouts;

use App\Services\WebsiteSettingsService;
use Illuminate\View\Component;
use Illuminate\View\View;

class PublicWebsite extends Component
{
    public array $settings;

    public function __construct(
        public ?string $title = null,
        ?array $settings = null,
        public ?string $metaTitle = null,
        public ?string $metaDescription = null
    ) {
        $this->settings = $settings ?? WebsiteSettingsService::all();
    }

    public function render(): View
    {
        return view('layouts.public-website');
    }
}
