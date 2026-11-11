<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$u = User::whereNotNull('student_id')->first();
$u->load(['student:id,first_name,last_name']);
echo "With id: " . ($u->student ? "LOADED" : "NULL") . "\n";

$u2 = User::whereNotNull('student_id')->first();
$u2->load(['student:user_id,first_name,last_name']);
echo "With user_id: " . ($u2->student ? "LOADED" : "NULL") . "\n";

