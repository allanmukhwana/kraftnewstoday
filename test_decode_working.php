<?php
/**
 * Working Google News URL Decoder Test
 * Based on: https://github.com/SSujitX/google-news-url-decoder
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'lib_url_decoder.php';

echo "=== Google News URL Decoder (Working Implementation) ===\n\n";

// Test URL
$test_url = 'https://news.google.com/rss/articles/CBMiogFBVV95cUxNYTZmZm91T0JlbG9lQUJBYVd3d3hzWWtFMVFtaWhfTFVZUUxEdzhRXzdHQVVhQl83U2lQa3FhbkRjRjRhYzB5QlBvUEt2M09GTERndFpVd0xIbWlHTjhsZVhmNllRUUt5aFItbTdWLTBIYjJ5SGtLdU12ME01eXhndGhiTU51NnVldlZweDJkdVFLRDdLYjR3OHBNMDdXeDk1Nmc?oc=5';

echo "Original Google News URL:\n";
echo "$test_url\n\n";
echo str_repeat("-", 80) . "\n\n";

echo "Decoding...\n\n";

$start_time = microtime(true);
$decoded_url = decode_google_news_redirect($test_url, 15); // 15 second timeout
$duration = round(microtime(true) - $start_time, 2);

if ($decoded_url && $decoded_url !== $test_url) {
    echo "✓ SUCCESS!\n\n";
    echo "Decoded URL:\n";
    echo "$decoded_url\n\n";
    echo "Duration: {$duration}s\n\n";
    
    // Extract domain
    $domain = get_domain_from_url($decoded_url);
    if ($domain) {
        echo "Source Domain: $domain\n\n";
    }
    
    echo str_repeat("=", 80) . "\n\n";
    echo "RESULT: The decoder is working correctly!\n";
    echo "The Google News redirect URL was successfully decoded to the actual article URL.\n";
    
} else {
    echo "✗ FAILED\n\n";
    echo "The URL could not be decoded.\n\n";
    echo "Possible reasons:\n";
    echo "1. Network/firewall blocking the requests\n";
    echo "2. Google News changed their format\n";
    echo "3. The URL is invalid or expired\n";
    echo "4. Timeout (try increasing timeout value)\n\n";
    
    if ($decoded_url === $test_url) {
        echo "Note: The function returned the same URL, which means decoding failed.\n";
    }
}

echo "\n=== Test Complete ===\n";
