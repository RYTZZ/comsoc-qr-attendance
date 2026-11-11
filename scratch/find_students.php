<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "STUDENTS LIKE GERSALIA:\n";
$students = App\Models\Student::where('last_name', 'like', '%GERSALIA%')
    ->orWhere('first_name', 'like', '%CARL%')
    ->orWhere('first_name', 'like', '%JOHN%')
    ->get();
foreach ($students as $s) {
    echo "Student ID: " . $s->id . " | user_id: " . $s->user_id . " | Name: " . $s->first_name . " " . $s->last_name . "\n";
}

echo "\nUSERS TOTAL: " . App\Models\User::count() . "\n";
echo "FIRST 10 USERS:\n";
foreach (App\Models\User::take(10)->get() as $u) {
    echo "User ID: " . $u->id . " | Name: " . $u->name . " | Username: " . $u->username . " | Email: " . $u->email . "\n";
}

