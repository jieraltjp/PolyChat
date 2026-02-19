<?php
// Test Google translation
$text = "Hello World";
$url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl=zh&dt=t&q=" . urlencode($text);

$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'ignore_errors' => true
    ]
]);

$response = @file_get_contents($url, false, $context);

echo "URL: $url\n";
echo "Response: $response\n";

$result = json_decode($response, true);
if ($result && isset($result[0]) && is_array($result[0])) {
    $translated = '';
    foreach ($result[0] as $item) {
        if (isset($item[0])) {
            $translated .= $item[0];
        }
    }
    echo "Translated: $translated\n";
}
