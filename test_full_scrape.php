<?php
/**
 * Test Full Scraping Flow
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== Testing Full Scraping Flow ===\n\n";

require_once 'config.php';
require_once 'agent_scraper.php';

echo "1. Creating NewsScraper instance...\n";
$scraper = new NewsScraper();
echo "   ✓ Created\n\n";

echo "2. Testing scrapeIndustry() for 'technology'...\n";
try {
    $count = $scraper->scrapeIndustry('technology', 'Technology');
    echo "   ✓ Scraped $count articles\n\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    echo "   Stack: " . $e->getTraceAsString() . "\n\n";
}

echo "3. Checking database for articles...\n";
try {
    $pdo = get_db_connection();
    $stmt = $pdo->query("SELECT COUNT(*) FROM articles WHERE industry_code = 'technology'");
    $total = $stmt->fetchColumn();
    echo "   Total technology articles in DB: $total\n\n";
    
    // Get latest 3 articles
    $stmt = $pdo->query("
        SELECT title, source, published_at 
        FROM articles 
        WHERE industry_code = 'technology' 
        ORDER BY scraped_at DESC 
        LIMIT 3
    ");
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($articles) > 0) {
        echo "   Latest articles:\n";
        foreach ($articles as $article) {
            echo "   - {$article['title']} ({$article['source']}) - {$article['published_at']}\n";
        }
    }
} catch (Exception $e) {
    echo "   ✗ Database Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
