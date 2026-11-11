<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = App\Models\User::with('student')->where('username', 'like', '%kuya%')
    ->orWhere('name', 'like', '%GERSALIA%')
    ->orWhereHas('student', function($q) {
        $q->where('last_name', 'like', '%GERSALIA%');
    })->get();

foreach ($users as $u) {
    echo "ID: " . $u->id . " | Name: " . $u->name . " | Username: " . $u->username . " | Active: " . var_export($u->is_active, true) . " | Deleted: " . var_export($u->deleted_at, true) . "\n";
    if ($u->student) {
        echo "  Student: " . $u->student->first_name . " " . $u->student->last_name . " | Student#: " . $u->student->student_number . "\n";
    }
}

