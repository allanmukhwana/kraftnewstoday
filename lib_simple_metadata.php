<?php
/**
 * Simple Metadata Fetcher
 * Fetches only essential metadata: image, title, description
 * Uses smart fallbacks and placeholder images
 */

/**
 * Fetch essential metadata from URL
 * 
 * @param string $url The URL to fetch metadata from
 * @param int $timeout Timeout in seconds (default: 10)
 * @return array Metadata with image, title, description
 */
function fetch_simple_metadata($url, $timeout = 10) {
    $html = fetch_html_simple($url, $timeout);
    
    if (!$html) {
        // Return defaults if fetch fails
        return [
            'success' => false,
            'url' => $url,
            'image' => generate_placeholder_image($url),
            'title' => extract_title_from_url($url),
            'description' => 'Description not available',
            'error' => 'Failed to fetch URL'
        ];
    }
    
    // Extract metadata with fallbacks
    $og_image = extract_og_tag_simple($html, 'og:image');
    $og_title = extract_og_tag_simple($html, 'og:title');
    $og_description = extract_og_tag_simple($html, 'og:description');
    
    // Fallback to meta tags
    $meta_title = extract_title_tag($html);
    $meta_description = extract_meta_description_simple($html);
    
    // Use OG tags with fallbacks
    $final_title = $og_title ?: $meta_title ?: extract_title_from_url($url);
    $final_description = $og_description ?: $meta_description ?: 'Description not available';
    $final_image = $og_image ?: generate_placeholder_image($url);
    
    return [
        'success' => true,
        'url' => $url,
        'image' => $final_image,
        'title' => $final_title,
        'description' => $final_description,
        'has_og_image' => !empty($og_image),
        'has_og_title' => !empty($og_title),
        'has_og_description' => !empty($og_description)
    ];
}

/**
 * Fetch HTML with minimal overhead
 * 
 * @param string $url URL to fetch
 * @param int $timeout Timeout in seconds
 * @return string|null HTML content or null
 */
function fetch_html_simple($url, $timeout = 10) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_ENCODING => '',
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
            'Cache-Control: no-cache'
        ]
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Accept 200-299 as success
    if ($http_code >= 200 && $http_code < 300 && $response) {
        return $response;
    }
    
    return null;
}

/**
 * Extract Open Graph tag
 * 
 * @param string $html HTML content
 * @param string $property OG property name
 * @return string|null Tag content or null
 */
function extract_og_tag_simple($html, $property) {
    // Try property="og:..."
    if (preg_match('/<meta\s+property=["\']' . preg_quote($property, '/') . '["\']\s+content=["\'](.*?)["\']/i', $html, $matches)) {
        return html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
    }
    
    // Try reversed order
    if (preg_match('/<meta\s+content=["\'](.*?)["\']\s+property=["\']' . preg_quote($property, '/') . '["\']/i', $html, $matches)) {
        return html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
    }
    
    return null;
}

/**
 * Extract meta description
 * 
 * @param string $html HTML content
 * @return string|null Description or null
 */
function extract_meta_description_simple($html) {
    // Try name="description"
    if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/i', $html, $matches)) {
        return html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
    }
    
    // Try reversed
    if (preg_match('/<meta\s+content=["\'](.*?)["\']\s+name=["\']description["\']/i', $html, $matches)) {
        return html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
    }
    
    return null;
}

/**
 * Extract title from <title> tag
 * 
 * @param string $html HTML content
 * @return string|null Title or null
 */
function extract_title_tag($html) {
    if (preg_match('/<title>(.*?)<\/title>/is', $html, $matches)) {
        return html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
    }
    
    return null;
}

/**
 * Generate placeholder image URL
 * 
 * @param string $url Article URL (used to generate unique color)
 * @return string Placeholder image URL
 */
function generate_placeholder_image($url) {
    // Generate a consistent color based on URL
    $hash = md5($url);
    $color = substr($hash, 0, 6);
    
    // Use placehold.co with custom color
    // Format: https://placehold.co/600x400/COLOR/white?text=Article
    return "https://placehold.co/1200x630/{$color}/ffffff?text=Article+Image";
}

/**
 * Extract a title from URL as last resort
 * 
 * @param string $url URL
 * @return string Generated title
 */
function extract_title_from_url($url) {
    $parsed = parse_url($url);
    $host = $parsed['host'] ?? 'Article';
    
    // Remove www.
    $host = preg_replace('/^www\./i', '', $host);
    
    // Capitalize first letter
    return ucfirst($host) . ' Article';
}

/**
 * Fetch and store simple metadata for an article
 * 
 * @param int $article_id Article ID
 * @param string $url Article URL
 * @return bool Success status
 */
function fetch_and_store_simple_metadata($article_id, $url) {
    try {
        $metadata = fetch_simple_metadata($url, 10);
        
        $pdo = get_db_connection();
        
        $stmt = $pdo->prepare("
            UPDATE articles SET
                og_title = ?,
                og_description = ?,
                og_image = ?,
                featured_image = ?,
                metadata_fetched = TRUE,
                metadata_fetched_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $metadata['title'],
            $metadata['description'],
            $metadata['image'],
            $metadata['image'], // Use same image for featured_image
            $article_id
        ]);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error storing simple metadata for article $article_id: " . $e->getMessage());
        
        // Store placeholder data even on error
        try {
            $pdo = get_db_connection();
            $placeholder_image = generate_placeholder_image($url);
            $placeholder_title = extract_title_from_url($url);
            
            $stmt = $pdo->prepare("
                UPDATE articles SET
                    og_title = ?,
                    og_description = ?,
                    og_image = ?,
                    featured_image = ?,
                    metadata_fetched = TRUE,
                    metadata_fetched_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $placeholder_title,
                'Description not available',
                $placeholder_image,
                $placeholder_image,
                $article_id
            ]);
        } catch (Exception $e2) {
            error_log("Failed to store placeholder metadata: " . $e2->getMessage());
        }
        
        return false;
    }
}

/**
 * Batch fetch simple metadata for multiple articles
 * 
 * @param int $limit Number of articles to process
 * @return array Statistics about processing
 */
function batch_fetch_simple_metadata($limit = 20) {
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
    
    $stats = [
        'total' => count($articles),
        'success' => 0,
        'failed' => 0,
        'with_og_image' => 0,
        'with_placeholder' => 0
    ];
    
    foreach ($articles as $article) {
        $metadata = fetch_simple_metadata($article['url'], 10);
        
        if (fetch_and_store_simple_metadata($article['id'], $article['url'])) {
            $stats['success']++;
            
            if ($metadata['has_og_image']) {
                $stats['with_og_image']++;
            } else {
                $stats['with_placeholder']++;
            }
        } else {
            $stats['failed']++;
            $stats['with_placeholder']++;
        }
        
        // Small delay
        usleep(300000); // 0.3 seconds
    }
    
    return $stats;
}
