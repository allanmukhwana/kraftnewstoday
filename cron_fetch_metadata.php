<?php
/**
 * Kraft News Today - Cron Job: Fetch Article Metadata
 * Run every hour to fetch metadata for articles without it
 */

require_once 'config.php';
require_once 'lib_metadata_fetcher.php';

// Log start
$start_time = microtime(true);
log_agent("Starting metadata fetch job");

try {
    $pdo = get_db_connection();
    
    // Log agent task start
    $stmt = $pdo->prepare("
        INSERT INTO agent_logs (task_type, status, message)
        VALUES ('other', 'started', 'Metadata fetch job started')
    ");
    $stmt->execute();
    $log_id = $pdo->lastInsertId();
    
    // Fetch metadata for articles (process 20 at a time)
    $processed_count = batch_fetch_article_metadata(20);
    
    // Calculate execution time
    $execution_time = round(microtime(true) - $start_time, 2);
    
    // Update log
    $stmt = $pdo->prepare("
        UPDATE agent_logs 
        SET status = 'completed',
            message = 'Successfully fetched metadata',
            articles_processed = ?,
            execution_time_seconds = ?
        WHERE id = ?
    ");
    $stmt->execute([$processed_count, $execution_time, $log_id]);
    
    log_agent("Metadata fetch completed: $processed_count articles in {$execution_time}s");
    
} catch (Exception $e) {
    log_error("Metadata fetch job failed: " . $e->getMessage());
    
    if (isset($log_id)) {
        $stmt = $pdo->prepare("
            UPDATE agent_logs 
            SET status = 'failed',
                message = 'Metadata fetch failed',
                error_details = ?
            WHERE id = ?
        ");
        $stmt->execute([$e->getMessage(), $log_id]);
    }
}
