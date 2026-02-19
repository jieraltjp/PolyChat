<?php
// PolyChat SSE - Server-Sent Events for real-time updates
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/db.php';

$last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;

// Set SSE headers
header('X-Accel-Buffering: no');

function sendEvent($data) {
    echo "data: " . json_encode($data) . "\n\n";
    ob_flush();
    flush();
}

// Send initial data
sendEvent(['type' => 'connected', 'last_id' => $last_id]);

// Long polling - check for new messages every second
$timeout = 30; // 30 seconds max
$start = time();

while (time() - $start < $timeout) {
    // Check for new messages
    $stmt = $pdo->prepare("SELECT MAX(id) as max_id FROM messages");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_max_id = $result['max_id'] ? intval($result['max_id']) : 0;
    
    if ($current_max_id > $last_id) {
        // Get new messages
        $stmt = $pdo->query("
            SELECT m.*, u.username, u.color 
            FROM messages m 
            JOIN users u ON m.user_id = u.id 
            ORDER BY m.created_at DESC 
            LIMIT 10
        ");
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
