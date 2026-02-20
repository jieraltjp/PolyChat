<?php
// PolyChat API v2.0 - 完整API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 时区设置
date_default_timezone_set('Asia/Tokyo');

require_once __DIR__ . '/db.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

// 路由处理
switch ($action) {
    // ========== 认证 ==========
    case 'register':
        registerUser();
        break;
    case 'login':
        loginUser();
        break;
    case 'logout':
        logoutUser();
        break;
    
    // ========== 用户 ==========
    case 'profile':
        getProfile();
        break;
    case 'update_profile':
        updateProfile();
        break;
    case 'users':
        getUsers();
        break;
    
    // ========== 消息 ==========
    case 'messages':
        getMessages();
        break;
    case 'send':
        sendMessage();
        break;
    case 'message':
        handleMessage();
        break;
    case 'like':
        likeMessage();
        break;
    
    // ========== 房间 ==========
    case 'rooms':
        getRooms();
        break;
    case 'create_room':
        createRoom();
        break;
    
    // ========== 配置 (管理员) ==========
    case 'config':
        handleConfig();
        break;
    
    // ========== SSE ==========
    case 'sse':
        // SSE 单独处理
        break;
    
    default:
        echo json_encode(['error' => 'Unknown action: ' . $action]);
}

// ========== 认证功能 ==========

function registerUser() {
    global $pdo;
    
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $color = isset($_POST['color']) ? $_POST['color'] : '#6366f1';
    
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'error' => '用户名和密码必填']);
        return;
    }
    
    if (strlen($username) < 2 || strlen($username) > 20) {
        echo json_encode(['success' => false, 'error' => '用户名2-20字符']);
        return;
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'error' => '密码至少6位']);
        return;
    }
    
    try {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, color) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $password_hash, $email, $color]);
        
        $user_id = $pdo->lastInsertId();
        
        // 创建 token (兼容 PHP 5.6)
        $token = bin2hex(openssl_random_pseudo_bytes(32));
        $stmt = $pdo->prepare("UPDATE users SET last_active = datetime('now') WHERE id = ?");
        $stmt->execute([$user_id]);
        
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $user_id,
                'username' => $username,
                'email' => $email,
                'color' => $color,
                'token' => $token
            ]
        ]);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'UNIQUE') !== false) {
            echo json_encode(['success' => false, 'error' => '用户名已存在']);
        } else {
            echo json_encode(['success' => false, 'error' => '注册失败']);
        }
    }
}

function loginUser() {
    global $pdo;
    
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'error' => '用户名和密码必填']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'error' => '用户名或密码错误']);
        return;
    }
    
    // 更新在线状态
    $token = bin2hex(openssl_random_pseudo_bytes(32));
    $stmt = $pdo->prepare("UPDATE users SET last_active = datetime('now') WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'color' => $user['color'],
            'role' => $user['role'],
            'token' => $token
        ]
    ]);
}

function logoutUser() {
    echo json_encode(['success' => true]);
}

// ========== 用户功能 ==========

function getProfile() {
    global $pdo;
    
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    
    $stmt = $pdo->prepare("SELECT id, username, email, color, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'error' => '用户不存在']);
    }
}

function updateProfile() {
    global $pdo;
    
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $color = isset($_POST['color']) ? $_POST['color'] : '';
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, color = ? WHERE id = ?");
        $stmt->execute([$username, $email, $color, $user_id]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => '更新失败']);
    }
}

function getUsers() {
    global $pdo;
    
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
    
    $stmt = $pdo->query("SELECT id, username, color, last_active, created_at FROM users ORDER BY last_active DESC LIMIT $limit");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'users' => $users]);
}

// ========== 消息功能 ==========

