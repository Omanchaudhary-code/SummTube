
<?php
/**
 * Test AI Service Connection
 * Run: php test_ai_connection.php
 */

require_once 'vendor/autoload.php';

echo "===========================================\n";
echo "SummTube - AI Service Connection Test\n";
echo "===========================================\n\n";

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Get config
$config = require 'config/app.php';
$aiUrl = rtrim($config['ai_service']['url'], '/');

echo "📍 Base URL: $aiUrl\n";
echo "📍 Health Endpoint: $aiUrl/health\n";
echo "📍 Summarize Endpoint: $aiUrl/summarize\n\n";

// Test 1: Health Check
echo "Test 1: Health Check\n";
echo "-------------------------------------------\n";
$ch = curl_init($aiUrl . '/health');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Error: $error\n\n";
} else {
    echo "✅ Status Code: $httpCode\n";
    echo "✅ Response: $response\n\n";
}

// Test 2: Summarize Endpoint
echo "Test 2: Generate Summary\n";
echo "-------------------------------------------\n";
$testUrl = 'https://www.youtube.com/watch?v=jNQXAC9IVRw';
echo "🎥 Video: $testUrl\n\n";

$ch = curl_init($aiUrl . '/summarize');
$payload = json_encode(['video_url' => $testUrl]);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ],
    CURLOPT_TIMEOUT => 30
]);

echo "⏳ Generating summary...\n";
$startTime = microtime(true);
$response = curl_exec($ch);
$endTime = microtime(true);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

$duration = round($endTime - $startTime, 2);

if ($error) {
    echo "❌ cURL Error: $error\n\n";
    exit(1);
}

echo "✅ Status Code: $httpCode\n";
echo "✅ Duration: {$duration}s\n\n";

if ($httpCode === 200) {
    $result = json_decode($response, true);
    if ($result) {
        echo "📦 Response Structure:\n";
        print_r(array_keys($result));
        echo "\n";
        
        echo "📝 Summary: " . substr($result['summary'] ?? 'N/A', 0, 100) . "...\n";
        echo "🎬 Title: " . ($result['video_title'] ?? $result['title'] ?? 'N/A') . "\n";
        echo "⏱️  Duration: " . ($result['video_duration'] ?? $result['duration'] ?? 'N/A') . "\n\n";
        
        echo "✅ AI Service is working correctly!\n";
    } else {
        echo "❌ Failed to decode JSON response\n";
        echo "Raw response: $response\n";
    }
} else {
    echo "❌ Request failed\n";
    echo "Response: $response\n";
}

echo "\n===========================================\n";
echo "Test Complete\n";
echo "===========================================\n";
