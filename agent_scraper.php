<?php
/**
 * Kraft News Today - Web Scraping Module
 * Handles news discovery and article scraping using Google News RSS
 */

//display errors
//ini_set('display_errors', 1);error_reporting(E_ALL);

require_once 'config.php';
require_once 'lib_news_scraper.php';
require_once 'lib_metadata_fetcher.php';

class NewsScraper {
    private $google_news;
    
    public function __construct() {
        $this->google_news = new GoogleNewsRSS();
    }
    
    /**
     * Scrape news articles for specific industry
     */
    public function scrapeIndustry($industry_code, $industry_name) {
        log_agent("Starting scrape for industry: $industry_name");
        
        // Get search keywords for industry
        $keywords = $this->getIndustryKeywords($industry_code);
        
        // Fetch articles from News API
        $articles = $this->fetchFromNewsAPI($keywords);
        
        // Store articles in database
        $stored_count = 0;
        $pdo = get_db_connection();
        
        foreach ($articles as $article) {
            try {
                // Check if article already exists
                $stmt = $pdo->prepare("SELECT id FROM articles WHERE url = ?");
                $stmt->execute([$article['url']]);
                
                if (!$stmt->fetch()) {
                    // Insert new article
                    $stmt = $pdo->prepare("
                        INSERT INTO articles 
                        (title, url, source, author, description, content, image_url, 
                         published_at, industry_code, relevance_score)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $article['title'],
                        $article['url'],
                        $article['source'],
                        $article['author'],
                        $article['description'],
                        $article['content'],
                        $article['image_url'],
                        $article['published_at'],
                        $industry_code,
                        0.5 // Default relevance score
                    ]);
                    
                    $stored_count++;
                }
            } catch (PDOException $e) {
                log_error("Failed to store article: " . $e->getMessage());
            }
        }
        
