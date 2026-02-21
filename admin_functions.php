<?php
// Admin functions to append

// ========== 管理员功能 ==========

function isAdmin($user_id) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user && $user['role'] === 'admin';
}

function adminGetUsers() {
    global $pdo;
    
    $admin_id = isset($_GET['admin_id']) ? intval($_GET['admin_id']) : 0;
    
    if (!isAdmin($admin_id)) {
        echo json_encode(['success' => false, 'error' => 'Permission denied']);
        return;
    }
    
    $stmt = $pdo->query('SELECT id, username, email, color, role, status, last_active, created_at FROM users ORDER BY created_at DESC');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'users' => $users]);
}

function adminBanUser() {
    global $pdo;
    
    $admin_id = isset($_POST['admin_id']) ? intval($_POST['admin_id']) : 0;
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $ban = isset($_POST['ban']) ? intval($_POST['ban']) : 1;
    
    if (!isAdmin($admin_id)) {
        echo json_encode(['success' => false, 'error' => 'Permission denied']);
        return;
    }
    
    $status = $ban ? 'banned' : 'online';
    $stmt = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
    $stmt->execute([$status, $user_id]);
    
    echo json_encode(['success' => true]);
}

function adminGetStats() {
    global $pdo;
    
    $admin_id = isset($_GET['admin_id']) ? intval($_GET['admin_id']) : 0;
    
    if (!isAdmin($admin_id)) {
        echo json_encode(['success' => false, 'error' => 'Permission denied']);
        return;
    }
    
    $total_users = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $total_messages = $pdo->query('SELECT COUNT(*) FROM messages')->fetchColumn();
    $total_rooms = $pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn();
    $today_messages = $pdo->query("SELECT COUNT(*) FROM messages WHERE date(created_at) = date('now')")->fetchColumn();
    $online_users = $pdo->query("SELECT COUNT(*) FROM users WHERE last_active > datetime('now', '-5 minutes')")->fetchColumn();
    
    echo json_encode(['success' => true, 'stats' => [
        'total_users' => $total_users,
        'total_messages' => $total_messages,
        'total_rooms' => $total_rooms,
        'today_messages' => $today_messages,
        'online_users' => $online_users
    ]]);
}

function adminGetLogs() {
    global $pdo;
    
    $admin_id = isset($_GET['admin_id']) ? intval($_GET['admin_id']) : 0;
    
    if (!isAdmin($admin_id)) {
        echo json_encode(['success' => false, 'error' => 'Permission denied']);
        return;
    }
    
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $stmt = $pdo->query("SELECT * FROM logs ORDER BY created_at DESC LIMIT $limit");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'logs' => $logs]);
}

function adminDeleteMessage() {
    global $pdo;
    
    $admin_id = isset($_POST['admin_id']) ? intval($_POST['admin_id']) : 0;
    $msg_id = isset($_POST['msg_id']) ? intval($_POST['msg_id']) : 0;
    
    if (!isAdmin($admin_id)) {
        echo json_encode(['success' => false, 'error' => 'Permission denied']);
        return;
    }
    
    $stmt = $pdo->prepare('UPDATE messages SET is_deleted = 1 WHERE id = ?');
    $stmt->execute([$msg_id]);
    
    echo json_encode(['success' => true]);
}
