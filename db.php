<?php
// 数据库初始化 - PolyChat v2.0
$db_file = __DIR__ . '/chat.db';

try {
    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 用户表
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT,
        email TEXT,
        avatar TEXT,
        color TEXT DEFAULT '#6366f1',
        role TEXT DEFAULT 'user',
        status TEXT DEFAULT 'online',
        last_active DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // 消息表
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        room_id INTEGER DEFAULT 1,
        parent_id INTEGER DEFAULT 0,
        original_text TEXT NOT NULL,
        translated_text TEXT,
        original_lang TEXT DEFAULT 'auto',
        target_lang TEXT DEFAULT 'zh',
        emoji TEXT DEFAULT '',
        likes INTEGER DEFAULT 0,
        liked_by TEXT DEFAULT '[]',
        is_deleted INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
    
    // 房间表
    $pdo->exec("CREATE TABLE IF NOT EXISTS rooms (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        type TEXT DEFAULT 'public',
        created_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // 任务表 (用于任务模式房间)
    $pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        room_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        completed INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (room_id) REFERENCES rooms(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
    
    // 创建默认公共房间
    $pdo->exec("INSERT OR IGNORE INTO rooms (id, name, description, type) VALUES (1, '公共聊天室', '所有人可以在这里聊天', 'public')");
    
    // 添加缺失的列 (兼容旧数据库)
    $columns = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_ASSOC);
    $existing_cols = array_column($columns, 'name');
    
    if (!in_array('password', $existing_cols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN password TEXT");
    }
    if (!in_array('email', $existing_cols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN email TEXT");
    }
    if (!in_array('avatar', $existing_cols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar TEXT");
    }
    if (!in_array('role', $existing_cols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role TEXT DEFAULT 'user'");
    }
    if (!in_array('status', $existing_cols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN status TEXT DEFAULT 'online'");
    }
    if (!in_array('last_active', $existing_cols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN last_active DATETIME");
    }
    
    // 消息表新增列
    $msg_columns = $pdo->query("PRAGMA table_info(messages)")->fetchAll(PDO::FETCH_ASSOC);
    $existing_msg_cols = array_column($msg_columns, 'name');
    
    if (!in_array('room_id', $existing_msg_cols)) {
        $pdo->exec("ALTER TABLE messages ADD COLUMN room_id INTEGER DEFAULT 1");
    }
    if (!in_array('parent_id', $existing_msg_cols)) {
        $pdo->exec("ALTER TABLE messages ADD COLUMN parent_id INTEGER DEFAULT 0");
    }
    if (!in_array('is_deleted', $existing_msg_cols)) {
        $pdo->exec("ALTER TABLE messages ADD COLUMN is_deleted INTEGER DEFAULT 0");
    }
    if (!in_array('updated_at', $existing_msg_cols)) {
        $pdo->exec("ALTER TABLE messages ADD COLUMN updated_at DATETIME");
    }
    
    // 创建索引 (性能优化)
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_messages_room ON messages(room_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_messages_created ON messages(created_at DESC)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_messages_user ON messages(user_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_last_active ON users(last_active)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_tasks_room ON tasks(room_id)");
    
    // 私信表
    $pdo->exec("CREATE TABLE IF NOT EXISTS private_messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        from_user_id INTEGER NOT NULL,
        to_user_id INTEGER NOT NULL,
        original_text TEXT NOT NULL,
        translated_text TEXT,
        is_read INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
} catch (PDOException $e) {
    die("数据库错误: " . $e->getMessage());
}

// 时区设置
date_default_timezone_set('Asia/Tokyo');

// ========== 数据库操作函数 ==========

function getUserByName($username) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function createUser($username, $color) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO users (username, color, last_active) VALUES (?, ?, datetime('now'))");
    $stmt->execute([$username, $color]);
    return $pdo->lastInsertId();
}

function saveMessage($user_id, $text, $translated, $orig_lang, $target_lang, $emoji = '', $room_id = 1, $parent_id = 0) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO messages (user_id, room_id, parent_id, original_text, translated_text, original_lang, target_lang, emoji) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $room_id, $parent_id, $text, $translated, $orig_lang, $target_lang, $emoji]);
    return $pdo->lastInsertId();
}

function db_likeMessage($msg_id, $username) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT likes, liked_by FROM messages WHERE id = ?");
    $stmt->execute([$msg_id]);
    $msg = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($msg) {
        $liked_by = json_decode($msg['liked_by'] ?: '[]', true);
        if (!in_array($username, $liked_by)) {
            $liked_by[] = $username;
            $new_likes = $msg['likes'] + 1;
            $stmt = $pdo->prepare("UPDATE messages SET likes = ?, liked_by = ? WHERE id = ?");
            $stmt->execute([$new_likes, json_encode($liked_by), $msg_id]);
            return ['success' => true, 'likes' => $new_likes];
        } else {
            $liked_by = array_diff($liked_by, [$username]);
            $new_likes = max(0, $msg['likes'] - 1);
            $stmt = $pdo->prepare("UPDATE messages SET likes = ?, liked_by = ? WHERE id = ?");
            $stmt->execute([$new_likes, json_encode(array_values($liked_by)), $msg_id]);
            return ['success' => true, 'likes' => $new_likes, 'unliked' => true];
        }
    }
    return ['success' => false];
}

function translateText($text, $from = 'auto', $to = 'zh') {
    // 获取翻译服务配置
    global $pdo;
    $translator = 'google'; // 默认
    
    try {
        $stmt = $pdo->prepare("SELECT value FROM config WHERE key = 'translator'");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) $translator = $row['value'];
    } catch (Exception $e) {}
    
    if ($translator === 'google') {
        return translateWithGoogle($text, $from, $to);
    } else {
        // 使用本地翻译服务
        $url = "http://localhost:16689/?action=translate&text=" . urlencode($text) . "&from=" . $from . "&to=" . $to;
        
        $context = stream_context_create([
            'http' => ['timeout' => 10, 'ignore_errors' => true]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response) {
            $result = json_decode($response, true);
            if ($result && isset($result['success']) && $result['success']) {
                return $result['translated'];
            }
        }
    }
    
    return $text;
}

function translateWithGoogle($text, $from = 'auto', $to = 'zh') {
    // 获取 Google API Key
    global $pdo;
    $apiKey = '';
    
    try {
        $stmt = $pdo->prepare("SELECT value FROM config WHERE key = 'google_api_key'");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) $apiKey = $row['value'];
    } catch (Exception $e) {}
    
    if (empty($apiKey)) {
        error_log("Google Translate: No API key");
        return $text;
    }
    
    // Google Translate API
    $url = "https://translation.googleapis.com/language/translate/v2?key=" . $apiKey;
    $data = [
        'q' => $text,
        'source' => $from === 'auto' ? '' : $from,
        'target' => $to,
        'format' => 'text'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    error_log("Google Translate response: $response, error: $error");
    
    if ($response) {
        $result = json_decode($response, true);
        if (isset($result['data']['translations'][0]['translatedText'])) {
            return $result['data']['translations'][0]['translatedText'];
        }
    }
    
    return $text;
}

function getConfig($key, $default = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT value FROM config WHERE key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

function setConfig($key, $value) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO config (key, value) VALUES (?, ?)");
    $stmt->execute([$key, $value]);
}
