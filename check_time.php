<?php
require_once __DIR__ . '/db.php';

$stmt = $pdo->query("
    SELECT m.id, m.original_text, m.created_at, u.username 
    FROM messages m 
    JOIN users u ON m.user_id = u.id 
    ORDER BY m.id DESC 
    LIMIT 3
");
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Current PHP Time (Tokyo): " . date('Y-m-d H:i:s') . "\n\n";
echo "Server Timezone: " . date_default_timezone_get() . "\n\n";
foreach ($messages as $msg) {
    echo "ID: {$msg['id']}, User: {$msg['username']}, Time: {$msg['created_at']}\n";
}
