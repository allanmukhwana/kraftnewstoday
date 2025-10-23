<?php
/**
 * Test Google News RSS Scraper
 * Run this to verify the news scraper is working
 */

require_once 'lib_news_scraper.php';

echo "=== Testing Google News RSS Scraper ===\n\n";

// Test 1: Get top headlines
echo "Test 1: Top Headlines (US)\n";
echo "----------------------------\n";
$headlines = fetch_google_news('headlines', ['country' => 'US', 'language' => 'en-US']);
if (isset($headlines['success']) && $headlines['success']) {
    echo "✓ Success! Found {$headlines['count']} articles\n";
    if ($headlines['count'] > 0) {
        echo "First article: {$headlines['articles'][0]['title']}\n";
    }
} else {
    echo "✗ Failed: " . ($headlines['error'] ?? 'Unknown error') . "\n";
}
echo "\n";

// Test 2: Get technology news
echo "Test 2: Technology Topic\n";
echo "----------------------------\n";
$tech_news = fetch_google_news('topic', ['topic' => 'TECHNOLOGY']);
if (isset($tech_news['success']) && $tech_news['success']) {
    echo "✓ Success! Found {$tech_news['count']} articles\n";
    if ($tech_news['count'] > 0) {
        echo "First article: {$tech_news['articles'][0]['title']}\n";
    }
} else {
    echo "✗ Failed: " . ($tech_news['error'] ?? 'Unknown error') . "\n";
}
echo "\n";

// Test 3: Search for specific keyword
echo "Test 3: Search 'artificial intelligence' (last 24h)\n";
echo "----------------------------\n";
$search_results = fetch_google_news('search', [
    'query' => 'artificial intelligence',
    'options' => ['when' => '24h']
]);
if (isset($search_results['success']) && $search_results['success']) {
    echo "✓ Success! Found {$search_results['count']} articles\n";
    if ($search_results['count'] > 0) {
        echo "First article: {$search_results['articles'][0]['title']}\n";
    }
} else {
    echo "✗ Failed: " . ($search_results['error'] ?? 'Unknown error') . "\n";
}
echo "\n";

// Test 4: Search by industry
echo "Test 4: Healthcare Industry News\n";
echo "----------------------------\n";
$industry_news = fetch_google_news('industry', [
    'industry' => 'healthcare',
    'keywords' => ['medical', 'hospital'],
    'timeRange' => '24h'
]);
if (isset($industry_news['success']) && $industry_news['success']) {
    echo "✓ Success! Found {$industry_news['count']} articles\n";
    if ($industry_news['count'] > 0) {
        echo "First article: {$industry_news['articles'][0]['title']}\n";
        echo "Published: {$industry_news['articles'][0]['date_formatted']}\n";
        echo "Source: {$industry_news['articles'][0]['source']}\n";
    }
} else {
    echo "✗ Failed: " . ($industry_news['error'] ?? 'Unknown error') . "\n";
}
echo "\n";

echo "=== All Tests Complete ===\n";
