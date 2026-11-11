<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "ALL USERS:\n";
foreach (App\Models\User::all() as $u) {
    echo "- User: [{$u->id}] '{$u->name}' (username: '{$u->username}', email: '{$u->email}')\n";
}

echo "\nSTUDENT COUNT: " . App\Models\Student::count() . "\n";
echo "FIRST 5 STUDENTS:\n";
foreach (App\Models\Student::take(5)->get() as $s) {
    echo "- Student: [{$s->id}] user_id: '{$s->user_id}' '{$s->first_name} {$s->last_name}' ({$s->student_number})\n";
}

