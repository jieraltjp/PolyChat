<?php
// Set admin
$pdo = new PDO('sqlite:D:/Enviroment/OpenClaw/workspace/polychat/chat.db');
$pdo->exec("UPDATE users SET role = 'admin' WHERE username = 'jieralt'");

// Create config table
$pdo->exec("CREATE TABLE IF NOT EXISTS config (
    key TEXT PRIMARY KEY,
    value TEXT
)");

// Insert translation config
$config = [
    'translator' => 'google',
    'google_api_key' => 'AIzaSyC2koO9krfo0HlDQYdkzfNzjmCAqp0Stj0'
];

foreach ($config as $k => $v) {
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO config (key, value) VALUES (?, ?)");
    $stmt->execute([$k, $v]);
}

echo "Done! Admin set, config created.";
