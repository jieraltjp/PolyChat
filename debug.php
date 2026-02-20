<?php
// Debug - check what's being received
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : 'none');
$method = $_SERVER['REQUEST_METHOD'];

file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " $method - action: $action\n", FILE_APPEND);

echo json_encode(['received_action' => $action, 'method' => $method, 'post' => $_POST]);
