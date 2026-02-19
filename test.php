<?php
// Test sending a message
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:16688/api.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'action' => 'send',
    'username' => '测试用户',
    'text' => '你好世界！',
    'color' => '#6366f1',
    'target_lang' => 'zh'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

echo $response . "\n";
