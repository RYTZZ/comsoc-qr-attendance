<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$users = User::all()->map(function ($u) {
    return [
        'id' => $u->id,
        'name' => $u->name,
        'username' => $u->username,
        'display_username' => $u->display_username,
        'public_real_name' => $u->public_real_name,
        'nickname' => $u->nickname,
        'student_id' => $u->student_id,
        'student_number' => $u->student_number,
        'student' => $u->student ? [
            'first_name' => $u->student->first_name,
            'last_name' => $u->student->last_name,
            'student_number' => $u->student->student_number,
            'program' => $u->student->program,
            'year_level' => $u->student->year_level,
        ] : null,
    ];
});

echo json_encode($users, JSON_PRETTY_PRINT) . "\n";