function getMessages() {
    global $pdo;
    
    $room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $before_id = isset($_GET['before_id']) ? intval($_GET['before_id']) : 0;
    
    $where = "m.room_id = $room_id AND m.is_deleted = 0";
    if ($before_id > 0) {
        $where .= " AND m.id < $before_id";
    }
    
    $stmt = $pdo->query("
        SELECT m.*, u.username, u.color 
        FROM messages m 
        JOIN users u ON m.user_id = u.id 
        WHERE $where
        ORDER BY m.created_at DESC 
        LIMIT $limit
    ");
    $messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    
    echo json_encode(['success' => true, 'messages' => $messages]);
}

function sendMessage() {
    global $pdo;
    
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $text = isset($_POST['text']) ? trim($_POST['text']) : '';
    $targetLang = isset($_POST['target_lang']) ? $_POST['target_lang'] : 'zh';
    $color = isset($_POST['color']) ? $_POST['color'] : '#6366f1';
    $room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 1;
    $emoji = isset($_POST['emoji']) ? $_POST['emoji'] : '';
    $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
    
    if (empty($username) || empty($text)) {
        echo json_encode(['success' => false, 'error' => '参数不完整']);
        return;
    }
    
    // 获取或创建用户
    $user = getUserByName($username);
    if (!$user) {
        $user_id = createUser($username, $color);
        $user = ['id' => $user_id, 'username' => $username, 'color' => $color];
    } else {
        $user_id = $user['id'];
    }
    
    // 翻译消息
    $translated = translateText($text, 'auto', $targetLang);
    
    // 保存消息
    $stmt = $pdo->prepare("
        INSERT INTO messages (user_id, room_id, parent_id, original_text, translated_text, original_lang, target_lang, emoji) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $room_id, $parent_id, $text, $translated, 'auto', $targetLang, $emoji]);
    $msg_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => [
            'id' => $msg_id,
            'user_id' => $user_id,
            'room_id' => $room_id,
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

function handleMessage() {
    global $pdo;
    
    $method = $_SERVER['REQUEST_METHOD'];
    $msg_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($method === 'PUT') {
        // 编辑消息
        $text = isset($_POST['text']) ? trim($_POST['text']) : '';
        
        $stmt = $pdo->prepare("UPDATE messages SET original_text = ?, updated_at = datetime('now') WHERE id = ?");
        $stmt->execute([$text, $msg_id]);
        
        echo json_encode(['success' => true]);
    } elseif ($method === 'DELETE') {
        // 软删除消息
        $stmt = $pdo->prepare("UPDATE messages SET is_deleted = 1 WHERE id = ?");
        $stmt->execute([$msg_id]);
        
        echo json_encode(['success' => true]);
    }
}

function likeMessage() {
    global $pdo;
    
    $msg_id = isset($_POST['msg_id']) ? intval($_POST['msg_id']) : 0;
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    
    if (empty($msg_id) || empty($username)) {
        echo json_encode(['success' => false, 'error' => '参数不完整']);
        return;
    }
    
    $result = db_likeMessage($msg_id, $username);
    echo json_encode($result);
}

// ========== 房间功能 ==========

function getRooms() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM rooms ORDER BY created_at DESC");
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'rooms' => $rooms]);
}

function createRoom() {
    global $pdo;
    
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $type = isset($_POST['type']) ? $_POST['type'] : 'public';
    $created_by = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => '房间名必填']);
        return;
    }
    
    $stmt = $pdo->prepare("INSERT INTO rooms (name, description, type, created_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $description, $type, $created_by]);
    
    $room_id = $pdo->lastInsertId();
    
    echo json_encode(['success' => true, 'room' => ['id' => $room_id, 'name' => $name]]);
}

function handleConfig() {
    global $pdo;
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    // 获取用户角色
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $isAdmin = false;
    
    if ($user_id > 0) {
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && $user['role'] === 'admin') {
            $isAdmin = true;
        }
    }
    
    if ($method === 'GET') {
        // 获取配置
        $key = isset($_GET['key']) ? $_GET['key'] : '';
        
        if ($key) {
            $value = getConfig($key);
            echo json_encode(['success' => true, 'key' => $key, 'value' => $value]);
        } else {
            // 获取所有配置
            $stmt = $pdo->query("SELECT * FROM config");
            $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $result = [];
            foreach ($configs as $c) {
                $result[$c['key']] = $c['value'];
            }
            echo json_encode(['success' => true, 'config' => $result]);
        }
    } elseif ($method === 'POST' && $isAdmin) {
        // 更新配置
        $key = isset($_POST['key']) ? $_POST['key'] : '';
        $value = isset($_POST['value']) ? $_POST['value'] : '';
        
        if ($key) {
            setConfig($key, $value);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Missing key']);
        }
    } elseif (!$isAdmin) {
        echo json_encode(['success' => false, 'error' => 'Permission denied']);
    }
}
