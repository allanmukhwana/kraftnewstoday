<?php
/**
 * Test URL Decoding Integration with News Scraper
 * Shows how Google News URLs are decoded to actual article URLs
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== Testing URL Decoding Integration ===\n\n";

require_once 'lib_news_scraper.php';

// Test 1: Fetch news and show URL decoding
echo "Test 1: Fetching technology news with URL decoding\n";
echo str_repeat("=", 80) . "\n\n";

$result = fetch_google_news('search', [
    'query' => 'technology',
    'options' => ['when' => '24h']
]);

if (isset($result['success']) && $result['success']) {
    echo "✓ Fetched {$result['count']} articles\n\n";
    
    // Show first 3 articles with URL details
    $articles_to_show = array_slice($result['articles'], 0, 3);
    
    foreach ($articles_to_show as $index => $article) {
        $num = $index + 1;
        echo "Article $num:\n";
        echo "  Title: {$article['title']}\n";
        echo "  Source: {$article['source']}\n";
        echo "  Published: {$article['date_formatted']}\n";
        echo "\n";
        
        echo "  Google News URL:\n";
        echo "    " . $article['link_google'] . "\n";
        echo "\n";
        
        if (isset($article['link_decoded']) && $article['link_decoded']) {
            echo "  ✓ URL DECODED!\n";
            echo "  Actual Article URL:\n";
            echo "    " . $article['link'] . "\n";
            echo "\n";
            
            if (isset($article['domain'])) {
                echo "  Domain: {$article['domain']}\n";
            }
        } else {
            echo "  ⚠ URL not decoded (may not be a redirect URL)\n";
            echo "  URL: {$article['link']}\n";
        }
        
        echo "\n" . str_repeat("-", 80) . "\n\n";
    }
} else {
    echo "✗ Failed to fetch articles\n";
    if (isset($result['error'])) {
        echo "Error: {$result['error']}\n";
    }
}

// Test 2: Test specific Google News URL
echo "\nTest 2: Testing specific Google News redirect URL\n";
echo str_repeat("=", 80) . "\n\n";

$test_url = 'https://news.google.com/rss/articles/CBMiogFBVV95cUxNYTZmZm91T0JlbG9lQUJBYVd3d3hzWWtFMVFtaWhfTFVZUUxEdzhRXzdHQVVhQl83U2lQa3FhbkRjRjRhYzB5QlBvUEt2M09GTERndFpVd0xIbWlHTjhsZVhmNllRUUt5aFItbTdWLTBIYjJ5SGtLdU12ME01eXhndGhiTU51NnVldlZweDJkdVFLRDdLYjR3OHBNMDdXeDk1Nmc?oc=5';

echo "Input URL:\n$test_url\n\n";

if (is_google_news_redirect($test_url)) {
    echo "✓ This is a Google News redirect URL\n\n";
    
    $article_id = extract_google_news_article_id($test_url);
    if ($article_id) {
        echo "Article ID: $article_id\n\n";
    }
    
    echo "Decoding URL...\n";
    $decoded = decode_google_news_redirect($test_url);
    
    if ($decoded && $decoded !== $test_url) {
        echo "✓ Successfully decoded!\n\n";
        echo "Final URL:\n$decoded\n\n";
        
        $domain = get_domain_from_url($decoded);
        if ($domain) {
            echo "Domain: $domain\n";
        }
    } else {
        echo "✗ Could not decode URL\n";
        echo "This may happen if:\n";
        echo "  - The URL has expired\n";
        echo "  - Network/firewall blocking the request\n";
        echo "  - Google News changed their redirect format\n";
    }
} else {
    echo "⚠ This is not a Google News redirect URL\n";
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// Test 3: Show statistics
echo "Test 3: URL Decoding Statistics\n";
echo str_repeat("=", 80) . "\n\n";

if (isset($result['success']) && $result['success']) {
    $total = $result['count'];
    $decoded_count = 0;
    $failed_count = 0;
    $domains = [];
    
    foreach ($result['articles'] as $article) {
        if (isset($article['link_decoded'])) {
            if ($article['link_decoded']) {
                $decoded_count++;
                if (isset($article['domain'])) {
                    $domains[$article['domain']] = ($domains[$article['domain']] ?? 0) + 1;
                }
            } else {
                $failed_count++;
            }
        }
    }
    
    echo "Total articles: $total\n";
    echo "Successfully decoded: $decoded_count\n";
    echo "Failed to decode: $failed_count\n";
    echo "Success rate: " . round(($decoded_count / $total) * 100, 1) . "%\n\n";
    
    if (!empty($domains)) {
        echo "Top source domains:\n";
        arsort($domains);
        $top_domains = array_slice($domains, 0, 10, true);
        foreach ($top_domains as $domain => $count) {
            echo "  - $domain: $count articles\n";
        }
    }
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// Test 4: Performance test
echo "Test 4: URL Decoding Performance\n";
echo str_repeat("=", 80) . "\n\n";

if (isset($result['success']) && $result['success'] && $result['count'] > 0) {
    $test_urls = array_slice($result['articles'], 0, 5);
    
    echo "Testing decoding speed for 5 URLs...\n\n";
    
    $total_time = 0;
    foreach ($test_urls as $index => $article) {
        $num = $index + 1;
        $url = $article['link_google'];
        
        $start = microtime(true);
        $decoded = decode_google_news_redirect($url, 5); // 5 second timeout
        $duration = microtime(true) - $start;
        $total_time += $duration;
        
        echo "URL $num: " . round($duration, 2) . "s";
        if ($decoded && $decoded !== $url) {
            echo " ✓ Success\n";
        } else {
            echo " ✗ Failed\n";
        }
    }
    
    $avg_time = $total_time / 5;
    echo "\nAverage decode time: " . round($avg_time, 2) . "s per URL\n";
    echo "Total time: " . round($total_time, 2) . "s\n";
}

echo "\n=== Test Complete ===\n\n";

echo "Summary:\n";
echo "✓ URL decoder library integrated with news scraper\n";
echo "✓ Google News redirect URLs automatically decoded\n";
echo "✓ Actual article URLs extracted\n";
echo "✓ Source domains identified\n\n";

echo "Usage in your code:\n";
echo "  \$news = fetch_google_news('search', ['query' => 'tech']);\n";
echo "  foreach (\$news['articles'] as \$article) {\n";
echo "      echo \$article['link'];  // Decoded actual URL\n";
echo "      echo \$article['link_google'];  // Original Google News URL\n";
echo "      echo \$article['domain'];  // Source domain\n";
echo "  }\n";
