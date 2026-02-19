<?php
// Test translation
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:16688/api.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'action' => 'send',
    'username' => '测试翻译',
    'text' => 'Hello everyone, how are you?',
    'color' => '#ec4899',
    'target_lang' => 'zh'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

echo $response . "\n";
