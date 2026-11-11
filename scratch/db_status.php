<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "DB connection: " . config('database.default') . "\n";
echo "DB database: " . config('database.connections.' . config('database.default') . '.database') . "\n";
echo "Users count: " . App\Models\User::count() . "\n";
echo "Students count: " . App\Models\Student::count() . "\n";

$users = App\Models\User::all();
foreach ($users as $u) {
    echo "- User: [{$u->id}] '{$u->name}' | username: '{$u->username}' | email: '{$u->email}'\n";
}

