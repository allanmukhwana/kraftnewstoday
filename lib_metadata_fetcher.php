<?php
/**
 * Metadata Fetcher Library
 * Fetches Open Graph, meta tags, and featured images from URLs
 */

/**
 * Fetch all metadata from a URL
 * 
 * @param string $url The URL to fetch metadata from
 * @param int $timeout Timeout in seconds
 * @param int $retries Number of retry attempts (default: 2)
 * @return array Metadata array with all extracted information
 */
function fetch_url_metadata($url, $timeout = 10, $retries = 2) {
    $html = null;
    $last_error = '';
    
    // Try multiple times with increasing timeout
    for ($attempt = 0; $attempt <= $retries; $attempt++) {
        $current_timeout = $timeout + ($attempt * 5); // Increase timeout on retries
        $html = fetch_url_html($url, $current_timeout);
        
        if ($html) {
            break;
        }
        
        if ($attempt < $retries) {
            usleep(500000); // Wait 0.5 seconds before retry
        }
    }
    
    if (!$html) {
        return [
            'success' => false,
            'error' => 'Failed to fetch URL after ' . ($retries + 1) . ' attempts',
            'url' => $url
        ];
    }
    
    $metadata = [
        'success' => true,
        'url' => $url,
        'meta_description' => extract_meta_description($html),
        'og_title' => extract_og_tag($html, 'og:title'),
        'og_description' => extract_og_tag($html, 'og:description'),
        'og_image' => extract_og_tag($html, 'og:image'),
        'og_type' => extract_og_tag($html, 'og:type'),
        'og_site_name' => extract_og_tag($html, 'og:site_name'),
        'og_url' => extract_og_tag($html, 'og:url'),
        'twitter_card' => extract_twitter_tag($html, 'twitter:card'),
        'twitter_title' => extract_twitter_tag($html, 'twitter:title'),
        'twitter_description' => extract_twitter_tag($html, 'twitter:description'),
        'twitter_image' => extract_twitter_tag($html, 'twitter:image'),
        'canonical_url' => extract_canonical_url($html),
        'featured_image' => extract_featured_image($html, $url),
        'title' => extract_title($html),
        'author' => extract_author($html),
        'published_date' => extract_published_date($html)
    ];
    
    // Use OG image as featured image if no featured image found
    if (empty($metadata['featured_image']) && !empty($metadata['og_image'])) {
        $metadata['featured_image'] = $metadata['og_image'];
    }
    
    // Use Twitter image if no other image found
    if (empty($metadata['featured_image']) && !empty($metadata['twitter_image'])) {
        $metadata['featured_image'] = $metadata['twitter_image'];
    }
    
    return $metadata;
}

/**
 * Fetch HTML content from URL
 * 
 * @param string $url The URL to fetch
 * @param int $timeout Timeout in seconds
 * @return string|null HTML content or null on failure
 */
function fetch_url_html($url, $timeout = 10) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_ENCODING => '', // Accept all encodings
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
            'Accept-Encoding: gzip, deflate, br',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'DNT: 1',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1',
            'Sec-Fetch-Dest: document',
            'Sec-Fetch-Mode: navigate',
            'Sec-Fetch-Site: none'
        ]
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        error_log("Failed to fetch URL $url: Error: $error");
        return null;
    }
    
    // Accept 200-299 status codes as success
    if ($http_code < 200 || $http_code >= 400) {
        error_log("Failed to fetch URL $url: HTTP $http_code");
        return null;
    }
    
    return $response;
}

/**
 * Extract meta description from HTML
 * 
 * @param string $html HTML content
 * @return string|null Meta description or null
 */
function extract_meta_description($html) {
    // Try <meta name="description">
    if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/i', $html, $matches)) {
        return html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
    }
    
    // Try <meta property="description">
    if (preg_match('/<meta\s+property=["\']description["\']\s+content=["\'](.*?)["\']/i', $html, $matches)) {
        return html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
    }
    
    return null;
}

/**
 * Extract Open Graph tag from HTML
 * 
 * @param string $html HTML content
 * @param string $property OG property name (e.g., 'og:title')
 * @return string|null Tag content or null
 */
