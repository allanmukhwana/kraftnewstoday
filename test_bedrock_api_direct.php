<?php
/**
 * Direct Bedrock API Test - Debug API calls
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== Direct Bedrock API Test ===\n\n";

// Configuration
$api_key = 'ABSKQmVkcm9ja0FQSUtleS1mYWo1LWF0LTc0MTQ0ODkxNzkwMjpmeWFycnJTa2VrVmlqTTkyYkZKd2tCQTBCQ1R4c1ltUGZQekJlQ1lDZUVvb2tLZVJaNU9nVDhERXR0OD0=';
$region = 'us-east-1';
$model_id = 'us.anthropic.claude-3-5-haiku-20241022-v1:0';

echo "Configuration:\n";
echo "  API Key: " . substr($api_key, 0, 30) . "...\n";
echo "  Region: $region\n";
echo "  Model: $model_id\n\n";

// Build endpoint
$endpoint = "https://bedrock-runtime.{$region}.amazonaws.com/model/{$model_id}/converse";
echo "Endpoint: $endpoint\n\n";

// Build request payload
$payload = [
    'messages' => [
        [
            'role' => 'user',
            'content' => [
                ['text' => 'Say hello in 3 words']
            ]
        ]
    ],
    'inferenceConfig' => [
        'maxTokens' => 100,
        'temperature' => 0.7
    ]
];

$json_payload = json_encode($payload, JSON_PRETTY_PRINT);
echo "Request Payload:\n";
echo $json_payload . "\n\n";

// Prepare headers
$headers = [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $api_key,
    'Accept: application/json'
];

echo "Request Headers:\n";
foreach ($headers as $header) {
    if (strpos($header, 'Authorization') !== false) {
        echo "  Authorization: Bearer " . substr($api_key, 0, 20) . "...\n";
    } else {
        echo "  $header\n";
    }
}
echo "\n";

// Make request
echo "Making API request...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $endpoint);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_VERBOSE, true);

// Capture verbose output
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
$curl_info = curl_getinfo($ch);

curl_close($ch);

// Get verbose output
rewind($verbose);
$verbose_log = stream_get_contents($verbose);
fclose($verbose);

echo "\n=== Response ===\n\n";
echo "HTTP Code: $http_code\n";

if ($curl_error) {
    echo "cURL Error: $curl_error\n\n";
}

echo "Response Body:\n";
echo $response . "\n\n";

// Try to parse JSON response
if ($response) {
    $json_response = json_decode($response, true);
    if ($json_response) {
        echo "Parsed JSON Response:\n";
        print_r($json_response);
        echo "\n";
        
        // Try to extract text
        if (isset($json_response['output']['message']['content'][0]['text'])) {
            echo "✓ Extracted Text: " . $json_response['output']['message']['content'][0]['text'] . "\n";
        } else {
            echo "⚠ Could not find text in expected path\n";
        }
    } else {
        echo "⚠ Response is not valid JSON\n";
        echo "JSON Error: " . json_last_error_msg() . "\n";
    }
}

echo "\n=== cURL Info ===\n";
echo "Total Time: " . $curl_info['total_time'] . "s\n";
echo "Name Lookup Time: " . $curl_info['namelookup_time'] . "s\n";
echo "Connect Time: " . $curl_info['connect_time'] . "s\n";
echo "Pretransfer Time: " . $curl_info['pretransfer_time'] . "s\n";
echo "Starttransfer Time: " . $curl_info['starttransfer_time'] . "s\n";
echo "Size Download: " . $curl_info['size_download'] . " bytes\n";

if ($http_code >= 400) {
    echo "\n=== Error Analysis ===\n";
    echo "HTTP $http_code indicates:\n";
    
    switch ($http_code) {
        case 400:
            echo "  - Bad Request: Check payload format\n";
            break;
        case 401:
            echo "  - Unauthorized: API key is invalid or expired\n";
            break;
        case 403:
            echo "  - Forbidden: API key doesn't have permission\n";
            break;
        case 404:
            echo "  - Not Found: Model ID or endpoint incorrect\n";
            break;
        case 429:
            echo "  - Too Many Requests: Rate limit exceeded\n";
            break;
        case 500:
        case 502:
        case 503:
            echo "  - Server Error: AWS service issue\n";
            break;
        default:
            echo "  - Unknown error\n";
    }
}

echo "\n=== Verbose Log ===\n";
echo $verbose_log . "\n";

echo "\n=== Test Complete ===\n";
