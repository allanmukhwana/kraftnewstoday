<?php
/**
 * Kraft News Today - Google News RSS Scraper
 * Fetches news from Google News RSS feeds (no API key required)
 */

// Load URL decoder library
require_once __DIR__ . '/lib_url_decoder.php';

/**
 * Google News RSS Scraper
 */
class GoogleNewsRSS {
    private $base_url = 'https://news.google.com/rss';
    private $language = 'en-US';
    private $country = 'US';
    
    /**
     * Fetch top headlines
     */
    public function getTopHeadlines($country = 'US', $language = 'en-US') {
        $url = $this->base_url . "?hl={$language}&gl={$country}&ceid={$country}:{$language}";
        return $this->fetchAndParse($url);
    }
    
    /**
     * Fetch headlines by topic
     * Topics: WORLD, NATION, BUSINESS, TECHNOLOGY, ENTERTAINMENT, SPORTS, SCIENCE, HEALTH
     */
    public function getHeadlinesByTopic($topic, $country = 'US', $language = 'en-US') {
        $topic = strtoupper($topic);
        $url = $this->base_url . "/headlines/section/topic/{$topic}?hl={$language}&gl={$country}&ceid={$country}:{$language}";
        return $this->fetchAndParse($url);
    }
    
    /**
     * Search news by keyword with advanced options
     * 
     * @param string $query Search query (supports AND, OR, quotes for exact match)
     * @param array $options Optional parameters:
     *   - when: Time range (e.g., '1h', '24h', '7d', '30d')
     *   - after: Start date (YYYY-MM-DD)
     *   - before: End date (YYYY-MM-DD)
     *   - site: Specific website (e.g., 'reuters.com')
     *   - intitle: Search in title only
     */
    public function searchNews($query, $options = []) {
        $search_query = $query;
        
        // Add time range
        if (isset($options['when'])) {
            $search_query .= " when:{$options['when']}";
        }
        
        // Add date range
        if (isset($options['after'])) {
            $search_query .= " after:{$options['after']}";
        }
        if (isset($options['before'])) {
            $search_query .= " before:{$options['before']}";
        }
        
        // Add site filter
        if (isset($options['site'])) {
            $search_query .= " site:{$options['site']}";
        }
        
        // Add title search
        if (isset($options['intitle'])) {
            $search_query .= " intitle:{$options['intitle']}";
        }
        
        $country = $options['country'] ?? 'US';
        $language = $options['language'] ?? 'en-US';
        
        $encoded_query = urlencode($search_query);
        $url = $this->base_url . "/search?q={$encoded_query}&hl={$language}&gl={$country}&ceid={$country}:{$language}";
        
        return $this->fetchAndParse($url);
    }
    
    /**
     * Fetch RSS feed and parse to JSON
     */
    private function fetchAndParse($url) {
        // Fetch RSS content using cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $rss_content = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200 || !$rss_content) {
            return ['error' => 'Failed to fetch RSS feed', 'http_code' => $http_code];
        }
        
        // Parse RSS XML
        return $this->parseRSSToJSON($rss_content);
    }
    
    /**
     * Parse RSS XML to JSON array
     */
    private function parseRSSToJSON($xml_content) {
        // Suppress XML parsing errors
        libxml_use_internal_errors(true);
        
        $xml = simplexml_load_string($xml_content);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            return ['error' => 'Failed to parse RSS XML', 'details' => $errors];
        }
        
        $articles = [];
        
        // Parse each item in the RSS feed
        foreach ($xml->channel->item as $item) {
            $google_url = (string) $item->link;
            
            $article = [
                'title' => (string) $item->title,
                'link' => $google_url,
                'link_google' => $google_url, // Keep original Google News URL
                'pub_date' => (string) $item->pubDate,
                'description' => (string) $item->description,
                'source' => (string) $item->source,
                'guid' => (string) $item->guid
            ];
            
            // Decode Google News redirect URL to get actual article URL
            if (is_google_news_redirect($google_url)) {
                $decoded_url = decode_google_news_redirect($google_url);
                if ($decoded_url && $decoded_url !== $google_url) {
                    $article['link'] = $decoded_url;
                    $article['link_decoded'] = true;
                    $article['domain'] = get_domain_from_url($decoded_url);
                } else {
                    $article['link_decoded'] = false;
                }
            }
            
            // Parse publication date to timestamp
            $article['timestamp'] = strtotime($article['pub_date']);
            $article['date_formatted'] = date('Y-m-d H:i:s', $article['timestamp']);
            
            // Extract source URL if available
            if (isset($item->source['url'])) {
                $article['source_url'] = (string) $item->source['url'];
            }
            
            // Clean description (remove HTML tags)
            $article['description_clean'] = strip_tags($article['description']);
            
            $articles[] = $article;
        }
        
        return [
            'success' => true,
            'count' => count($articles),
            'articles' => $articles
        ];
    }
    
    /**
     * Search by industry/topic with keywords
     */
    public function searchByIndustry($industry, $keywords = [], $timeRange = '24h') {
        // Build search query
        $query_parts = [$industry];
        
        if (!empty($keywords)) {
            $query_parts = array_merge($query_parts, $keywords);
        }
        
        $query = implode(' OR ', $query_parts);
        
        return $this->searchNews($query, ['when' => $timeRange]);
    }
}

/**
 * Helper function to fetch news
 */
function fetch_google_news($type = 'search', $params = []) {
    $scraper = new GoogleNewsRSS();
    
    switch ($type) {
        case 'headlines':
            return $scraper->getTopHeadlines(
                $params['country'] ?? 'US',
                $params['language'] ?? 'en-US'
            );
            
        case 'topic':
            return $scraper->getHeadlinesByTopic(
                $params['topic'] ?? 'TECHNOLOGY',
                $params['country'] ?? 'US',
                $params['language'] ?? 'en-US'
            );
            
        case 'search':
            return $scraper->searchNews(
                $params['query'] ?? '',
                $params['options'] ?? []
            );
            
        case 'industry':
            return $scraper->searchByIndustry(
                $params['industry'] ?? '',
                $params['keywords'] ?? [],
                $params['timeRange'] ?? '24h'
            );
            
        default:
            return ['error' => 'Invalid type'];
    }
}

// Example usage (uncomment to test):
/*
// Test 1: Get top headlines
$headlines = fetch_google_news('headlines');
echo json_encode($headlines, JSON_PRETTY_PRINT);

// Test 2: Get technology news
$tech_news = fetch_google_news('topic', ['topic' => 'TECHNOLOGY']);
echo json_encode($tech_news, JSON_PRETTY_PRINT);

// Test 3: Search for specific keyword
$search_results = fetch_google_news('search', [
    'query' => 'artificial intelligence',
    'options' => ['when' => '24h']
]);
echo json_encode($search_results, JSON_PRETTY_PRINT);

// Test 4: Search by industry
$industry_news = fetch_google_news('industry', [
    'industry' => 'healthcare',
    'keywords' => ['medical', 'hospital', 'pharmaceutical'],
    'timeRange' => '24h'
]);
echo json_encode($industry_news, JSON_PRETTY_PRINT);
*/
