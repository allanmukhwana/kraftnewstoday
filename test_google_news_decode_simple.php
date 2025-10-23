<?php
/**
 * Simple Google News URL Decoder Test
 * Tests the new decoder implementation
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== Google News URL Decoder Test ===<br/><br/>";

require_once 'lib_url_decoder.php';

// Test URL from user
$test_url = 'https://news.google.com/rss/articles/CBMiogFBVV95cUxNYTZmZm91T0JlbG9lQUJBYVd3d3hzWWtFMVFtaWhfTFVZUUxEdzhRXzdHQVVhQl83U2lQa3FhbkRjRjRhYzB5QlBvUEt2M09GTERndFpVd0xIbWlHTjhsZVhmNllRUUt5aFItbTdWLTBIYjJ5SGtLdU12ME01eXhndGhiTU51NnVldlZweDJkdVFLRDdLYjR3OHBNMDdXeDk1Nmc?oc=5';

echo "Input URL:<br/>";
echo "$test_url<br/><br/>";

echo "Step 1: Extracting base64 string...<br/>";
$base64_result = get_base64_str($test_url);

if ($base64_result['status']) {
    echo "✓ Success!<br/>";
    echo "Base64 String: {$base64_result['base64_str']}<br/><br/>";
    
    echo "Step 2: Getting decoding parameters...<br/>";
    $params_result = get_decoding_params($base64_result['base64_str']);
    
    if ($params_result['status']) {
        echo "✓ Success!<br/>";
        echo "Signature: {$params_result['signature']}<br/>";
        echo "Timestamp: {$params_result['timestamp']}<br/><br/>";
        
        echo "Step 3: Decoding URL...<br/>";
        $decode_result = decode_url_with_params(
            $params_result['signature'],
            $params_result['timestamp'],
            $params_result['base64_str']
        );
        
        if ($decode_result['status']) {
            echo "✓ Success!<br/><br/>";
            echo "=== RESULT ===<br/><br/>";
            echo "Original URL:<br/>$test_url<br/><br/>";
            echo "Decoded URL:<br/>{$decode_result['decoded_url']}<br/><br/>";
            
            // Extract domain
            $domain = get_domain_from_url($decode_result['decoded_url']);
            if ($domain) {
                echo "Source Domain: $domain<br/>";
            }
        } else {
            echo "✗ Failed!<br/>";
            echo "Error: {$decode_result['message']}<br/>";
        }
    } else {
        echo "✗ Failed!<br/>";
        echo "Error: {$params_result['message']}<br/>";
    }
} else {
    echo "✗ Failed!<br/>";
    echo "Error: {$base64_result['message']}<br/>";
}

echo "<br/>" . str_repeat("=", 80) . "<br/><br/>";

// Test with the simple function
echo "Testing with decode_google_news_redirect() function:<br/><br/>";

$start = microtime(true);
$decoded = decode_google_news_redirect($test_url);
$duration = round(microtime(true) - $start, 2);

if ($decoded) {
    echo "✓ Successfully decoded in {$duration}s<br/><br/>";
    echo "Decoded URL:<br/>$decoded<br/>";
} else {
    echo "✗ Failed to decode<br/>";
    echo "Check error logs for details<br/>";
}

echo "<br/>=== Test Complete ===<br/>";
