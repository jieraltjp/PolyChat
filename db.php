<?php
// 数据库初始化
$db_file = __DIR__ . '/chat.db';

try {
    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 创建表
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        color TEXT DEFAULT '#6366f1',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        original_text TEXT NOT NULL,
        translated_text TEXT,
        original_lang TEXT DEFAULT 'auto',
        target_lang TEXT DEFAULT 'zh',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
    
} catch (PDOException $e) {
    die("数据库错误: " . $e->getMessage());
}

function getUserByName($username) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function createUser($username, $color) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO users (username, color) VALUES (?, ?)");
    $stmt->execute([$username, $color]);
    return $pdo->lastInsertId();
}

function saveMessage($user_id, $text, $translated, $orig_lang, $target_lang) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO messages (user_id, original_text, translated_text, original_lang, target_lang) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $text, $translated, $orig_lang, $target_lang]);
    return $pdo->lastInsertId();
}

function db_getMessages($limit = 50) {
    global $pdo;
    $stmt = $pdo->query("
        SELECT m.*, u.username, u.color 
        FROM messages m 
        JOIN users u ON m.user_id = u.id 
        ORDER BY m.created_at DESC 
        LIMIT $limit
    ");
    return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
}

function translateText($text, $from = 'auto', $to = 'zh') {
    // 使用本地 Node.js 翻译服务
    $url = "http://localhost:16689/?action=translate&text=" . urlencode($text) . "&from=" . $from . "&to=" . $to;
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response) {
        $result = json_decode($response, true);
        if ($result && isset($result['success']) && $result['success']) {
            return $result['translated'];
        }
    }
    
    return $text; // 翻译失败返回原文
}
