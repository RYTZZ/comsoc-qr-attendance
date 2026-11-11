<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$comments = App\Models\AnnouncementComment::all();
echo "Total comments: " . $comments->count() . "\n";
foreach ($comments as $c) {
    echo "- [{$c->id}] (Parent: " . ($c->parent_id ?? 'none') . ") '{$c->content}' by User {$c->user_id}\n";
}

