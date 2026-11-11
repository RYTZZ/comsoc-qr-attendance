<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Announcement;
use Illuminate\Support\Facades\Auth;

$user = User::first();
Auth::login($user);

$announcement = Announcement::first();

// Render the view
$view = view('announcements.show', [
    'announcement' => $announcement,
    'acknowledged' => false,
    'reactionsConfig' => config('announcements.reactions', []),
])->render();

file_put_contents(__DIR__ . '/rendered_announcement.html', $view);
echo "Rendered announcement view to scratch/rendered_announcement.html (length: " . strlen($view) . " bytes)\n";
