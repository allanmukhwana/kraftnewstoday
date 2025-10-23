<?php
/**
 * URL Decoder Library
 * Handles decoding of Google News redirect URLs
 * Based on: https://github.com/SSujitX/google-news-url-decoder
 */

/**
 * Extract base64 string from Google News URL
 * 
 * @param string $source_url The Google News URL
 * @return array Result with status and base64_str or error message
 */
function get_base64_str($source_url) {
    try {
        $parsed = parse_url($source_url);
        
        if (!isset($parsed['host']) || $parsed['host'] !== 'news.google.com') {
            return ['status' => false, 'message' => 'Not a Google News URL'];
        }
        
        $path_parts = explode('/', trim($parsed['path'], '/'));
        
        // Check if URL format is /articles/{base64} or /rss/articles/{base64} or /read/{base64}
        if (count($path_parts) >= 2) {
            $second_last = $path_parts[count($path_parts) - 2];
            if (in_array($second_last, ['articles', 'read'])) {
                $base64_str = end($path_parts);
                // Remove query parameters if present
                $base64_str = explode('?', $base64_str)[0];
                return ['status' => true, 'base64_str' => $base64_str];
            }
        }
        
        return ['status' => false, 'message' => 'Invalid Google News URL format'];
    } catch (Exception $e) {
        return ['status' => false, 'message' => 'Error in get_base64_str: ' . $e->getMessage()];
    }
}

/**
 * Get decoding parameters (signature and timestamp) from Google News page
 * 
 * @param string $base64_str The base64 string from the URL
 * @param int $timeout Timeout in seconds
 * @return array Result with status, signature, timestamp, or error message
 */
function get_decoding_params($base64_str, $timeout = 10) {
    // Try articles URL first
    $url = "https://news.google.com/articles/{$base64_str}";
    $html = fetch_google_news_page($url, $timeout);
    
    // If articles URL fails, try RSS URL
    if (!$html) {
        $url = "https://news.google.com/rss/articles/{$base64_str}";
        $html = fetch_google_news_page($url, $timeout);
    }
    
    if (!$html) {
        return ['status' => false, 'message' => 'Failed to fetch Google News page'];
    }
    
    // Extract data-n-a-sg (signature) and data-n-a-ts (timestamp)
    $signature = null;
    $timestamp = null;
    
    // Look for: <div jscontroller="..." data-n-a-sg="..." data-n-a-ts="...">
    if (preg_match('/data-n-a-sg="([^"]+)"/', $html, $matches)) {
        $signature = $matches[1];
    }
    
    if (preg_match('/data-n-a-ts="([^"]+)"/', $html, $matches)) {
        $timestamp = $matches[1];
    }
    
    if (!$signature || !$timestamp) {
        return ['status' => false, 'message' => 'Failed to extract signature and timestamp from page'];
    }
    
    return [
        'status' => true,
        'signature' => $signature,
        'timestamp' => $timestamp,
        'base64_str' => $base64_str
    ];
}

/**
 * Fetch Google News page HTML
 * 
 * @param string $url The URL to fetch
 * @param int $timeout Timeout in seconds
 * @return string|null The HTML content or null on failure
 */
function fetch_google_news_page($url, $timeout = 10) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
        ]
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error || $http_code !== 200) {
        return null;
    }
    
    return $response;
}

/**
 * Decode the URL using Google's batchexecute endpoint
 * 
 * @param string $signature The signature from the page
 * @param string $timestamp The timestamp from the page
 * @param string $base64_str The base64 string from the URL
 * @param int $timeout Timeout in seconds
 * @return array Result with status and decoded_url or error message
 */
function decode_url_with_params($signature, $timestamp, $base64_str, $timeout = 10) {
    try {
        $url = 'https://news.google.com/_/DotsSplashUi/data/batchexecute';
        
        // Build the payload exactly as in the Python code
        $payload = [
            'Fbv4je',
            '["garturlreq",[["X","X",["X","X"],null,null,1,1,"US:en",null,1,null,null,null,null,null,0,1],"X","X",1,[1,1,1],1,1,null,0,0,null,0],"' . $base64_str . '",' . $timestamp . ',"' . $signature . '"]'
        ];
        
        $payload_json = json_encode([[$payload]]);
        $post_data = 'f.req=' . urlencode($payload_json);
        
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36'
            ]
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            return ['status' => false, 'message' => 'Request error: ' . $error];
        }
        
        if ($http_code !== 200) {
            return ['status' => false, 'message' => 'HTTP error: ' . $http_code];
        }
        
        // Parse the response (format: )]}'\n\n{json}\n{json})
        $lines = explode("\n\n", $response);
        if (count($lines) < 2) {
            return ['status' => false, 'message' => 'Invalid response format'];
        }
        
        // Get the second line and remove trailing characters
        $json_str = rtrim($lines[1], ')');
        $parsed_data = json_decode($json_str, true);
        
        if (!$parsed_data || !isset($parsed_data[0][2])) {
            return ['status' => false, 'message' => 'Failed to parse response JSON'];
        }
        
        $inner_json = json_decode($parsed_data[0][2], true);
        if (!$inner_json || !isset($inner_json[1])) {
            return ['status' => false, 'message' => 'Failed to extract decoded URL from response'];
        }
        
        $decoded_url = $inner_json[1];
        
        return ['status' => true, 'decoded_url' => $decoded_url];
        
    } catch (Exception $e) {
        return ['status' => false, 'message' => 'Error in decode_url: ' . $e->getMessage()];
    }
}

