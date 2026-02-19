<?php
// PolyChat API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/db.php';

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

switch ($action) {
    case 'send':
        sendMessage();
        break;
    case 'messages':
        getMessages();
        break;
    case 'users':
        getOnlineUsers();
        break;
    case 'like':
        likeMessage();
        break;
    default:
        echo json_encode(['error' => 'Unknown action']);
}

function sendMessage() {
    global $pdo;
    
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $text = isset($_POST['text']) ? trim($_POST['text']) : '';
    $targetLang = isset($_POST['target_lang']) ? $_POST['target_lang'] : 'zh';
    $color = isset($_POST['color']) ? $_POST['color'] : '#6366f1';
    $emoji = isset($_POST['emoji']) ? $_POST['emoji'] : '';
    
    if (empty($username) || empty($text)) {
        echo json_encode(['success' => false, 'error' => 'Missing parameters']);
        return;
    }
    
    // 获取或创建用户
    $user = getUserByName($username);
    if (!$user) {
        $user_id = createUser($username, $color);
        $user = ['id' => $user_id, 'username' => $username, 'color' => $color];
    }
    
    // 翻译消息
    $translated = translateText($text, 'auto', $targetLang);
    
    // 保存消息
    $msg_id = saveMessage($user['id'], $text, $translated, 'auto', $targetLang, $emoji);
    
    echo json_encode([
        'success' => true,
        'message' => [
            'id' => $msg_id,
            'username' => $username,
            'color' => $user['color'],
            'original_text' => $text,
            'translated_text' => $translated,
            'emoji' => $emoji,
            'likes' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
}

function getMessages() {
    global $pdo;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $stmt = $pdo->query("
        SELECT m.*, u.username, u.color 
        FROM messages m 
        JOIN users u ON m.user_id = u.id 
        ORDER BY m.created_at DESC 
        LIMIT $limit
    ");
    $messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo json_encode(['success' => true, 'messages' => $messages]);
}

function getOnlineUsers() {
    global $pdo;
    $stmt = $pdo->query("SELECT username, color FROM users ORDER BY created_at DESC LIMIT 20");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'users' => $users]);
}

function likeMessage() {
    global $pdo;
    
    $msg_id = isset($_POST['msg_id']) ? intval($_POST['msg_id']) : 0;
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    
    if (empty($msg_id) || empty($username)) {
        echo json_encode(['success' => false, 'error' => 'Missing parameters']);
        return;
    }
    
    $result = db_likeMessage($msg_id, $username);
    echo json_encode($result);
}
