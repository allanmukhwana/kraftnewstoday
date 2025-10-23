<?php
/**
 * Kraft News Today - Cron Job: News Scraping
 * Run every 6 hours to scrape new articles
 */

require_once 'config.php';
require_once 'agent_scraper.php';

// Log start
$start_time = microtime(true);
log_agent("Starting news scraping job");

try {
    $pdo = get_db_connection();
    
    // Log agent task start
    $stmt = $pdo->prepare("
        INSERT INTO agent_logs (task_type, status, message)
        VALUES ('scrape', 'started', 'News scraping job started')
    ");
    $stmt->execute();
    $log_id = $pdo->lastInsertId();
    
    // Scrape articles
    $scraper = get_news_scraper();
    $total_articles = $scraper->scrapeAllIndustries();
    
    // Calculate execution time
    $execution_time = round(microtime(true) - $start_time, 2);
    
    // Update log
    $stmt = $pdo->prepare("
        UPDATE agent_logs 
        SET status = 'completed',
            message = 'Successfully scraped articles',
            articles_processed = ?,
            execution_time_seconds = ?
        WHERE id = ?
    ");
    $stmt->execute([$total_articles, $execution_time, $log_id]);
    
    log_agent("Scraping completed: $total_articles articles in {$execution_time}s");
    
} catch (Exception $e) {
    log_error("Scraping job failed: " . $e->getMessage());
    
    if (isset($log_id)) {
        $stmt = $pdo->prepare("
            UPDATE agent_logs 
            SET status = 'failed',
                error_details = ?
            WHERE id = ?
        ");
        $stmt->execute([$e->getMessage(), $log_id]);
    }
}
