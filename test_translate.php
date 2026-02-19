<?php
// Test translation
$text = "Hello World";
$url = "https://api.mymemory.translated.net/get?q=" . urlencode($text) . "&langpair=en|zh";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
curl_close($ch);

echo "URL: $url\n";
echo "Response: $response\n";

$result = json_decode($response, true);
if ($result && $result['responseStatus'] == 200) {
    echo "Translated: " . $result['responseData']['translatedText'] . "\n";
} else {
    echo "Translation failed\n";
}
