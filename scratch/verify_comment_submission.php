<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Announcement;
use App\Models\AnnouncementComment;
use Illuminate\Support\Facades\Auth;

$user = User::first();
Auth::login($user);
$announcement = Announcement::first();

echo "Testing Comment Creation with Mentions...\n";

// Target a user to mention
$otherUser = User::where('id', '!=', $user->id)->first();
$mentionPayload = "Hello @[{$otherUser->name}](user:{$otherUser->id}) welcome to the discussion!";

$controller = $app->make(App\Http\Controllers\AnnouncementCommentController::class);
$request = Illuminate\Http\Request::create(
    route('announcements.comments.store', $announcement),
    'POST',
    ['content' => $mentionPayload]
);
$request->setUserResolver(fn() => $user);

$response = $controller->store($request, $announcement);
$data = json_decode($response->getContent(), true);

echo "Response status: " . $response->getStatusCode() . "\n";
echo "Response success: " . ($data['success'] ? 'true' : 'false') . "\n";
echo "Created comment ID: " . $data['comment']['id'] . "\n";
echo "Comment content: " . $data['comment']['content'] . "\n";
echo "Comment author: " . $data['comment']['author']['name'] . "\n";

// Verify DB entry
$commentDb = AnnouncementComment::find($data['comment']['id']);
echo "DB content matches: " . ($commentDb->content === $mentionPayload ? 'YES' : 'NO') . "\n";
echo "DB mentions count: " . $commentDb->mentions()->count() . "\n";
if ($commentDb->mentions()->count() > 0) {
    echo "Mentioned user ID: " . $commentDb->mentions()->first()->id . " (Expected: {$otherUser->id})\n";
}

