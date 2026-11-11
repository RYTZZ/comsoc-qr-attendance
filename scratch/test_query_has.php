<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

DB::enableQueryLog();
User::whereHas('student', function($q) { $q->where('first_name', 'like', '%test%'); })->get();
print_r(DB::getQueryLog());