function extract_og_tag($html, $property) {
    // Try <meta property="og:...">
    if (preg_match('/<meta\s+property=["\']' . preg_quote($property, '/') . '["\']\s+content=["\'](.*?)["\']/i', $html, $matches)) {
        return html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
    }
    
    // Try reversed order: content first
    if (preg_match('/<meta\s+content=["\'](.*?)["\']\s+property=["\']' . preg_quote($property, '/') . '["\']/i', $html, $matches)) {
        return html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
    }
    
    return null;
}

/**
 * Extract Twitter Card tag from HTML
 * 
 * @param string $html HTML content
 * @param string $name Twitter tag name (e.g., 'twitter:card')
 * @return string|null Tag content or null
 */
function extract_twitter_tag($html, $name) {
    // Try <meta name="twitter:...">
    if (preg_match('/<meta\s+name=["\']' . preg_quote($name, '/') . '["\']\s+content=["\'](.*?)["\']/i', $html, $matches)) {
        return html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
    }
    
    // Try reversed order
    if (preg_match('/<meta\s+content=["\'](.*?)["\']\s+name=["\']' . preg_quote($name, '/') . '["\']/i', $html, $matches)) {
        return html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
    }
    
    return null;
}

/**
 * Extract canonical URL from HTML
 * 
 * @param string $html HTML content
 * @return string|null Canonical URL or null
 */
function extract_canonical_url($html) {
    if (preg_match('/<link\s+rel=["\']canonical["\']\s+href=["\'](.*?)["\']/i', $html, $matches)) {
        return $matches[1];
    }
    
    if (preg_match('/<link\s+href=["\'](.*?)["\']\s+rel=["\']canonical["\']/i', $html, $matches)) {
        return $matches[1];
    }
    
    return null;
}

/**
 * Extract featured/hero image from HTML
 * 
 * @param string $html HTML content
 * @param string $base_url Base URL for resolving relative URLs
 * @return string|null Featured image URL or null
 */
function extract_featured_image($html, $base_url) {
    // Try common featured image patterns
    
    // 1. Look for <img> with class containing 'featured', 'hero', 'main'
    if (preg_match('/<img[^>]+class=["\'][^"\']*(?:featured|hero|main|article-image|post-image)[^"\']*["\'][^>]+src=["\'](.*?)["\']/i', $html, $matches)) {
        return resolve_url($matches[1], $base_url);
    }
    
    // 2. Look for <img> with id containing 'featured', 'hero', 'main'
    if (preg_match('/<img[^>]+id=["\'][^"\']*(?:featured|hero|main)[^"\']*["\'][^>]+src=["\'](.*?)["\']/i', $html, $matches)) {
        return resolve_url($matches[1], $base_url);
    }
    
    // 3. Look for first <img> in <article> tag
    if (preg_match('/<article[^>]*>.*?<img[^>]+src=["\'](.*?)["\']/is', $html, $matches)) {
        return resolve_url($matches[1], $base_url);
    }
    
    // 4. Look for first <img> in main content area
    if (preg_match('/<(?:main|div[^>]+class=["\'][^"\']*(?:content|article|post)[^"\']*["\'])[^>]*>.*?<img[^>]+src=["\'](.*?)["\']/is', $html, $matches)) {
        return resolve_url($matches[1], $base_url);
    }
    
    // 5. Look for <figure> with <img>
    if (preg_match('/<figure[^>]*>.*?<img[^>]+src=["\'](.*?)["\']/is', $html, $matches)) {
        return resolve_url($matches[1], $base_url);
    }
    
    return null;
}

/**
 * Extract title from HTML
 * 
 * @param string $html HTML content
 * @return string|null Title or null
 */
function extract_title($html) {
    if (preg_match('/<title>(.*?)<\/title>/is', $html, $matches)) {
        return html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
    }
    
    return null;
}

/**
 * Extract author from HTML
 * 
 * @param string $html HTML content
 * @return string|null Author name or null
 */
function extract_author($html) {
    // Try <meta name="author">
    if (preg_match('/<meta\s+name=["\']author["\']\s+content=["\'](.*?)["\']/i', $html, $matches)) {
        return html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
    }
    
    // Try article:author
    if (preg_match('/<meta\s+property=["\']article:author["\']\s+content=["\'](.*?)["\']/i', $html, $matches)) {
        return html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
    }
    
    // Try <span> or <div> with class="author"
    if (preg_match('/<(?:span|div|a)[^>]+class=["\'][^"\']*author[^"\']*["\'][^>]*>(.*?)<\/(?:span|div|a)>/is', $html, $matches)) {
        return html_entity_decode(strip_tags($matches[1]), ENT_QUOTES, 'UTF-8');
    }
    
    return null;
}

