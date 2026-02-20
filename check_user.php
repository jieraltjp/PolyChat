<?php
$pdo = new PDO('sqlite:D:/Enviroment/OpenClaw/workspace/polychat/chat.db');
$stmt = $pdo->query('SELECT id, username, role FROM users WHERE username = "jieralt"');
$user = $stmt->fetch(PDO::FETCH_ASSOC);
echo json_encode($user);
