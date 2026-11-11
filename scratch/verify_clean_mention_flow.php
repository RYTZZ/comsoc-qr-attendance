<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Announcement;
use App\Models\AnnouncementComment;

$sender = User::first();
$receiver = User::where('id', '!=', $sender->id)->first();
$announcement = Announcement::first();

// Post comment with clean mention in text and explicit mentioned_user_ids
$controller = $app->make(App\Http\Controllers\AnnouncementCommentController::class);
$request = Illuminate\Http\Request::create(
    route('announcements.comments.store', $announcement),
    'POST',
    [
        'content' => "@[{$receiver->name}](user:{$receiver->id}) welcome!",
        'mentioned_user_ids' => [$receiver->id],
    ]
);
$request->setUserResolver(fn() => $sender);

$response = $controller->store($request, $announcement);
$data = json_decode($response->getContent(), true);

echo "Posted status: " . $response->getStatusCode() . "\n";
echo "Comment content: " . $data['comment']['content'] . "\n";
echo "Notification count for receiver: " . App\Models\Notification::where('user_id', $receiver->id)->count() . "\n";

