<?php
/**
 * Test Amazon Nova Model
 * Verify the nova-micro-v1:0 model works with your API key
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== Testing Amazon Nova Model ===<br/><br/>";

require_once 'config.php';
require_once 'lib_bedrock_client.php';

echo "Configuration:<br/>";
echo "  Region: " . AWS_REGION . "<br/>";
echo "  Model: " . BEDROCK_MODEL_ID . "<br/>";
echo "  API Key: " . (empty(BEDROCK_API_KEY) ? 'NOT SET' : 'Set (' . substr(BEDROCK_API_KEY, 0, 20) . '...)') . "<br/><br/>";

if (empty(BEDROCK_API_KEY)) {
    echo "❌ ERROR: API key not configured<br/>";
    echo "Please add your Bedrock API key to config.php<br/>";
    exit(1);
}

// Test 1: Simple text generation
echo "Test 1: Simple text generation<br/>";
echo "Prompt: 'What is the Capital City of Eritrea'<br/>";

$client = new BedrockClient(BEDROCK_API_KEY, AWS_REGION, BEDROCK_MODEL_ID);

try {
    $start = microtime(true);
    $response = $client->converse("What is the Capital City of Eritrea", ['maxTokens' => 50]);
    $duration = round(microtime(true) - $start, 2);
    
    if ($response !== null) {
        echo "✓ SUCCESS!<br/>";
        echo "Response: \"$response\"<br/>";
        echo "Duration: {$duration}s<br/><br/>";
    } else {
        echo "✗ FAILED: API returned null<br/>";
        echo "Check error logs for details<br/><br/>";
        
        // Try to get more info
        echo "Possible issues:<br/>";
        echo "1. Model ID 'amazon.nova-micro-v1:0' may not be available in your region<br/>";
        echo "2. API key may not have access to Nova models<br/>";
        echo "3. API key may be expired (30-day limit)<br/><br/>";
        
        echo "Try switching back to Claude:<br/>";
        echo "define('BEDROCK_MODEL_ID', 'us.anthropic.claude-3-5-haiku-20241022-v1:0');<br/><br/>";
    }
} catch (Exception $e) {
    echo "✗ EXCEPTION: " . $e->getMessage() . "<br/><br/>";
}

// Test 2: JSON response
echo "Test 2: JSON response generation<br/>";
echo "Prompt: 'Return JSON with status and score'<br/>";

try {
    $prompt = 'Return only this JSON without any explanation: {"status": "working", "score": 0.95}';
    $start = microtime(true);
    $response = $client->converse($prompt, ['maxTokens' => 100]);
    $duration = round(microtime(true) - $start, 2);
    
    if ($response !== null) {
        echo "✓ SUCCESS!<br/>";
        echo "Response: \"$response\"<br/>";
        echo "Duration: {$duration}s<br/>";
        
        // Try to parse JSON
        $json = json_decode($response, true);
        if ($json !== null) {
            echo "✓ Valid JSON parsed<br/>";
        } else {
            echo "⚠ Response is not valid JSON (may need prompt adjustment)<br/>";
        }
        echo "<br/>";
    } else {
        echo "✗ FAILED: API returned null<br/><br/>";
    }
} catch (Exception $e) {
    echo "✗ EXCEPTION: " . $e->getMessage() . "<br/><br/>";
}

// Test 3: Article analysis
echo "Test 3: Article analysis (realistic use case)<br/>";

try {
    $prompt = "Analyze this article for relevance to Healthcare industry:<br/><br/>";
    $prompt .= "Title: Nairobi Water Embarks on Mass Disconnection of Illegal connections and Defaulting Accounts<br/>";
    $prompt .= "Content: Studies by the government have shown that Kenya loses Sh10 billion per year through non-revenue water.<br/><br/>";
    $prompt .= "Provide analysis in JSON format: {\"score\": 0.0-1.0, \"explanation\": \"brief explanation\"}";
    
    $start = microtime(true);
    $response = $client->converse($prompt, ['maxTokens' => 200]);
    $duration = round(microtime(true) - $start, 2);
    
    if ($response !== null) {
        echo "✓ SUCCESS!<br/>";
        echo "Response: \"$response\"<br/>";
        echo "Duration: {$duration}s<br/><br/>";
    } else {
        echo "✗ FAILED: API returned null<br/><br/>";
    }
} catch (Exception $e) {
    echo "✗ EXCEPTION: " . $e->getMessage() . "<br/><br/>";
}

echo "=== Summary ===<br/><br/>";

if (BEDROCK_MODEL_ID === 'amazon.nova-micro-v1:0') {
    echo "You're using Amazon Nova Micro model.<br/><br/>";
    echo "Nova models are:<br/>";
    echo "✓ Faster and cheaper than Claude<br/>";
    echo "✓ Good for simple tasks<br/>";
    echo "⚠ May have different output format<br/>";
    echo "⚠ May not be available in all regions<br/><br/>";
    
    echo "If tests failed, consider:<br/>";
    echo "1. Switching back to Claude 3.5 Haiku (more reliable)<br/>";
    echo "2. Checking if Nova is available in us-east-1<br/>";
    echo "3. Verifying API key has Nova model access<br/>";
} else {
    echo "You're using: " . BEDROCK_MODEL_ID . "<br/>";
}

echo "<br/>=== Test Complete ===<br/>";