/**
 * Main function: Decode Google News redirect URL to get actual article URL
 * 
 * @param string $google_url The Google News redirect URL
 * @param int $timeout Timeout in seconds (default: 10)
 * @return string|null The final destination URL or null on failure
 */
function decode_google_news_redirect($google_url, $timeout = 10) {
    // Validate input
    if (empty($google_url)) {
        return null;
    }
    
    // If it's not a Google News URL, return as-is
    if (strpos($google_url, 'news.google.com') === false) {
        return $google_url;
    }
    
    try {
        // Step 1: Extract base64 string from URL
        $base64_result = get_base64_str($google_url);
        if (!$base64_result['status']) {
            error_log('Google News decode error: ' . $base64_result['message']);
            return null;
        }
        
        // Step 2: Get decoding parameters (signature and timestamp)
        $params_result = get_decoding_params($base64_result['base64_str'], $timeout);
        if (!$params_result['status']) {
            error_log('Google News decode error: ' . $params_result['message']);
            return null;
        }
        
        // Step 3: Decode the URL using the parameters
        $decode_result = decode_url_with_params(
            $params_result['signature'],
            $params_result['timestamp'],
            $params_result['base64_str'],
            $timeout
        );
        
        if (!$decode_result['status']) {
            error_log('Google News decode error: ' . $decode_result['message']);
            return null;
        }
        
        return $decode_result['decoded_url'];
        
    } catch (Exception $e) {
        error_log('Google News decode exception: ' . $e->getMessage());
        return null;
    }
}

/**
 * Decode URL and extract domain
 * 
 * @param string $google_url The Google News redirect URL
 * @return array Array with 'url' and 'domain' keys
 */
function decode_google_news_with_domain($google_url) {
    $final_url = decode_google_news_redirect($google_url);
    
    if (!$final_url) {
        return [
            'url' => $google_url,
            'domain' => null,
            'decoded' => false
        ];
    }
    
    $parsed = parse_url($final_url);
    $domain = $parsed['host'] ?? null;
    
    // Remove www. prefix
    if ($domain && strpos($domain, 'www.') === 0) {
        $domain = substr($domain, 4);
    }
    
    return [
        'url' => $final_url,
        'domain' => $domain,
        'decoded' => true
    ];
}

/**
 * Batch decode multiple Google News URLs
 * 
 * @param array $urls Array of Google News URLs
 * @param int $timeout Timeout per URL in seconds
 * @return array Array of decoded URLs
 */
function batch_decode_google_news_urls($urls, $timeout = 5) {
    $results = [];
    
    foreach ($urls as $key => $url) {
        $decoded = decode_google_news_redirect($url, $timeout);
        $results[$key] = $decoded ?: $url;
        
        // Small delay to avoid overwhelming the server
        usleep(100000); // 0.1 seconds
    }
    
    return $results;
}

/**
 * Check if URL is a Google News redirect
 * 
 * @param string $url The URL to check
 * @return bool True if it's a Google News redirect URL
 */
function is_google_news_redirect($url) {
    return strpos($url, 'news.google.com/rss/articles/') !== false;
}

/**
 * Extract article ID from Google News URL
 * 
 * @param string $google_url The Google News URL
 * @return string|null The article ID or null
 */
function extract_google_news_article_id($google_url) {
    if (!is_google_news_redirect($google_url)) {
        return null;
    }
    
    $parsed = parse_url($google_url);
    $path = $parsed['path'] ?? '';
    
    // Extract the article ID from the path
    // Format: /rss/articles/{ARTICLE_ID}
    if (preg_match('#/rss/articles/([^/?]+)#', $path, $matches)) {
        return $matches[1];
    }
    
    return null;
}

/**
 * Get clean domain name from URL
 * 
 * @param string $url The URL
 * @return string|null The domain name
 */
function get_domain_from_url($url) {
    $parsed = parse_url($url);
    $domain = $parsed['host'] ?? null;
    
    if (!$domain) {
        return null;
    }
    
    // Remove www. prefix
    if (strpos($domain, 'www.') === 0) {
        $domain = substr($domain, 4);
    }
    
    return $domain;
}
