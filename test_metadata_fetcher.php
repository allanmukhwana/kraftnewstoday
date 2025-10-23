<?php
/**
 * Test Metadata Fetcher
 * Tests fetching Open Graph, meta tags, and featured images
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'lib_metadata_fetcher.php';

echo "=== Metadata Fetcher Test ===<br/><br/>";

// Test URLs
$test_urls = [
    'https://cybernews.com/ai-news/tech-professionals-adapting-local-ai-models-for-diverse-applications/',
    'https://accesswdun.com/ap_article/smus-trip-to-wake-forest-no-7-georgia-techs-league-leading-march-headline-acc-slate',
    'https://www.news10.com/video/hvcc-breaking-ground-on-new-applied-technology-center/11194935/'
];

// Allow custom URL from command line
if (isset($argv[1])) {
    $test_urls = [$argv[1]];
}

foreach ($test_urls as $index => $url) {
    $num = $index + 1;
    echo "Test $num: $url<br/>";
    echo str_repeat("=", 80) . "<br/><br/>";
    
    $start = microtime(true);
    $metadata = fetch_url_metadata($url, 15);
    $duration = round(microtime(true) - $start, 2);
    
    if ($metadata['success']) {
        echo "✓ Successfully fetched metadata in {$duration}s<br/><br/>";
        
        // Display metadata
        echo "=== Basic Metadata ===<br/>";
        if ($metadata['title']) {
            echo "Title: {$metadata['title']}<br/>";
        }
        if ($metadata['meta_description']) {
            echo "Meta Description: {$metadata['meta_description']}<br/>";
        }
        if ($metadata['author']) {
            echo "Author: {$metadata['author']}<br/>";
        }
        if ($metadata['published_date']) {
            echo "Published: {$metadata['published_date']}<br/>";
        }
        if ($metadata['canonical_url']) {
            echo "Canonical URL: {$metadata['canonical_url']}<br/>";
        }
        
        echo "<br/>=== Open Graph Metadata ===<br/>";
        if ($metadata['og_title']) {
            echo "OG Title: {$metadata['og_title']}<br/>";
        }
        if ($metadata['og_description']) {
            echo "OG Description: " . substr($metadata['og_description'], 0, 100) . "...<br/>";
        }
        if ($metadata['og_image']) {
            echo "OG Image: {$metadata['og_image']}<br/>";
        }
        if ($metadata['og_type']) {
            echo "OG Type: {$metadata['og_type']}<br/>";
        }
        if ($metadata['og_site_name']) {
            echo "OG Site Name: {$metadata['og_site_name']}<br/>";
        }
        
        echo "<br/>=== Twitter Card Metadata ===<br/>";
        if ($metadata['twitter_card']) {
            echo "Twitter Card: {$metadata['twitter_card']}<br/>";
        }
        if ($metadata['twitter_title']) {
            echo "Twitter Title: {$metadata['twitter_title']}<br/>";
        }
        if ($metadata['twitter_description']) {
            echo "Twitter Description: " . substr($metadata['twitter_description'], 0, 100) . "...<br/>";
        }
        if ($metadata['twitter_image']) {
            echo "Twitter Image: {$metadata['twitter_image']}<br/>";
        }
        
        echo "<br/>=== Featured Image ===<br/>";
        if ($metadata['featured_image']) {
            echo "Featured Image: {$metadata['featured_image']}<br/>";
        } else {
            echo "No featured image found<br/>";
        }
        
        // Summary
        echo "<br/>=== Summary ===<br/>";
        $fields_found = 0;
        $total_fields = 15;
        
        foreach (['title', 'meta_description', 'og_title', 'og_description', 'og_image', 
                  'og_type', 'og_site_name', 'twitter_card', 'twitter_title', 
                  'twitter_description', 'twitter_image', 'canonical_url', 
                  'featured_image', 'author', 'published_date'] as $field) {
            if (!empty($metadata[$field])) {
                $fields_found++;
            }
        }
        
        echo "Fields found: $fields_found / $total_fields<br/>";
        echo "Completeness: " . round(($fields_found / $total_fields) * 100, 1) . "%<br/>";
        
    } else {
        echo "✗ Failed to fetch metadata<br/>";
        echo "Error: {$metadata['error']}<br/>";
    }
    
    echo "<br/>" . str_repeat("=", 80) . "<br/><br/>";
}

echo "=== Usage ===<br/><br/>";
echo "Test specific URL:<br/>";
echo "  php test_metadata_fetcher.php \"https://example.com/article\"<br/><br/>";

echo "In code:<br/>";
echo "  \$metadata = fetch_url_metadata(\$url);<br/>";
echo "  echo \$metadata['og_image'];<br/>";
echo "  echo \$metadata['featured_image'];<br/>";
echo "  echo \$metadata['meta_description'];<br/><br/>";

echo "=== Test Complete ===<br/>";