        log_agent("Scraped $stored_count new articles for $industry_name");
        return $stored_count;
    }
    
    /**
     * Scrape all active industries
     */
    public function scrapeAllIndustries() {
        $pdo = get_db_connection();
        
        // Get all industries that users are tracking
        $stmt = $pdo->query("
            SELECT DISTINCT industry_code 
            FROM user_industries
        ");
        $industries = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $total_articles = 0;
        foreach ($industries as $industry_code) {
            $industry_name = $GLOBALS['INDUSTRIES'][$industry_code] ?? $industry_code;
            $count = $this->scrapeIndustry($industry_code, $industry_name);
            $total_articles += $count;
        }
        
        return $total_articles;
    }
    
    /**
     * Get search keywords for industry
     */
    private function getIndustryKeywords($industry_code) {
        $keywords_map = [
            'technology' => 'technology OR tech OR software OR hardware',
            'healthcare' => 'healthcare OR medical OR health OR pharma',
            'finance' => 'finance OR fintech OR banking OR investment',
            'ai_ml' => 'artificial intelligence OR machine learning OR AI OR ML',
            'cybersecurity' => 'cybersecurity OR security OR hacking OR breach',
            'blockchain' => 'blockchain OR cryptocurrency OR bitcoin OR crypto',
            'ecommerce' => 'ecommerce OR e-commerce OR online shopping OR retail',
            'marketing' => 'digital marketing OR advertising OR marketing',
            'real_estate' => 'real estate OR property OR housing',
            'energy' => 'energy OR renewable OR solar OR sustainability',
            'education' => 'education OR edtech OR learning OR university',
            'entertainment' => 'entertainment OR media OR streaming OR gaming',
            'automotive' => 'automotive OR electric vehicle OR EV OR cars',
            'aerospace' => 'aerospace OR aviation OR space OR defense',
            'biotech' => 'biotechnology OR biotech OR genetics OR pharma'
        ];
        
        return $keywords_map[$industry_code] ?? $industry_code;
    }
    
    /**
     * Fetch articles from Google News RSS
     */
    private function fetchFromNewsAPI($keywords) {
        try {
            // Use Google News RSS to search for articles
            $result = $this->google_news->searchNews($keywords, [
                'when' => '24h',  // Last 24 hours
                'country' => 'US',
                'language' => 'en-US'
            ]);
            
            if (isset($result['success']) && $result['success']) {
                return $this->parseGoogleNewsResponse($result['articles']);
            }
            
            log_error("Google News RSS fetch failed");
            return [];
            
        } catch (Exception $e) {
            log_error("Google News RSS error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Parse Google News RSS response to match our database format
     */
    private function parseGoogleNewsResponse($articles) {
        $parsed = [];
        
        foreach ($articles as $article) {
            $parsed[] = [
                'title' => $article['title'] ?? 'Untitled',
                'url' => $article['link'] ?? '',
                'source' => $article['source'] ?? 'Unknown',
                'author' => null,  // Google News RSS doesn't provide author
                'description' => $article['description_clean'] ?? '',
                'content' => $article['description_clean'] ?? '',
                'image_url' => null,  // Google News RSS doesn't provide images
                'published_at' => $article['date_formatted'] ?? date('Y-m-d H:i:s')
            ];
        }
        
        return $parsed;
    }
    
    /**
     * Generate mock articles for demo/testing
     */
    private function generateMockArticles($keywords) {
        $mock_articles = [
            [
                'title' => 'Major Breakthrough in AI Technology Reshapes Industry Standards',
                'url' => 'https://example.com/article-' . uniqid(),
                'source' => 'Tech News Daily',
                'author' => 'Sarah Johnson',
                'description' => 'Researchers announce significant advancement in artificial intelligence that could transform how businesses operate.',
                'content' => 'In a groundbreaking development, researchers have unveiled a new AI system that demonstrates unprecedented capabilities in natural language understanding and reasoning. The technology, which has been in development for over three years, represents a significant leap forward in the field of artificial intelligence. Industry experts suggest this breakthrough could have far-reaching implications for businesses across multiple sectors, from healthcare to finance. The system\'s ability to process and analyze complex data sets in real-time opens up new possibilities for automation and decision-making support.',
                'image_url' => null,
                'published_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
            ],
            [
                'title' => 'Market Leaders Announce Strategic Partnership to Drive Innovation',
                'url' => 'https://example.com/article-' . uniqid(),
                'source' => 'Business Wire',
                'author' => 'Michael Chen',
                'description' => 'Two industry giants join forces in unprecedented collaboration aimed at accelerating technological advancement.',
                'content' => 'In a move that has surprised industry analysts, two leading companies have announced a strategic partnership that will combine their respective strengths in technology and market reach. The collaboration is expected to accelerate innovation and bring new products to market faster than either company could achieve independently. Executives from both organizations emphasized their shared vision for the future and commitment to delivering value to customers. The partnership includes joint research and development initiatives, shared intellectual property, and coordinated go-to-market strategies.',
                'image_url' => null,
                'published_at' => date('Y-m-d H:i:s', strtotime('-5 hours'))
            ],
            [
                'title' => 'New Regulations Set to Transform Industry Landscape',
                'url' => 'https://example.com/article-' . uniqid(),
                'source' => 'Industry Today',
                'author' => 'Emily Rodriguez',
                'description' => 'Government announces comprehensive regulatory framework that will impact how companies operate.',
                'content' => 'Regulatory authorities have unveiled a new framework that will significantly impact industry operations. The regulations, which will be phased in over the next 18 months, aim to address concerns about data privacy, security, and consumer protection. Industry stakeholders have expressed mixed reactions, with some welcoming the clarity provided by the new rules while others worry about compliance costs. Legal experts suggest companies should begin preparing now to ensure they meet the new requirements when they take effect.',
                'image_url' => null,
                'published_at' => date('Y-m-d H:i:s', strtotime('-8 hours'))
            ],
            [
                'title' => 'Emerging Startups Challenge Established Market Leaders',
                'url' => 'https://example.com/article-' . uniqid(),
                'source' => 'Startup Weekly',
                'author' => 'David Park',
                'description' => 'New wave of innovative companies disrupting traditional business models with fresh approaches.',
                'content' => 'A new generation of startups is challenging the dominance of established players with innovative business models and cutting-edge technology. These companies, many founded by industry veterans, are leveraging modern approaches to solve longstanding problems in more efficient ways. Venture capital firms have taken notice, with several startups securing significant funding rounds in recent months. Industry observers suggest this trend could lead to a major reshuffling of market positions over the next few years as traditional companies struggle to adapt to changing customer expectations.',
                'image_url' => null,
                'published_at' => date('Y-m-d H:i:s', strtotime('-12 hours'))
            ],
            [
                'title' => 'Research Study Reveals Surprising Trends in Consumer Behavior',
                'url' => 'https://example.com/article-' . uniqid(),
                'source' => 'Market Research Insights',
                'author' => 'Jennifer Williams',
                'description' => 'Comprehensive study uncovers unexpected patterns in how consumers make purchasing decisions.',
                'content' => 'A major research study has revealed surprising insights into consumer behavior that could reshape marketing strategies across the industry. The study, which surveyed thousands of consumers over a six-month period, found that traditional assumptions about purchasing decisions may no longer hold true. Researchers identified several emerging trends, including increased emphasis on sustainability, preference for personalized experiences, and growing distrust of traditional advertising. Companies that adapt their strategies to align with these findings may gain a significant competitive advantage.',
                'image_url' => null,
                'published_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ]
        ];
        
        // Return random subset of mock articles
        shuffle($mock_articles);
        return array_slice($mock_articles, 0, rand(3, 5));
    }
}

// Helper function to get scraper instance
function get_news_scraper() {
    static $scraper = null;
    if ($scraper === null) {
        $scraper = new NewsScraper();
    }
    return $scraper;
}
