<?php
/**
 * Test Simple Metadata Fetcher
 * Tests the simplified metadata fetching with smart fallbacks
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'lib_simple_metadata.php';

echo "=== Simple Metadata Fetcher Test ===<br/><br/>";

// Test URLs - mix of working and blocked sites
$test_urls = [
    'https://accesswdun.com/ap_article/smus-trip-to-wake-forest-no-7-georgia-techs-league-leading-march-headline-acc-slate',
    'https://cybernews.com/ai-news/tech-professionals-adapting-local-ai-models-for-diverse-applications/',
    'https://www.news10.com/video/hvcc-breaking-ground-on-new-applied-technology-center/11194935/',
    'https://techcrunch.com/',
    'https://dev.to/'
];

// Allow custom URL
if (isset($argv[1])) {
    $test_urls = [$argv[1]];
}

$total_tests = count($test_urls);
$success_count = 0;
$with_og_image = 0;
$with_placeholder = 0;

foreach ($test_urls as $index => $url) {
    $num = $index + 1;
    echo "<strong>Test $num/$total_tests:</strong> $url<br/>";
    echo str_repeat("=", 80) . "<br/><br/>";
    
    $start = microtime(true);
    $metadata = fetch_simple_metadata($url, 10);
    $duration = round(microtime(true) - $start, 2);
    
    echo "Duration: {$duration}s<br/><br/>";
    
    // Display results
    echo "<strong>✓ Image:</strong> ";
    if ($metadata['has_og_image']) {
        echo "OG Image Found<br/>";
        $with_og_image++;
    } else {
        echo "Using Placeholder<br/>";
        $with_placeholder++;
    }
    echo "  URL: <a href='{$metadata['image']}' target='_blank'>{$metadata['image']}</a><br/>";
    echo "  <img src='{$metadata['image']}' style='max-width:300px; height:auto; border:1px solid #ccc; margin:5px 0;'><br/><br/>";
    
    echo "<strong>✓ Title:</strong> ";
    if ($metadata['has_og_title']) {
        echo "OG Title Found<br/>";
    } else {
        echo "Using Fallback<br/>";
    }
    echo "  {$metadata['title']}<br/><br/>";
    
    echo "<strong>✓ Description:</strong> ";
    if ($metadata['has_og_description']) {
        echo "OG Description Found<br/>";
    } else {
        echo "Using Fallback<br/>";
    }
    $desc_preview = strlen($metadata['description']) > 150 
        ? substr($metadata['description'], 0, 150) . '...' 
        : $metadata['description'];
    echo "  {$desc_preview}<br/><br/>";
    
    if ($metadata['success']) {
        $success_count++;
        echo "<span style='color:green;'>✓ Metadata fetched successfully</span><br/>";
    } else {
        echo "<span style='color:orange;'>⚠ Using fallback data (site blocked)</span><br/>";
        echo "  Error: {$metadata['error']}<br/>";
    }
    
    echo "<br/>" . str_repeat("=", 80) . "<br/><br/>";
}

// Summary
echo "<div style='background:#f0f0f0; padding:15px; border-radius:5px;'><br/>";
echo "<strong>=== Summary ===</strong><br/><br/>";
echo "Total URLs tested: $total_tests<br/>";
echo "Successfully fetched: $success_count (" . round(($success_count/$total_tests)*100, 1) . "%)<br/>";
echo "With OG images: $with_og_image<br/>";
echo "With placeholders: $with_placeholder<br/><br/>";

echo "<strong>Key Points:</strong><br/>";
echo "✓ <strong>Every article gets an image</strong> (real or placeholder)<br/>";
echo "✓ <strong>Every article gets a title</strong> (OG or fallback)<br/>";
echo "✓ <strong>Every article gets a description</strong> (OG or fallback)<br/>";
echo "✓ <strong>No failures</strong> - system always returns data<br/>";
echo "</div><br/><br/>";

// Usage example
echo "=== Usage Example ===<br/><br/>";
echo "<pre style='background:#f5f5f5; padding:10px; border-radius:5px;'>";
echo htmlspecialchars('<?php
require_once \'lib_simple_metadata.php\';

$metadata = fetch_simple_metadata($url);

// Always has data - no need to check for failures
echo $metadata[\'image\'];        // Always has image (real or placeholder)
echo $metadata[\'title\'];        // Always has title
echo $metadata[\'description\']; // Always has description

// Optional: Check if real OG data was found
if ($metadata[\'has_og_image\']) {
    echo "Real image found!";
} else {
    echo "Using placeholder";
}
?>');
echo "</pre><br/>";

echo "=== Test Complete ===<br/>";
