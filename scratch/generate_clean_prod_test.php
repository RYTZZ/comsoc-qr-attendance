<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Announcement;
use App\Models\AnnouncementComment;

// Clear scratch comments to keep test clean
AnnouncementComment::query()->delete();

$user = User::first();
auth()->login($user);
$announcement = Announcement::first();

$view = view('announcements.show', [
    'announcement' => $announcement,
    'acknowledged' => false,
    'reactionsConfig' => config('announcements.reactions', []),
])->render();

// Write to public/test_composer_prod.html
file_put_contents(__DIR__ . '/../public/test_composer_prod.html', $view);
echo "Written fresh view to public/test_composer_prod.html (bytes: " . strlen($view) . ")\n";

