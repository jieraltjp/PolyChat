<?php
// Test Google Translation
$text = 'Hello world';
$apiKey = 'AIzaSyC2koO9krfo0HlDQYdkzfNzjmCAqp0Stj0';

$url = "https://translation.googleapis.com/language/translate/v2?key=$apiKey";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
$postFields = json_encode([
    'q' => $text,
    'target' => 'zh',
    'format' => 'text'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
curl_close($ch);

echo $response . "\n";
