<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Announcement;
use App\Models\User;

$user = User::first();
if (!$user) {
    echo "No user found\n";
    exit;
}

$announcement = Announcement::first();
if (!$announcement) {
    $announcement = Announcement::create([
        'title' => 'COMSOC General Assembly 2026',
        'body' => 'Welcome all Computing Society members to our 2026 general assembly! Feel free to share your thoughts, questions, or tag colleagues in the discussion below.',
        'posted_by' => $user->id,
        'priority' => 'high',
        'category' => 'general',
        'target_audience_type' => 'all',
        'is_active' => true,
        'published_at' => now(),
    ]);
    echo "Created announcement: " . $announcement->id . "\n";
} else {
    echo "Existing announcement: " . $announcement->id . "\n";
}
