<?php
/**
 * Kraft News Today - Cron Job: Fetch Simple Metadata
 * Run every hour to fetch essential metadata for articles
 */

require_once 'config.php';
require_once 'lib_simple_metadata.php';

// Log start
$start_time = microtime(true);
log_agent("Starting simple metadata fetch job");

try {
    $pdo = get_db_connection();
    
    // Log agent task start
    $stmt = $pdo->prepare("
        INSERT INTO agent_logs (task_type, status, message)
        VALUES ('other', 'started', 'Simple metadata fetch job started')
    ");
    $stmt->execute();
    $log_id = $pdo->lastInsertId();
    
    // Fetch metadata for articles (process 30 at a time - faster than full metadata)
    $stats = batch_fetch_simple_metadata(30);
    
    // Calculate execution time
    $execution_time = round(microtime(true) - $start_time, 2);
    
    // Build message
    $message = sprintf(
        "Processed %d articles: %d successful, %d failed. OG images: %d, Placeholders: %d",
        $stats['total'],
        $stats['success'],
        $stats['failed'],
        $stats['with_og_image'],
        $stats['with_placeholder']
    );
    
    // Update log
    $stmt = $pdo->prepare("
        UPDATE agent_logs 
        SET status = 'completed',
            message = ?,
            articles_processed = ?,
            execution_time_seconds = ?
        WHERE id = ?
    ");
    $stmt->execute([$message, $stats['total'], $execution_time, $log_id]);
    
    log_agent("Simple metadata fetch completed: {$stats['total']} articles in {$execution_time}s");
    
} catch (Exception $e) {
    log_error("Simple metadata fetch job failed: " . $e->getMessage());
    
    if (isset($log_id)) {
        $stmt = $pdo->prepare("
            UPDATE agent_logs 
            SET status = 'failed',
                message = 'Simple metadata fetch failed',
                error_details = ?
            WHERE id = ?
        ");
        $stmt->execute([$e->getMessage(), $log_id]);
    }
}
