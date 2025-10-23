<?php
/**
 * Test Google News Redirect URL Decoder
 * Decodes Google News redirect URLs to get the actual article URL
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== Google News Redirect URL Decoder ===<br/><br/>";

/**
 * Decode Google News redirect URL to get actual article URL
 * Uses the new decoder implementation
 */
function decode_google_news_url($google_url) {
    echo "Input URL:\n$google_url\n\n";
    
    // Use the new decoder
    echo "Decoding using Google's batchexecute API...\n<br/>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $google_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    
    // Get redirect history
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    $response = curl_exec($ch);
    $final_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirect_count = curl_getinfo($ch, CURLINFO_REDIRECT_COUNT);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        echo "  ✗ cURL Error: $error<br/><br/>";
        return null;
    }
    
    echo "  HTTP Code: $http_code<br/>";
    echo "  Redirects: $redirect_count<br/>";
    echo "  Final URL: $final_url<br/><br/>";
    
    if ($http_code == 200 && $final_url !== $google_url) {
        echo "  ✓ Successfully decoded!<br/><br/>";
        return $final_url;
    }
    
    // Method 2: Try to extract from response headers
    echo "Method 2: Parsing redirect headers...<br/>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $google_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't follow, just get first redirect
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    // Extract Location header
    if (preg_match('/Location:\s*(.+)/i', $response, $matches)) {
        $redirect_url = trim($matches[1]);
        echo "  Found redirect: $redirect_url<br/><br/>";
        
        // If it's another Google URL, follow it
        if (strpos($redirect_url, 'google.com') !== false) {
            echo "  Still a Google URL, following again...<br/>";
            return decode_google_news_url($redirect_url);
        }
        
        return $redirect_url;
    }
    
    echo "  ✗ Could not find redirect<br/><br/>";
    return $final_url;
}

/**
 * Decode Google News URL with detailed analysis
 */
function analyze_google_news_url($google_url) {
    echo "=== Analyzing URL ===<br/><br/>";
    
    // Parse URL components
    $parsed = parse_url($google_url);
    
    echo "URL Components:<br/>";
    echo "  Scheme: " . ($parsed['scheme'] ?? 'N/A') . "<br/>";
    echo "  Host: " . ($parsed['host'] ?? 'N/A') . "<br/>";
    echo "  Path: " . ($parsed['path'] ?? 'N/A') . "<br/>";
    
    if (isset($parsed['query'])) {
        echo "  Query: " . $parsed['query'] . "<br/>";
        parse_str($parsed['query'], $query_params);
        echo "  Query Params:<br/>";
        foreach ($query_params as $key => $value) {
            echo "    - $key: $value<br/>";
        }
    }
    echo "<br/>";
    
    // Check if it's a Google News article URL
    if (strpos($google_url, 'news.google.com/rss/articles/') !== false) {
        echo "✓ This is a Google News article redirect URL<br/>";
        echo "  Article ID: " . basename($parsed['path']) . "<br/><br/>";
    } else {
        echo "⚠ This doesn't look like a Google News article URL<br/><br/>";
    }
    
    // Decode the URL
    $final_url = decode_google_news_url($google_url);
    
    if ($final_url && $final_url !== $google_url) {
        echo "=== Result ===<br/><br/>";
        echo "Original URL:<br/>$google_url<br/><br/>";
        echo "Final Destination:<br/>$final_url<br/><br/>";
        
        // Extract domain from final URL
        $final_parsed = parse_url($final_url);
        if (isset($final_parsed['host'])) {
            echo "Source Domain: " . $final_parsed['host'] . "<br/>";
        }
        
        return $final_url;
    } else {
        echo "=== Result ===<br/><br/>";
        echo "✗ Could not decode URL or URL doesn't redirect<br/>";
        return null;
    }
}

// Test with the provided URL
echo "TEST 1: Provided Google News URL<br/>";
echo str_repeat("=", 80) . "<br/><br/>";

$test_url = 'https://news.google.com/rss/articles/CBMiogFBVV95cUxNYTZmZm91T0JlbG9lQUJBYVd3d3hzWWtFMVFtaWhfTFVZUUxEdzhRXzdHQVVhQl83U2lQa3FhbkRjRjRhYzB5QlBvUEt2M09GTERndFpVd0xIbWlHTjhsZVhmNllRUUt5aFItbTdWLTBIYjJ5SGtLdU12ME01eXhndGhiTU51NnVldlZweDJkdVFLRDdLYjR3OHBNMDdXeDk1Nmc?oc=5';

$result = analyze_google_news_url($test_url);

echo "<br/>" . str_repeat("=", 80) . "<br/><br/>";

// Test with custom URL (if provided via command line)
if (isset($argv[1])) {
    echo "TEST 2: Custom URL from command line<br/>";
    echo str_repeat("=", 80) . "<br/><br/>";
    
    $custom_url = $argv[1];
    $result = analyze_google_news_url($custom_url);
    
    echo "<br/>" . str_repeat("=", 80) . "<br/><br/>";
}

// Interactive mode
if (php_sapi_name() === 'cli') {
    echo "<br/>=== Interactive Mode ===<br/><br/>";
    echo "Enter a Google News URL to decode (or press Enter to skip):<br/>";
    $input = trim(fgets(STDIN));
    
    if (!empty($input)) {
        echo "<br/>";
        $result = analyze_google_news_url($input);
    }
}

echo "<br/>=== Usage ===<br/><br/>";
echo "Command line:<br/>";
echo "  php test_google_news_redirect.php \"https://news.google.com/rss/articles/...\"<br/><br/>";
echo "In code:<br/>";
echo "  \$final_url = decode_google_news_url(\$google_url);<br/><br/>";

echo "=== Test Complete ===<br/>";
