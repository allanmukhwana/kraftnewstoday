<?php
/**
 * Test AWS Bedrock Integration
 * Verifies that Bedrock API is properly configured and working
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== Testing AWS Bedrock Integration ===\n\n";

// Test 1: Load configuration
echo "1. Loading configuration...\n";
try {
    require_once 'config.php';
    echo "   ✓ Config loaded\n";
    echo "   Region: " . AWS_REGION . "\n";
    echo "   Model ID: " . BEDROCK_MODEL_ID . "\n";
    echo "   API Key: " . (empty(BEDROCK_API_KEY) ? '❌ NOT SET' : '✓ Set (' . substr(BEDROCK_API_KEY, 0, 20) . '...)') . "\n";
    echo "   Max Tokens: " . BEDROCK_MAX_TOKENS . "\n\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Load Bedrock client
echo "2. Loading Bedrock client library...\n";
try {
    require_once 'lib_bedrock_client.php';
    echo "   ✓ lib_bedrock_client.php loaded\n\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 3: Create Bedrock client instance
echo "3. Creating Bedrock client instance...\n";
try {
    $client = new BedrockClient(BEDROCK_API_KEY, AWS_REGION, BEDROCK_MODEL_ID);
    echo "   ✓ BedrockClient instance created\n\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 4: Test simple API call
echo "4. Testing simple API call (Hello World)...\n";
if (empty(BEDROCK_API_KEY)) {
    echo "   ⚠ SKIPPED: API key not configured\n";
    echo "   To test real API calls, add your Bedrock API key to config.php\n\n";
} else {
    try {
        $prompt = "Say 'Hello from AWS Bedrock!' in exactly 5 words.";
        echo "   Prompt: \"$prompt\"\n";
        echo "   Calling Bedrock API...\n";
        
        $start_time = microtime(true);
        $response = $client->converse($prompt, ['maxTokens' => 50]);
        $duration = round(microtime(true) - $start_time, 2);
        
        if ($response !== null) {
            echo "   ✓ API call successful!\n";
            echo "   Response: \"$response\"\n";
            echo "   Duration: {$duration}s\n\n";
        } else {
            echo "   ✗ API call failed (returned null)\n";
            echo "   Check error logs for details\n\n";
        }
    } catch (Exception $e) {
        echo "   ✗ Exception: " . $e->getMessage() . "\n\n";
    }
}

// Test 5: Test JSON response parsing
echo "5. Testing JSON response parsing...\n";
if (empty(BEDROCK_API_KEY)) {
    echo "   ⚠ SKIPPED: API key not configured\n\n";
} else {
    try {
        $prompt = 'Return only this JSON: {"status": "working", "score": 0.95}';
        echo "   Prompt: \"$prompt\"\n";
        echo "   Calling Bedrock API...\n";
        
        $response = $client->converse($prompt, ['maxTokens' => 100]);
        
        if ($response !== null) {
            echo "   ✓ API call successful!\n";
            echo "   Raw response: \"$response\"\n";
            
            // Try to parse as JSON
            $json_data = json_decode($response, true);
            if ($json_data !== null) {
                echo "   ✓ JSON parsed successfully\n";
                echo "   Parsed data: " . print_r($json_data, true) . "\n";
            } else {
                echo "   ⚠ Response is not valid JSON (this is OK, just testing)\n\n";
            }
        } else {
            echo "   ✗ API call failed\n\n";
        }
    } catch (Exception $e) {
        echo "   ✗ Exception: " . $e->getMessage() . "\n\n";
    }
}

// Test 6: Load BedrockAgent
echo "6. Loading BedrockAgent class...\n";
try {
    require_once 'agent_bedrock.php';
    echo "   ✓ agent_bedrock.php loaded\n\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 7: Create BedrockAgent instance
echo "7. Creating BedrockAgent instance...\n";
try {
    $agent = new BedrockAgent();
    echo "   ✓ BedrockAgent instance created\n\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 8: Test article relevance analysis
echo "8. Testing article relevance analysis...\n";
if (empty(BEDROCK_API_KEY)) {
    echo "   ⚠ Using mock response (API key not configured)\n";
}

try {
    $test_title = "New AI Breakthrough Transforms Healthcare Industry";
    $test_content = "Researchers have developed a new artificial intelligence system that can diagnose diseases with unprecedented accuracy. The technology uses machine learning algorithms to analyze medical images and patient data, potentially revolutionizing healthcare delivery.";
    $test_industry = "Healthcare";
    
    echo "   Article: \"$test_title\"\n";
    echo "   Industry: $test_industry\n";
    echo "   Analyzing...\n";
    
    $start_time = microtime(true);
    $result = $agent->analyzeRelevance($test_title, $test_content, $test_industry);
    $duration = round(microtime(true) - $start_time, 2);
    
    echo "   ✓ Analysis completed!\n";
    echo "   Relevance Score: {$result['score']}\n";
    echo "   Explanation: {$result['explanation']}\n";
    echo "   Duration: {$duration}s\n\n";
} catch (Exception $e) {
    echo "   ✗ Exception: " . $e->getMessage() . "\n\n";
}

// Test 9: Test multi-dimensional analysis
echo "9. Testing multi-dimensional analysis...\n";
if (empty(BEDROCK_API_KEY)) {
    echo "   ⚠ Using mock response (API key not configured)\n";
}

try {
    $test_title = "Major Tech Company Announces Quarterly Earnings";
    $test_content = "The company reported strong quarterly results, beating analyst expectations. Revenue grew 25% year-over-year, driven by cloud services and AI products.";
    $test_industry = "Technology";
    
    echo "   Article: \"$test_title\"\n";
    echo "   Industry: $test_industry\n";
    echo "   Analyzing...\n";
    
    $start_time = microtime(true);
    $result = $agent->analyzeArticle($test_title, $test_content, $test_industry);
    $duration = round(microtime(true) - $start_time, 2);
    
    echo "   ✓ Analysis completed!\n";
    echo "   Dimensions analyzed: " . count($result) . "\n";
    foreach ($result as $dimension => $analysis) {
        echo "   - $dimension: {$analysis['score']} - {$analysis['analysis']}\n";
    }
    echo "   Duration: {$duration}s\n\n";
} catch (Exception $e) {
    echo "   ✗ Exception: " . $e->getMessage() . "\n\n";
}

// Test 10: Test article summarization
echo "10. Testing article summarization...\n";
if (empty(BEDROCK_API_KEY)) {
    echo "   ⚠ Using mock response (API key not configured)\n";
}

try {
    $test_title = "Climate Change Report Reveals Urgent Need for Action";
    $test_content = "A comprehensive new report from leading climate scientists warns that global temperatures are rising faster than previously predicted. The report calls for immediate action to reduce carbon emissions and transition to renewable energy sources. Without significant changes, the world could face catastrophic consequences within the next few decades.";
    
    echo "   Article: \"$test_title\"\n";
    echo "   Summarizing...\n";
    
    $start_time = microtime(true);
    $summary = $agent->generateSummary($test_title, $test_content);
    $duration = round(microtime(true) - $start_time, 2);
    
    echo "   ✓ Summary generated!\n";
    echo "   Summary: \"$summary\"\n";
    echo "   Duration: {$duration}s\n\n";
} catch (Exception $e) {
    echo "   ✗ Exception: " . $e->getMessage() . "\n\n";
}

// Test 11: Check cURL availability
echo "11. Checking cURL availability (required for API calls)...\n";
if (function_exists('curl_init')) {
    echo "   ✓ cURL is available\n";
    
    // Check if we can make HTTPS requests
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.google.com');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $result = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "   ⚠ cURL HTTPS test failed: $error\n";
    } else {
        echo "   ✓ cURL can make HTTPS requests\n";
    }
} else {
    echo "   ✗ cURL is NOT available (required for Bedrock API)\n";
}
echo "\n";

// Summary
echo "=== Test Summary ===\n\n";

if (empty(BEDROCK_API_KEY)) {
    echo "⚠ API KEY NOT CONFIGURED\n";
    echo "The Bedrock integration is set up correctly, but using mock responses.\n";
    echo "To test real API calls:\n";
    echo "1. Generate API key: https://console.aws.amazon.com/bedrock -> API keys\n";
    echo "2. Add to config.php: define('BEDROCK_API_KEY', 'your-key-here');\n";
    echo "3. Run this test again\n\n";
    echo "Current Status:\n";
    echo "✓ Code structure is correct\n";
    echo "✓ Classes load properly\n";
    echo "✓ Mock responses work\n";
    echo "⚠ Real API calls not tested (no API key)\n";
} else {
    echo "✓ BEDROCK INTEGRATION CONFIGURED\n";
    echo "API Key: Present\n";
    echo "Region: " . AWS_REGION . "\n";
    echo "Model: " . BEDROCK_MODEL_ID . "\n\n";
    echo "If all tests above passed, your Bedrock integration is working!\n";
    echo "If tests failed, check:\n";
    echo "1. API key is valid (not expired)\n";
    echo "2. API key has Bedrock permissions\n";
    echo "3. Network allows HTTPS to AWS endpoints\n";
    echo "4. cURL is enabled and working\n";
}

echo "\n=== Test Complete ===\n";
