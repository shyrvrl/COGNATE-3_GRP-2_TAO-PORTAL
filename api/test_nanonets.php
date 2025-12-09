<?php
// api/test_key.php
header('Content-Type: text/plain');

// --- CREDENTIALS TO TEST ---
$api_key = "0a5fdcd8-d44f-11f0-890d-e6e0013317b1"; // The key you provided
$model_id = "a37018ed-3f0b-475a-a860-3f33607652e7"; // SHS Model ID

echo "Testing Connection...\n";
echo "Key: $api_key\n";
echo "Model: $model_id\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://app.nanonets.com/api/v2/OCR/Model/$model_id"); // Simple GET request info
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERPWD, "$api_key:");
$result = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "HTTP Code: " . $info['http_code'] . "\n";
echo "Response: " . $result . "\n";

if ($info['http_code'] == 200) {
    echo "\n✅ SUCCESS! The Key is valid.";
} else {
    echo "\n❌ FAILED! Please generate a NEW API KEY in Nanonets Settings.";
}
?>