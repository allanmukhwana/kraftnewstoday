<?php
/**
 * Metadata Fetcher Diagnostic Test
 * Shows detailed information about fetch attempts
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== Metadata Fetcher Diagnostic Test ===<br/><br/>";

// Test URL
$test_url = isset($argv[1]) ? $argv[1] : 'https://cybernews.com/ai-news/tech-professionals-adapting-local-ai-models-for-diverse-applications/';

echo "Testing URL: $test_url<br/>";
echo str_repeat("=", 80) . "<br/><br/>";

// Test 1: Basic cURL test
echo "Test 1: Basic cURL Connection<br/>";
echo str_repeat("-", 80) . "<br/>";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $test_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
    CURLOPT_VERBOSE => false
]);

$start = microtime(true);
$response = curl_exec($ch);
$duration = round(microtime(true) - $start, 2);

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$effective_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
$error = curl_error($ch);
$error_no = curl_errno($ch);

curl_close($ch);

echo "Duration: {$duration}s<br/>";
echo "HTTP Code: $http_code<br/>";
echo "Content Type: $content_type<br/>";
echo "Effective URL: $effective_url<br/>";

if ($error) {
    echo "✗ cURL Error ($error_no): $error<br/>";
} else {
    echo "✓ No cURL errors<br/>";
}

if ($response) {
    $response_length = strlen($response);
    echo "✓ Response received: " . number_format($response_length) . " bytes<br/>";
    
    // Check if it's HTML
    if (stripos($content_type, 'html') !== false || stripos($response, '<html') !== false) {
        echo "✓ Response appears to be HTML<br/>";
    } else {
        echo "⚠ Response may not be HTML<br/>";
    }
} else {
    echo "✗ No response received<br/>";
}

echo "<br/>";

// Test 2: Check for common blocking patterns
if ($response) {
    echo "Test 2: Analyzing Response Content<br/>";
    echo str_repeat("-", 80) . "<br/>";
    
    // Check for Cloudflare
    if (stripos($response, 'cloudflare') !== false) {
        echo "⚠ Cloudflare detected (may be blocking)<br/>";
    }
    
    // Check for captcha
    if (stripos($response, 'captcha') !== false || stripos($response, 'recaptcha') !== false) {
        echo "⚠ CAPTCHA detected (blocking automated access)<br/>";
    }
    
    // Check for access denied
    if (stripos($response, 'access denied') !== false || stripos($response, '403') !== false) {
        echo "⚠ Access denied message detected<br/>";
    }
    
    // Check for bot detection
    if (stripos($response, 'bot') !== false && stripos($response, 'detected') !== false) {
        echo "⚠ Bot detection message found<br/>";
    }
    
    // Check for meta tags
    $has_og_tags = (stripos($response, 'og:title') !== false || stripos($response, 'og:image') !== false);
    $has_meta_desc = (stripos($response, 'name="description"') !== false || stripos($response, 'name=\'description\'') !== false);
    
    echo "<br/>Metadata presence:<br/>";
    echo "  Open Graph tags: " . ($has_og_tags ? "✓ Found" : "✗ Not found") . "<br/>";
    echo "  Meta description: " . ($has_meta_desc ? "✓ Found" : "✗ Not found") . "<br/>";
    
    echo "<br/>";
}

// Test 3: Try with metadata fetcher
if ($response) {
    echo "Test 3: Using Metadata Fetcher Library<br/>";
    echo str_repeat("-", 80) . "<br/>";
    
    require_once 'lib_metadata_fetcher.php';
    
    $start = microtime(true);
    $metadata = fetch_url_metadata($test_url, 15, 1); // 15s timeout, 1 retry
    $duration = round(microtime(true) - $start, 2);
    
    echo "Duration: {$duration}s<br/>";
    
    if ($metadata['success']) {
        echo "✓ Metadata fetched successfully<br/><br/>";
        
        $fields_found = 0;
        $fields = ['meta_description', 'og_title', 'og_description', 'og_image', 'og_type', 
                   'og_site_name', 'twitter_card', 'featured_image', 'canonical_url'];
        
        foreach ($fields as $field) {
            if (!empty($metadata[$field])) {
                $fields_found++;
                $value = $metadata[$field];
                if (strlen($value) > 80) {
                    $value = substr($value, 0, 80) . '...';
                }
                echo "  ✓ $field: $value<br/>";
            }
        }
        
        echo "<br/>Fields found: $fields_found / " . count($fields) . "<br/>";
    } else {
        echo "✗ Failed to fetch metadata<br/>";
        echo "Error: {$metadata['error']}<br/>";
    }
}

echo "<br/>" . str_repeat("=", 80) . "<br/><br/>";

// Summary and recommendations
echo "=== Summary & Recommendations ===<br/><br/>";

if (!$response) {
    echo "❌ <strong>Cannot fetch URL</strong><br/><br/>";
    echo "Possible causes:<br/>";
    echo "1. Website is down or unreachable<br/>";
    echo "2. Firewall blocking outbound requests<br/>";
    echo "3. DNS resolution issues<br/>";
    echo "4. SSL/TLS certificate problems<br/><br/>";
    
    echo "Recommendations:<br/>";
    echo "• Check if the URL is accessible in a browser<br/>";
    echo "• Verify server can make outbound HTTPS connections<br/>";
    echo "• Check firewall rules<br/>";
} elseif ($http_code >= 400) {
    echo "❌ <strong>HTTP Error: $http_code</strong><br/><br/>";
    
    if ($http_code == 403) {
        echo "The website is blocking automated access (403 Forbidden).<br/><br/>";
        echo "This is common for sites with:<br/>";
        echo "• Cloudflare protection<br/>";
        echo "• Bot detection systems<br/>";
        echo "• IP-based rate limiting<br/><br/>";
        
        echo "Solutions:<br/>";
        echo "• Some sites will always block scrapers (this is normal)<br/>";
        echo "• The metadata fetcher will skip these and continue with others<br/>";
        echo "• Focus on sites that allow automated access<br/>";
    } elseif ($http_code == 404) {
        echo "The URL was not found (404).<br/>";
    } elseif ($http_code >= 500) {
        echo "The website is experiencing server errors ($http_code).<br/>";
    }
} else {
    echo "✅ <strong>URL is accessible</strong><br/><br/>";
    
    if (stripos($response, 'cloudflare') !== false || stripos($response, 'captcha') !== false) {
        echo "⚠ <strong>Bot protection detected</strong><br/><br/>";
        echo "The site uses protection systems that may block automated access.<br/>";
        echo "This is normal - not all sites allow scraping.<br/><br/>";
        echo "The system will:<br/>";
        echo "• Skip sites that block access<br/>";
        echo "• Successfully fetch from sites that allow it<br/>";
        echo "• Continue processing other articles<br/>";
    } else {
        echo "✅ <strong>Metadata fetching should work</strong><br/><br/>";
        echo "The URL is accessible and appears to have metadata.<br/>";
    }
}

echo "<br/>=== Test Complete ===<br/>";
