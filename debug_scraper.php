<?php
/**
 * Debug News Scraper - Find out why it's not working
 */

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== Debugging News Scraper ===\n\n";

// Test 1: Check if lib_news_scraper.php loads
echo "1. Loading lib_news_scraper.php...\n";
try {
    require_once 'lib_news_scraper.php';
    echo "   ✓ Loaded successfully\n\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Create GoogleNewsRSS instance
echo "2. Creating GoogleNewsRSS instance...\n";
try {
    $scraper = new GoogleNewsRSS();
    echo "   ✓ Instance created\n\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 3: Test simple search
echo "3. Testing simple search (technology)...\n";
try {
    $result = $scraper->searchNews('technology', [
        'when' => '24h',
        'country' => 'US',
        'language' => 'en-US'
    ]);
    
    echo "   Result type: " . gettype($result) . "\n";
    echo "   Result keys: " . implode(', ', array_keys($result)) . "\n";
    
    if (isset($result['error'])) {
        echo "   ✗ Error: " . $result['error'] . "\n";
        if (isset($result['http_code'])) {
            echo "   HTTP Code: " . $result['http_code'] . "\n";
        }
        if (isset($result['details'])) {
            echo "   Details: " . print_r($result['details'], true) . "\n";
        }
    } elseif (isset($result['success']) && $result['success']) {
        echo "   ✓ Success! Found {$result['count']} articles\n";
        if ($result['count'] > 0) {
            echo "   First article title: {$result['articles'][0]['title']}\n";
            echo "   First article link: {$result['articles'][0]['link']}\n";
        }
    } else {
        echo "   ✗ Unexpected result format\n";
        echo "   Result: " . print_r($result, true) . "\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Exception: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n\n";
}

// Test 4: Test with OR keywords (like agent_scraper uses)
echo "4. Testing with OR keywords (technology OR tech OR software)...\n";
try {
    $result = $scraper->searchNews('technology OR tech OR software', [
        'when' => '24h',
        'country' => 'US',
        'language' => 'en-US'
    ]);
    
    if (isset($result['error'])) {
        echo "   ✗ Error: " . $result['error'] . "\n";
    } elseif (isset($result['success']) && $result['success']) {
        echo "   ✓ Success! Found {$result['count']} articles\n";
    } else {
        echo "   ✗ Unexpected result\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Exception: " . $e->getMessage() . "\n\n";
}

// Test 5: Check cURL availability
echo "5. Checking cURL availability...\n";
if (function_exists('curl_init')) {
    echo "   ✓ cURL is available\n";
    
    // Test a simple cURL request
    echo "   Testing cURL connection to Google News...\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://news.google.com/rss?hl=en-US&gl=US&ceid=US:en');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        echo "   ✗ cURL Error: $curl_error\n";
    } else {
        echo "   HTTP Code: $http_code\n";
        if ($http_code == 200) {
            echo "   ✓ Successfully connected to Google News\n";
            echo "   Response length: " . strlen($response) . " bytes\n";
            
            // Try to parse XML
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($response);
            if ($xml === false) {
                echo "   ✗ Failed to parse XML\n";
                $errors = libxml_get_errors();
                foreach ($errors as $error) {
                    echo "      XML Error: " . $error->message . "\n";
                }
                libxml_clear_errors();
            } else {
                echo "   ✓ XML parsed successfully\n";
                $item_count = count($xml->channel->item);
                echo "   Found $item_count items in feed\n";
            }
        } else {
            echo "   ✗ HTTP request failed with code: $http_code\n";
        }
    }
} else {
    echo "   ✗ cURL is NOT available\n";
}
echo "\n";

// Test 6: Check if agent_scraper loads
echo "6. Testing agent_scraper.php integration...\n";
try {
    require_once 'config.php';
    require_once 'agent_scraper.php';
    echo "   ✓ agent_scraper.php loaded\n";
    
    $news_scraper = new NewsScraper();
    echo "   ✓ NewsScraper instance created\n";
    
    // Test keyword generation
    $keywords = $news_scraper->getIndustryKeywords('technology');
    echo "   Keywords for 'technology': $keywords\n";
    
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Debug Complete ===\n";
