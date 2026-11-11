<?php
$dbPath = 'c:/Users/FixAdmin/Desktop/comsoc-qr-attendance/database/database.sqlite.checkpoint';
if (file_exists($dbPath)) {
    $pdo = new PDO("sqlite:$dbPath");
    $users = $pdo->query("SELECT id, name, username, email FROM users")->fetchAll(PDO::FETCH_ASSOC);
    echo "Users in checkpoint DB:\n";
    foreach ($users as $u) {
        echo "- {$u['name']} (@{$u['username']}) - {$u['email']}\n";
    }
} else {
    echo "Checkpoint does not exist\n";
}

