<?php

namespace App\View\Components;

use App\Models\Announcement;
use App\Models\AnnouncementReaction;
use Illuminate\View\Component;
use Illuminate\View\View;

class AnnouncementEngagementSection extends Component
{
    public function __construct(
        public Announcement $announcement
    ) {}

    public function render(): View
    {
        $reactionsConfig = AnnouncementReaction::TYPES;

        return view('components.announcement-engagement-section', [
            'announcement' => $this->announcement,
            'reactionsConfig' => $reactionsConfig,
        ]);
    }
}

