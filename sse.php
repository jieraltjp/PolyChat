<?php
// PolyChat SSE - Server-Sent Events for real-time updates
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/db.php';

$last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 1;

function sendEvent($data) {
    echo "data: " . json_encode($data) . "\n\n";
    ob_flush();
    flush();
}

sendEvent(['type' => 'connected', 'last_id' => $last_id]);

$timeout = 30;
$start = time();

while (time() - $start < $timeout) {
    $stmt = $pdo->prepare("SELECT MAX(id) as max_id FROM messages WHERE room_id = ? AND is_deleted = 0");
    $stmt->execute([$room_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_max_id = $result['max_id'] ? intval($result['max_id']) : 0;
    
    if ($current_max_id > $last_id) {
        $stmt = $pdo->prepare("
            SELECT m.*, u.username, u.color 
            FROM messages m 
            JOIN users u ON m.user_id = u.id 
            WHERE m.room_id = ? AND m.is_deleted = 0
            ORDER BY m.created_at DESC 
            LIMIT 10
        ");
        $stmt->execute([$room_id]);
        $messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
        
        sendEvent([
            'type' => 'new_messages',
            'messages' => $messages,
            'last_id' => $current_max_id
        ]);
        
        break;
    }
    
    sleep(1);
}
