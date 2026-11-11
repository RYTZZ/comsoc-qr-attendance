<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'grietarytzz9009@gmail.com')->first();
if (!$user) {
    echo "User not found\n";
    exit(1);
}

// Generate session for this user
$sessionId = bin2hex(random_bytes(20));
$sessionData = [
    '_token' => bin2hex(random_bytes(20)),
    'login_web_' . sha1('Illuminate\Auth\SessionGuard') => $user->id,
];

Illuminate\Support\Facades\DB::table('sessions')->insert([
    'id' => $sessionId,
    'user_id' => $user->id,
    'ip_address' => '127.0.0.1',
    'user_agent' => 'Mozilla/5.0 Chrome Headless',
    'payload' => base64_encode(serialize($sessionData)),
    'last_activity' => time(),
]);

echo "Created session: " . $sessionId . "\n";
echo "Cookie value: " . encrypt($sessionId) . "\n";