/**
 * Extract published date from HTML
 * 
 * @param string $html HTML content
 * @return string|null Published date or null
 */
function extract_published_date($html) {
    // Try article:published_time
    if (preg_match('/<meta\s+property=["\']article:published_time["\']\s+content=["\'](.*?)["\']/i', $html, $matches)) {
        return $matches[1];
    }
    
    // Try <time> tag with datetime
    if (preg_match('/<time[^>]+datetime=["\'](.*?)["\']/i', $html, $matches)) {
        return $matches[1];
    }
    
    // Try datePublished schema.org
    if (preg_match('/"datePublished"\s*:\s*"(.*?)"/i', $html, $matches)) {
        return $matches[1];
    }
    
    return null;
}

/**
 * Resolve relative URL to absolute URL
 * 
 * @param string $url URL to resolve
 * @param string $base_url Base URL
 * @return string Absolute URL
 */
function resolve_url($url, $base_url) {
    // Already absolute
    if (preg_match('/^https?:\/\//i', $url)) {
        return $url;
    }
    
    $base_parts = parse_url($base_url);
    
    // Protocol-relative URL
    if (strpos($url, '//') === 0) {
        return ($base_parts['scheme'] ?? 'https') . ':' . $url;
    }
    
    $base_scheme = $base_parts['scheme'] ?? 'https';
    $base_host = $base_parts['host'] ?? '';
    
    // Absolute path
    if (strpos($url, '/') === 0) {
        return $base_scheme . '://' . $base_host . $url;
    }
    
    // Relative path
    $base_path = dirname($base_parts['path'] ?? '/');
    return $base_scheme . '://' . $base_host . $base_path . '/' . $url;
}

/**
 * Fetch and store metadata for an article
 * 
 * @param int $article_id Article ID
 * @param string $url Article URL
 * @return bool Success status
 */
function fetch_and_store_article_metadata($article_id, $url) {
    try {
        $metadata = fetch_url_metadata($url, 15, 1); // 15s timeout, 1 retry
        
        if (!$metadata['success']) {
            error_log("Failed to fetch metadata for article $article_id ($url): {$metadata['error']}");
            
            // Mark as attempted even if failed, to avoid retrying indefinitely
            $pdo = get_db_connection();
            $stmt = $pdo->prepare("
                UPDATE articles SET
                    metadata_fetched = TRUE,
                    metadata_fetched_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$article_id]);
            
            return false;
        }
        
        $pdo = get_db_connection();
        
        $stmt = $pdo->prepare("
            UPDATE articles SET
                meta_description = ?,
                og_title = ?,
                og_description = ?,
                og_image = ?,
                og_type = ?,
                og_site_name = ?,
                featured_image = ?,
                twitter_card = ?,
                twitter_title = ?,
                twitter_description = ?,
                twitter_image = ?,
                canonical_url = ?,
                metadata_fetched = TRUE,
                metadata_fetched_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $metadata['meta_description'],
            $metadata['og_title'],
            $metadata['og_description'],
            $metadata['og_image'],
            $metadata['og_type'],
            $metadata['og_site_name'],
            $metadata['featured_image'],
            $metadata['twitter_card'],
            $metadata['twitter_title'],
            $metadata['twitter_description'],
            $metadata['twitter_image'],
            $metadata['canonical_url'],
            $article_id
        ]);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error storing metadata for article $article_id: " . $e->getMessage());
        return false;
    }
}

/**
 * Batch fetch metadata for multiple articles
 * 
 * @param int $limit Number of articles to process
 * @return int Number of articles processed
 */
function batch_fetch_article_metadata($limit = 10) {
    $pdo = get_db_connection();
    
    // Get articles without metadata
    $stmt = $pdo->prepare("
        SELECT id, url 
        FROM articles 
        WHERE metadata_fetched = FALSE 
        AND url IS NOT NULL 
        AND url != ''
        ORDER BY scraped_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    $articles = $stmt->fetchAll();
    
    $processed = 0;
    
    foreach ($articles as $article) {
        if (fetch_and_store_article_metadata($article['id'], $article['url'])) {
            $processed++;
        }
        
        // Small delay to avoid overwhelming servers
        usleep(500000); // 0.5 seconds
    }
    
    return $processed;
}
